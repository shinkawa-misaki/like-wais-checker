<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Entities;

use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\AssessmentStatus;
use App\Domain\Assessment\ValueObjects\SubtestType;
use DateTimeImmutable;
use DomainException;

final class Assessment
{
    /** @var array<string> */
    private array $completedSubtests;

    /** @var array<Answer> */
    private array $answers;

    /**
     * @param array<string> $completedSubtests
     * @param array<Answer> $answers
     */
    public function __construct(
        private readonly AssessmentId $id,
        private AssessmentStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $completedAt = null,
        array $completedSubtests = [],
        array $answers = [],
    ) {
        $this->completedSubtests = $completedSubtests;
        $this->answers = $answers;
    }

    public static function start(): self
    {
        return new self(
            id: AssessmentId::generate(),
            status: AssessmentStatus::IN_PROGRESS,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function getId(): AssessmentId
    {
        return $this->id;
    }

    public function getStatus(): AssessmentStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    /** @return array<string> */
    public function getCompletedSubtests(): array
    {
        return $this->completedSubtests;
    }

    /** @return array<Answer> */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function isSubtestCompleted(SubtestType $subtestType): bool
    {
        return in_array($subtestType->value, $this->completedSubtests, true);
    }

    public function markSubtestCompleted(SubtestType $subtestType): void
    {
        if ($this->status->isCompleted()) {
            throw new DomainException('Cannot modify a completed assessment.');
        }

        if (!$this->isSubtestCompleted($subtestType)) {
            $this->completedSubtests[] = $subtestType->value;
        }
    }

    public function addAnswer(Answer $answer): void
    {
        if ($this->status->isCompleted()) {
            throw new DomainException('Cannot add answers to a completed assessment.');
        }

        $this->answers[] = $answer;
    }

    public function complete(): void
    {
        if ($this->status->isCompleted()) {
            throw new DomainException('Assessment is already completed.');
        }

        $this->status = AssessmentStatus::COMPLETED;
        $this->completedAt = new DateTimeImmutable();
    }

    public function areAllSubtestsCompleted(): bool
    {
        $allSubtests = SubtestType::orderedList();

        foreach ($allSubtests as $subtest) {
            if (!$this->isSubtestCompleted($subtest)) {
                return false;
            }
        }

        return true;
    }
}
