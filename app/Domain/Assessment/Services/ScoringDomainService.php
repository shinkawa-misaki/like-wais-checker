<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Services;

use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Entities\Question;
use App\Domain\Assessment\ValueObjects\IndexType;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;

final class ScoringDomainService
{
    public function __construct(
        private readonly ?NaturalLanguageScoringInterface $nlScoring = null,
    ) {}

    /**
     * Grade a single answer against its question.
     * For FREE_TEXT questions, auto-grading is applied based on content analysis.
     * For MULTIPLE_CHOICE and SEQUENCE questions, auto-grading is applied.
     * For TIME_BASED questions (Symbol Search), penalty scoring is applied.
     */
    public function gradeAnswer(Question $question, Answer $answer): Score
    {
        return match ($question->getQuestionType()) {
            \App\Domain\Assessment\ValueObjects\QuestionType::FREE_TEXT       => $this->gradeFreeText($question, $answer),
            \App\Domain\Assessment\ValueObjects\QuestionType::MULTIPLE_CHOICE,
            \App\Domain\Assessment\ValueObjects\QuestionType::SEQUENCE        => $this->gradeExact($question, $answer),
            \App\Domain\Assessment\ValueObjects\QuestionType::TIME_BASED      => $answer->getAwardedScore(),
        };
    }

    private function gradeExact(Question $question, Answer $answer): Score
    {
        // 未回答（空の回答）は0点
        $response = trim($answer->getResponse());
        if ($response === '') {
            return Score::zero();
        }

        $isCorrect = strtolower($response) === strtolower(trim($question->getCorrectAnswer()));

        return $isCorrect ? new Score(1.0) : Score::zero();
    }

    /**
     * 自由記述問題の自動採点（類似・語彙問題用）
     * 0点: 空または全く見当違いの回答
     * 1点: 方向性が合っている
     * 2点: キーワードや概念が含まれている
     */
    private function gradeFreeText(Question $question, Answer $answer): Score
    {
        $response = trim($answer->getResponse());

        if ($response === '') {
            return Score::zero();
        }

        if ($this->isSimilarityQuestion($question)) {
            return $this->gradeSimilarityAnswer($question, $response);
        }

        if ($this->isVocabularyQuestion($question)) {
            return $this->gradeVocabularyAnswer($question, $response);
        }

        return $this->gradeByLength(mb_strlen($response));
    }

    /**
     * 類似問題かどうかを判定
     */
    private function isSimilarityQuestion(Question $question): bool
    {
        $content = $question->getContent();
        return str_contains($content, '共通点');
    }

    /**
     * 語彙問題かどうかを判定
     */
    private function isVocabularyQuestion(Question $question): bool
    {
        $content = $question->getContent();
        return str_contains($content, 'どういう意味')
            || str_contains($content, '意味を説明')
            || str_contains($content, '言葉の意味');
    }

    /**
     * 類似問題の採点
     *
     * 自然言語マッチングを使用:
     * - 2点: 文章・説明レベルで共通点を正確に捉えている
     * - 1点: 単語レベルで共通点の概念を捉えている
     * - 0点: 見当違いまたは無回答
     */
    private function gradeSimilarityAnswer(Question $question, string $response): Score
    {
        if ($this->nlScoring === null) {
            return Score::zero();
        }

        $score = $this->nlScoring->scoreSimilarityAnswer(
            $question->getContent(),
            $response,
        );

        return new Score((float) $score);
    }

    /**
     * 語彙問題の採点
     *
     * 採点方針（寛容寄り）:
     * - correct_answer との3文字以上の共通部分文字列があれば 2点（長さ>=8）or 1点
     * - ・区切りキーワードが1つ以上マッチすれば 2点（長さ>=8）or 1点
     * - 8文字以上書いていれば 1点
     * - それ以外は 0点
     */
    private function gradeVocabularyAnswer(Question $question, string $response): Score
    {
        $length     = mb_strlen($response);
        $correct    = $question->getCorrectAnswer();

        // ① correct_answer との共通部分文字列チェック（3文字以上）
        if ($this->hasCommonSubstring($response, $correct, 3)) {
            return new Score($length >= 8 ? 2.0 : 1.0);
        }

        // ② ・区切りキーワードが存在する場合（類似問題の correct_answer 形式）
        $keywords = $this->extractKeywords($correct);
        if (count($keywords) >= 2) {
            foreach ($keywords as $kw) {
                if (str_contains($response, $kw)) {
                    return new Score($length >= 8 ? 2.0 : 1.0);
                }
            }
        }

        // ③ 8文字以上書いていれば 1点
        if ($length >= 8) {
            return new Score(1.0);
        }

        return Score::zero();
    }

    /**
     * correct_answer 文字列からキーワードを抽出する
     * 区切り文字：「・」「、」「。」「／」「/」半角スペース
     *
     * @return array<string>
     */
    private function extractKeywords(string $text): array
    {
        $parts = preg_split('/[・、。／\/\s]+/u', $text) ?: [];

        return array_values(array_filter(
            array_map('trim', $parts),
            fn (string $kw) => mb_strlen($kw) >= 2
        ));
    }

