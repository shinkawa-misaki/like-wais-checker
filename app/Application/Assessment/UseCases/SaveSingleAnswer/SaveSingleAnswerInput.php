<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\SaveSingleAnswer;

final class SaveSingleAnswerInput
{
    public function __construct(
        public readonly string $assessmentId,
        public readonly string $subtestType,
        public readonly string $questionId,
        public readonly string $response,
        public readonly ?float $awardedScore = null,
    ) {
    }
}
