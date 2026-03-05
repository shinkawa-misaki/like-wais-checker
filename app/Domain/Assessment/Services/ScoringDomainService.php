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
     * For FREE_TEXT questions, the awardedScore must be set externally (human grading).
     * For MULTIPLE_CHOICE and SEQUENCE questions, auto-grading is applied.
     * For TIME_BASED questions (Symbol Search), penalty scoring is applied.
     */
    public function gradeAnswer(Question $question, Answer $answer): Score
    {
        return match ($question->getQuestionType()) {
            \App\Domain\Assessment\ValueObjects\QuestionType::FREE_TEXT       => $answer->getAwardedScore(),
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
