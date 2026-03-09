<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Entities;

use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\QuestionId;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;

final class Answer
{
    public function __construct(
        private readonly QuestionId $questionId,
        private readonly AssessmentId $assessmentId,
        private readonly SubtestType $subtestType,
        private readonly string $response,
        private Score $awardedScore,
    ) {
    }

    public function getQuestionId(): QuestionId
    {
        return $this->questionId;
    }

    public function getAssessmentId(): AssessmentId
    {
        return $this->assessmentId;
    }

    public function getSubtestType(): SubtestType
    {
        return $this->subtestType;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getAwardedScore(): Score
    {
        return $this->awardedScore;
    }

    public function updateScore(Score $score): void
    {
        $this->awardedScore = $score;
    }
}
