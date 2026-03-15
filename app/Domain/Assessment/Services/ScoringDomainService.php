<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Services;

use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Entities\Question;
use App\Domain\Assessment\ValueObjects\IndexType;
use App\Domain\Assessment\ValueObjects\QuestionType;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;
use LogicException;

final class ScoringDomainService
{
    /**
     * Grade a single answer against its question.
     * Only for MULTIPLE_CHOICE, SEQUENCE, and FREE_TEXT (auto-grading).
     * TIME_BASED questions use user-provided awarded_score and should not call this method.
     */
    public function gradeAnswer(Question $question, Answer $answer): Score
    {
        return match ($question->getQuestionType()) {
            QuestionType::FREE_TEXT                                        => $this->gradeFreeText($question, $answer),
            QuestionType::MULTIPLE_CHOICE, QuestionType::SEQUENCE,
            QuestionType::TIME_BASED                                       => $this->gradeExact($question, $answer),
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
     * Calculate total score for a subtest by summing awarded_score of each answer.
     * Scores are already persisted in the DB — no re-grading is performed here.
     *
     * @param array<Answer> $answers
     */
    public function calculateSubtestScore(array $answers): Score
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
     *
     * 難易度補正あり: このテストで一般ユーザーが平均的に取る正答率を
     * DIFFICULTY_MEAN で定義し、その正答率が IQ100（50パーセンタイル）に
     * なるよう線形シフトしてから z-score に変換する。
     *
     * DIFFICULTY_MEAN = 0.65 の場合の目安:
     *   0/6 (  0%) → IQ  55（下限）
     *   2/6 ( 33%) → IQ  89
     *   3/6 ( 50%) → IQ  95
     *   4/6 ( 67%) → IQ 101
     *   5/6 ( 83%) → IQ 112
     *   6/6 (100%) → IQ 125（上限）
     *
     * DIFFICULTY_MEAN を上げる（例: 0.75）ほど全体的に IQ が下がる。
     * 出力範囲: 55〜125
     */
    private const DIFFICULTY_MEAN = 0.65;

    public function calculatePseudoIQ(float $percentage): int
    {
        $normalized = $percentage / 100.0;

        if ($normalized <= 0.0) {
            return 55;
        }
        if ($normalized >= 1.0) {
            return 125;
        }

        // 難易度補正: DIFFICULTY_MEAN の正答率が 50パーセンタイル（IQ100）になるよう調整
        $mean = self::DIFFICULTY_MEAN;
        if ($normalized <= $mean) {
            $adjusted = $normalized / (2.0 * $mean);
        } else {
            $adjusted = 0.5 + ($normalized - $mean) / (2.0 * (1.0 - $mean));
        }

        $zScore = $this->percentileToZScore(max(0.0, min(1.0, $adjusted)));

        // 標準IQ: 平均100・SD15
        $iq = 100 + ($zScore * 15);

        return (int) round(max(55, min(125, $iq)));
    }

    /**
     * Convert percentile (0-1) to z-score using piecewise linear approximation.
     * Breakpoints: 2%≈-3SD, 16%≈-1SD, 50%=0SD, 84%≈+1SD, 98%≈+3SD
     */
    private function percentileToZScore(float $percentile): float
    {
        if ($percentile <= 0.02) {
            return -3.0;
        }
        if ($percentile <= 0.16) {
            // -3.0 at 2% → -1.0 at 16%
            return -3.0 + ($percentile - 0.02) / 0.14 * 2.0;
        }
        if ($percentile <= 0.50) {
            // -1.0 at 16% → 0.0 at 50%
            return -1.0 + ($percentile - 0.16) / 0.34;
        }
        if ($percentile <= 0.84) {
            // 0.0 at 50% → +1.0 at 84%
            return ($percentile - 0.50) / 0.34;
        }
        if ($percentile <= 0.98) {
            // +1.0 at 84% → +3.0 at 98%
            return 1.0 + ($percentile - 0.84) / 0.14 * 2.0;
        }
        return 3.0;
    }

    /**
     * IQ水準の文字解釈（出力範囲 55〜125 に合わせた5段階）
     */
    public function interpretIQ(int $iq): string
    {
        return match (true) {
            $iq >= 120  => '高い（Superior）',
            $iq >= 110  => '平均の上（High Average）',
            $iq >= 90   => '平均（Average）',
            $iq >= 75   => '平均の下（Low Average）',
            default     => '低め（Borderline）',
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
