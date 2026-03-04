<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Assessment\Entities\Assessment;
use App\Domain\Assessment\ValueObjects\AssessmentStatus;
use App\Domain\Assessment\ValueObjects\SubtestType;
use DomainException;
use PHPUnit\Framework\TestCase;

final class AssessmentTest extends TestCase
{
    public function test_start_creates_assessment_with_in_progress_status(): void
    {
        $assessment = Assessment::start();

        $this->assertSame(AssessmentStatus::IN_PROGRESS, $assessment->getStatus());
    }

    public function test_start_creates_assessment_with_no_completed_subtests(): void
    {
        $assessment = Assessment::start();

        $this->assertEmpty($assessment->getCompletedSubtests());
    }

    public function test_mark_subtest_completed(): void
    {
        $assessment = Assessment::start();

        $assessment->markSubtestCompleted(SubtestType::SIMILARITIES);

        $this->assertTrue($assessment->isSubtestCompleted(SubtestType::SIMILARITIES));
    }

    public function test_mark_same_subtest_twice_does_not_duplicate(): void
    {
        $assessment = Assessment::start();

        $assessment->markSubtestCompleted(SubtestType::SIMILARITIES);
        $assessment->markSubtestCompleted(SubtestType::SIMILARITIES);

        $this->assertCount(1, $assessment->getCompletedSubtests());
    }

    public function test_not_completed_subtest_returns_false(): void
    {
        $assessment = Assessment::start();

        $this->assertFalse($assessment->isSubtestCompleted(SubtestType::VOCABULARY));
    }

    public function test_are_all_subtests_completed_returns_false_when_partial(): void
    {
        $assessment = Assessment::start();
        $assessment->markSubtestCompleted(SubtestType::SIMILARITIES);

        $this->assertFalse($assessment->areAllSubtestsCompleted());
    }

    public function test_are_all_subtests_completed_returns_true_when_all_done(): void
    {
        $assessment = Assessment::start();

        foreach (SubtestType::orderedList() as $subtestType) {
            $assessment->markSubtestCompleted($subtestType);
        }

        $this->assertTrue($assessment->areAllSubtestsCompleted());
    }

    public function test_complete_changes_status_to_completed(): void
    {
        $assessment = Assessment::start();
        $assessment->complete();

        $this->assertSame(AssessmentStatus::COMPLETED, $assessment->getStatus());
    }

    public function test_complete_sets_completed_at(): void
    {
        $assessment = Assessment::start();
        $assessment->complete();

        $this->assertNotNull($assessment->getCompletedAt());
    }

    public function test_cannot_complete_already_completed_assessment(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Assessment is already completed.');

        $assessment = Assessment::start();
        $assessment->complete();
        $assessment->complete();
    }

    public function test_cannot_mark_subtest_on_completed_assessment(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot modify a completed assessment.');

        $assessment = Assessment::start();
        $assessment->complete();
        $assessment->markSubtestCompleted(SubtestType::SIMILARITIES);
    }
}
