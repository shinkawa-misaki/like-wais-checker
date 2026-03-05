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
     * 0点: 空または極端に短い回答
     * 1点: 基本的な回答はあるが不十分
     * 2点: 適切で十分な回答
     */
    private function gradeFreeText(Question $question, Answer $answer): Score
    {
        $response = trim($answer->getResponse());

        // 空の回答は0点
        if ($response === '') {
            return Score::zero();
        }

        // 類似問題のキーワード評価（長さチェックの前に実施）
        if ($this->isSimilarityQuestion($question)) {
            return $this->gradeSimilarityAnswer($response, mb_strlen($response));
        }

        // 極端に短い回答（5文字未満）は0点
        $length = mb_strlen($response);
        if ($length < 5) {
            return Score::zero();
        }

        // ヒントからキーワードを抽出（簡易的な実装）
        $hint = $question->getHint() ?? '';


        // 語彙問題のキーワード評価
        if ($this->isVocabularyQuestion($question)) {
            return $this->gradeVocabularyAnswer($response, $length, $hint);
        }

        // その他の自由記述は長さベースで評価
        return $this->gradeByLength($length);
    }

    /**
     * 類似問題かどうかを判定
     */
    private function isSimilarityQuestion(Question $question): bool
    {
        $content = $question->getContent();
        return str_contains($content, '共通点') || str_contains($content, 'の共通');
    }

    /**
     * 語彙問題かどうかを判定
     */
    private function isVocabularyQuestion(Question $question): bool
    {
        $content = $question->getContent();
        return str_contains($content, '意味を説明') || str_contains($content, '言葉の意味');
    }

    /**
     * 類似問題の採点
     */
    private function gradeSimilarityAnswer(string $response, int $length): Score
    {
        // キーワードポイント
        $points = 0;

        // 抽象的なカテゴリー表現があれば高得点
        $highLevelKeywords = [
            '生物', '哺乳類', '動物', '食べ物', '果物', '植物', '道具', '家具',
            '文房具', '乗り物', '交通手段', '媒体', 'メディア', '建築物',
            '天体', '自然', '季節', '時間', '期間', '職業', '記念', '行事',
            'スポーツ', '文学', '芸術', '感情', '要素', '概念', '状態',
            'システム', 'インフラ', '現象', '手段', '方法', '表現'
        ];

        $foundHighLevel = false;
        foreach ($highLevelKeywords as $keyword) {
            if (str_contains($response, $keyword)) {
                $foundHighLevel = true;
                break;
            }
        }

        // 抽象的表現がある場合
        if ($foundHighLevel) {
            $points = 2;
        }
        // 「どちらも」「両方」などの接続語がある場合
        else if (preg_match('/(どちらも|両方|共に|いずれも)/', $response)) {
            $points = $length >= 15 ? 2 : 1;
        }
        // 長さで判定
        else {
            $points = $length >= 20 ? 1 : 0;
        }

        return new Score((float) $points);
    }

    /**
     * 語彙問題の採点
     */
    private function gradeVocabularyAnswer(string $response, int $length, string $hint): Score
    {
        // ヒントのキーワードが含まれているかチェック
        $hintKeywords = preg_split('/[、。\s]+/u', $hint);
        $keywordMatches = 0;

        foreach ($hintKeywords as $keyword) {
            $keyword = trim($keyword);
            if (strlen($keyword) >= 2 && str_contains($response, $keyword)) {
                $keywordMatches++;
            }
        }

        // キーワードマッチが多い場合は高得点
        if ($keywordMatches >= 2) {
            return new Score(2.0);
        } else if ($keywordMatches >= 1) {
            return new Score($length >= 15 ? 2.0 : 1.0);
        }

        // キーワードなしでも十分な長さと説明があれば点数を与える
        if ($length >= 20 && $this->hasExplanationStructure($response)) {
            return new Score(1.0);
        } else if ($length >= 10) {
            return new Score(1.0);
        }

        return Score::zero();
    }

    /**
     * 説明的な構造を持っているかチェック
     */
    private function hasExplanationStructure(string $response): bool
    {
        // 「〜こと」「〜である」「〜する」などの説明的表現
        $patterns = [
            '/こと$/',
            '/である$/',
            '/です$/',
            '/する(心|気持ち|様子|状態)/',
            '/という/',
            '/ような/',
            '/という意味/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response)) {
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
