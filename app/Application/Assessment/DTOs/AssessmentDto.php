<?php

declare(strict_types=1);

namespace App\Application\Assessment\DTOs;

use App\Domain\Assessment\Entities\Assessment;

final class AssessmentDto
{
    /**
     * @param array<string> $completedSubtests
     * @param array<string> $remainingSubtests
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly array $completedSubtests,
        public readonly array $remainingSubtests,
        public readonly string $createdAt,
        public readonly ?string $completedAt,
    ) {
    }

    public static function fromEntity(Assessment $assessment): self
    {
        $allSubtests = \App\Domain\Assessment\ValueObjects\SubtestType::orderedList();
        $completed = $assessment->getCompletedSubtests();

        $remaining = array_values(array_filter(
            $allSubtests,
            fn ($s) => !in_array($s->value, $completed, true)
        ));

        return new self(
            id: $assessment->getId()->getValue(),
            status: $assessment->getStatus()->value,
            statusLabel: $assessment->getStatus()->label(),
            completedSubtests: $completed,
            remainingSubtests: array_map(fn ($s) => $s->value, $remaining),
            createdAt: $assessment->getCreatedAt()->format('Y-m-d H:i:s'),
            completedAt: $assessment->getCompletedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
