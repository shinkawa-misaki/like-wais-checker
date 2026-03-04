<?php

declare(strict_types=1);

namespace App\Domain\Assessment\ValueObjects;

use InvalidArgumentException;

final class QuestionId
{
    public function __construct(
        private readonly string $value,
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('QuestionId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self((string) \Illuminate\Support\Str::uuid());
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
