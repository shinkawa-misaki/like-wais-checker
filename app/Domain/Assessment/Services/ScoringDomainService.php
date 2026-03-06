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
     * hint / correct_answer のキーワードとの部分一致で概念マッチを判定し、
     * hint テキストとの文字列類似度で2点 vs 1点を判定する。
     *
     * - 2点: キーワードにマッチ かつ hint との類似度が閾値以上
     * - 1点: キーワードにマッチ かつ hint との類似度が閾値未満
     * - 0点: キーワードにマッチしない
     */
    private function gradeSimilarityAnswer(Question $question, string $response): Score
    {
        if (!$this->matchesHintConcept($question, $response)) {
            return Score::zero();
        }

        return $this->isSimilarToHint($question, $response)
            ? new Score(2.0)
            : new Score(1.0);
    }

    /**
     * 回答が hint テキストと十分に類似しているか判定する
     *
     * similar_text() で文字列の共通部分を計算し、
     * hint + response の合計長に対する割合が閾値（40%）以上なら類似とみなす。
     */
    private function isSimilarToHint(Question $question, string $response): bool
    {
        $hint = $question->getHint();
        if ($hint === null || $hint === '') {
            return false;
        }

        similar_text($hint, $response, $percent);

        return $percent >= 40.0;
    }

    /**
     * hint / correct_answer から抽出したキーワードと部分一致するか判定
     * 双方向チェック: response→kw / kw→response
     */
    private function matchesHintConcept(Question $question, string $response): bool
    {
        foreach ($this->extractConceptKeywords($question) as $kw) {
            if (str_contains($response, $kw) || str_contains($kw, $response)) {
                return true;
            }
        }

        return false;
    }

    /**
     * hint と correct_answer を助詞・区切り文字で分割してキーワード配列を返す
     *
     * @return array<string>
     */
    private function extractConceptKeywords(Question $question): array
    {
        $sources = array_filter([
            $question->getHint(),
            $question->getCorrectAnswer(),
        ]);

        $keywords = [];

        foreach ($sources as $source) {
            $parts = preg_split('/[・、。／\/\s　のやとをにへ]+/u', $source) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if (mb_strlen($part) >= 2) {
                    $keywords[] = $part;
                }
            }
        }

        return array_unique($keywords);
    }

    /**
     * 語彙問題の採点
     *
     * 類似問題と同じ方針:
     * - 2点: hint のキーワードにマッチ かつ hint との類似度が閾値以上
     * - 1点: hint のキーワードにマッチ かつ hint との類似度が閾値未満
     * - 0点: hint のキーワードにマッチしない
     */
    private function gradeVocabularyAnswer(Question $question, string $response): Score
    {
        if (!$this->matchesHintConcept($question, $response)) {
            return Score::zero();
        }

        return $this->isSimilarToHint($question, $response)
            ? new Score(2.0)
            : new Score(1.0);
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
