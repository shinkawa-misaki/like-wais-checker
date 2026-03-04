<?php

declare(strict_types=1);

namespace App\Application\Assessment\DTOs;

final class AnswerInputDto
{
    public function __construct(
        public readonly string $questionId,
        public readonly string $response,
        public readonly ?float $awardedScore = null, // For FREE_TEXT questions (human grading)
    ) {
    }
}
