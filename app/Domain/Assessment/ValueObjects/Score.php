<?php

declare(strict_types=1);

namespace App\Domain\Assessment\ValueObjects;

use InvalidArgumentException;

final class Score
{
    public function __construct(
        private readonly float $value,
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException('Score cannot be negative.');
        }
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function toPercentage(int $maxScore): float
    {
        if ($maxScore === 0) {
            return 0.0;
        }

        return round(($this->value / $maxScore) * 100, 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
