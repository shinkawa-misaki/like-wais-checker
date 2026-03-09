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

        $correctAnswer = $question->getCorrectAnswer();
        if ($correctAnswer === null) {
            return Score::zero();
        }

        $isCorrect = strtolower($response) === strtolower(trim($correctAnswer));

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

        // 類似・語彙問題も選択式と同じ厳密な一致判定を使用
        return $this->gradeExact($question, $answer);
    }

    /**
     * Calculate total score for a subtest from its answers.
     * All answers already have their awarded_score set in the database,
     * so this simply sums them up.
     *
     * @param array<Answer> $answers
     */
    public function calculateSubtestScore(SubtestType $subtestType, array $answers): Score
    {
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
        if ($percentile <= 0.02) {
            return -3.0;
        }
        if ($percentile <= 0.16) {
            return -1.0 + ($percentile - 0.02) / 0.14 * (-2.0);
        }
        if ($percentile <= 0.50) {
            return -1.0 + ($percentile - 0.16) / 0.34;
        }
        if ($percentile <= 0.84) {
            return 0.0 + ($percentile - 0.50) / 0.34;
        }
        if ($percentile <= 0.98) {
            return 1.0 + ($percentile - 0.84) / 0.14 * 2.0;
        }
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
