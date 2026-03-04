<?php

declare(strict_types=1);

namespace App\Domain\Assessment\ValueObjects;

enum AssessmentStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED   = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS => '進行中',
            self::COMPLETED   => '完了',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }
}
