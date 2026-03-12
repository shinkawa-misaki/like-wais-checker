<?php

declare(strict_types=1);

namespace App\Application\Assessment\DTOs;

final class ReportDto
{
    /**
     * @param array<IndexScoreDto> $indexScores
     * @param array<IndexScoreDto> $strengthIndices
     * @param array<IndexScoreDto> $weaknessIndices
     * @param array<string, string> $strategies
     * @param array<string> $nextSteps
     */
    public function __construct(
        public readonly string $assessmentId,
        public readonly string $disclaimer,
        public readonly array $indexScores,
        public readonly array $strengthIndices,
        public readonly array $weaknessIndices,
        public readonly array $strategies,
        public readonly array $nextSteps,
        public readonly ?string $aiAdvice,
        public readonly string $generatedAt,
    ) {
    }
}
