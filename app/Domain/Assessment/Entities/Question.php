<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Entities;

use App\Domain\Assessment\ValueObjects\QuestionId;
use App\Domain\Assessment\ValueObjects\QuestionType;
use App\Domain\Assessment\ValueObjects\SubtestType;

final class Question
{
    /**
     * @param array<string>|null $options
     */
    public function __construct(
        private readonly QuestionId $id,
        private readonly SubtestType $subtestType,
        private readonly int $sequenceNumber,
        private readonly string $content,
        private readonly QuestionType $questionType,
        private readonly string $correctAnswer,
        private readonly ?array $options = null,
        private readonly int $maxPoints = 1,
        private readonly ?string $hint = null,
    ) {
    }

    public function getId(): QuestionId
    {
        return $this->id;
    }

    public function getSubtestType(): SubtestType
    {
        return $this->subtestType;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getQuestionType(): QuestionType
    {
        return $this->questionType;
    }

    public function getCorrectAnswer(): string
    {
        return $this->correctAnswer;
    }

    /** @return array<string>|null */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function getMaxPoints(): int
    {
        return $this->maxPoints;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function isMultipleChoice(): bool
    {
        return $this->questionType === QuestionType::MULTIPLE_CHOICE;
    }

    public function isFreeText(): bool
    {
        return $this->questionType === QuestionType::FREE_TEXT;
    }
}
