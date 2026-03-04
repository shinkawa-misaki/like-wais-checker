<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\SubmitSubtestAnswers;

use App\Application\Assessment\DTOs\AnswerInputDto;

final class SubmitSubtestAnswersInput
{
    /**
     * @param array<AnswerInputDto> $answers
     */
    public function __construct(
        public readonly string $assessmentId,
        public readonly string $subtestType,
        public readonly array $answers,
        public readonly ?int $elapsedSeconds = null, // For PSI subtests
    ) {
    }
}
