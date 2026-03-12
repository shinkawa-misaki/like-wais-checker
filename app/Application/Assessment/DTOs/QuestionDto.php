<?php

declare(strict_types=1);

namespace App\Application\Assessment\DTOs;

use App\Domain\Assessment\Entities\Question;

final class QuestionDto
{
    /**
     * @param array<string>|null $options
     */
    public function __construct(
        public readonly string $id,
        public readonly string $subtestType,
        public readonly string $subtestLabel,
        public readonly int $sequenceNumber,
        public readonly string $content,
        public readonly string $questionType,
        public readonly ?array $options,
        public readonly int $maxPoints,
        public readonly ?string $hint,
    ) {
    }

    public static function fromEntity(Question $question): self
    {
        return new self(
            id: $question->getId()->getValue(),
            subtestType: $question->getSubtestType()->value,
            subtestLabel: $question->getSubtestType()->label(),
            sequenceNumber: $question->getSequenceNumber(),
            content: $question->getContent(),
            questionType: $question->getQuestionType()->value,
            options: $question->getOptions(),
            maxPoints: $question->getMaxPoints(),
            hint: $question->getHint(),
        );
    }
}