    /**
     * 文字列 $haystack に $needle の minLen 文字以上の共通部分文字列があるか判定
     */
    private function hasCommonSubstring(string $haystack, string $needle, int $minLen): bool
    {
        $haystackLen = mb_strlen($haystack);

        if ($haystackLen < $minLen) {
            return false;
        }

        for ($i = 0; $i <= $haystackLen - $minLen; $i++) {
            $sub = mb_substr($haystack, $i, $minLen);
            if (str_contains($needle, $sub)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 長さベースの採点
     */
    private function gradeByLength(int $length): Score
    {
        if ($length >= 30) {
            return new Score(2.0);
        } else if ($length >= 15) {
            return new Score(1.0);
        }
        return Score::zero();
    }

    /**
     * Calculate symbol search subtest score: correct - (wrong * 0.5), min 0.
     *
     * @param array<Answer> $answers
     * @param array<Question> $questions
     */
    public function calculateSymbolSearchScore(array $answers, array $questions): Score
    {
        $correct = 0;
        $wrong = 0;
        $questionMap = [];

        foreach ($questions as $question) {
            $questionMap[$question->getId()->getValue()] = $question;
        }

        foreach ($answers as $answer) {
            $question = $questionMap[$answer->getQuestionId()->getValue()] ?? null;

            if ($question === null) {
                continue;
            }

            // 未回答（空の回答）はスキップ
            $response = trim($answer->getResponse());
            if ($response === '') {
                continue;
            }

            $isCorrect = strtolower($response) === strtolower(trim($question->getCorrectAnswer()));

            if ($isCorrect) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        $raw = $correct - ($wrong * 0.5);
        $final = max(0.0, $raw);

        return new Score($final);
    }

    /**
     * Calculate total score for a subtest from its answers.
     *
     * @param array<Answer> $answers
     * @param array<Question> $questions
     */
    public function calculateSubtestScore(SubtestType $subtestType, array $answers, array $questions): Score
    {
        if ($subtestType === SubtestType::SYMBOL_SEARCH) {
            return $this->calculateSymbolSearchScore($answers, $questions);
        }

        $total = Score::zero();

        foreach ($answers as $answer) {
            $total = $total->add($answer->getAwardedScore());
        }

        return $total;
    }

    /**
     * Calculate index score from subtest scores.
     *
     * @param array<string, Score> $subtestScores keyed by SubtestType value
     */
    public function calculateIndexScore(IndexType $indexType, array $subtestScores): Score
    {
        $subtests = array_filter(
            SubtestType::cases(),
            fn (SubtestType $s) => $s->indexType() === $indexType
        );

        $total = Score::zero();

        foreach ($subtests as $subtest) {
            $score = $subtestScores[$subtest->value] ?? Score::zero();
            $total = $total->add($score);
        }

        return $total;
    }

    /**
     * Calculate pseudo IQ score from percentage.
     * Uses standard IQ distribution: mean=100, SD=15
     * Maps 0-100% to approximately 55-145 IQ range
     */
    public function calculatePseudoIQ(float $percentage): int
    {
        // パーセンテージを0-1の範囲に正規化
        $normalized = $percentage / 100.0;

        // 正規分布の逆関数を使用してz-scoreを計算
        // 簡易的な変換: percentage 50% = IQ 100, 各標準偏差ごとに約16.67%
        // 0% ≈ IQ 55, 50% = IQ 100, 100% ≈ IQ 145

        if ($normalized <= 0.0) {
            return 55;
        }
        if ($normalized >= 1.0) {
            return 145;
        }

        // 線形変換ではなく、より現実的な分布を使用
        // 50%を基準に、上下それぞれ3標準偏差の範囲をカバー
        $zScore = $this->percentileToZScore($normalized);
        $iq = 100 + ($zScore * 15);

        // IQの範囲を制限
        return (int) round(max(55, min(145, $iq)));
    }

    /**
     * Convert percentile (0-1) to z-score using approximation
     */
    private function percentileToZScore(float $percentile): float
    {
        // 0-1の範囲を-3から+3のz-scoreに変換
        // 簡易的な線形近似（より正確には逆正規分布関数を使用すべきだが、簡略化のため）

        // パーセンタイルを標準正規分布のz-scoreに変換
        // 16% ≈ -1SD, 50% = 0SD, 84% ≈ +1SD
        if ($percentile <= 0.02) return -3.0;
        if ($percentile <= 0.16) return -1.0 + ($percentile - 0.02) / 0.14 * (-2.0);
        if ($percentile <= 0.50) return -1.0 + ($percentile - 0.16) / 0.34;
        if ($percentile <= 0.84) return 0.0 + ($percentile - 0.50) / 0.34;
        if ($percentile <= 0.98) return 1.0 + ($percentile - 0.84) / 0.14 * 2.0;
        return 3.0;
    }

    public function interpretIQ(int $iq): string
    {
        return match (true) {
            $iq >= 130  => '非常に高い（Very Superior）',
            $iq >= 120  => '高い（Superior）',
            $iq >= 110  => '平均の上（High Average）',
            $iq >= 90   => '平均（Average）',
            $iq >= 80   => '平均の下（Low Average）',
            $iq >= 70   => '境界域（Borderline）',
            default     => '低い（Extremely Low）',
        };
    }

    public function percentageLevel(float $percentage): string
    {
        return match (true) {
            $percentage <= 20  => 'かなり弱い',
            $percentage <= 40  => '弱め',
            $percentage <= 60  => '平均域',
            $percentage <= 80  => '強め',
            default            => 'かなり強い',
        };
    }
}
