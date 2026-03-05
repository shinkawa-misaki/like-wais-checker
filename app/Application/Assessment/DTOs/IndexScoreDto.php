<?php

declare(strict_types=1);

namespace App\Application\Assessment\DTOs;

final class IndexScoreDto
{
    public function __construct(
        public readonly string $indexType,
        public readonly string $label,
        public readonly float $rawScore,
        public readonly int $maxScore,
        public readonly float $percentage,
        public readonly string $level,
        public readonly int $pseudoIQ,
        public readonly string $iqInterpretation,
        public readonly string $interpretation,
    ) {
    }
}
