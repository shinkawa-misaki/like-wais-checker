<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Assessment\ValueObjects\Score;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ScoreTest extends TestCase
{
    public function test_zero_score_is_created(): void
    {
        $score = Score::zero();

        $this->assertSame(0.0, $score->getValue());
    }

    public function test_score_with_positive_value(): void
    {
        $score = new Score(2.0);

        $this->assertSame(2.0, $score->getValue());
    }

    public function test_negative_score_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Score cannot be negative.');

        new Score(-1.0);
    }

    public function test_add_scores(): void
    {
        $score1 = new Score(3.0);
        $score2 = new Score(2.0);

        $result = $score1->add($score2);

        $this->assertSame(5.0, $result->getValue());
    }

    public function test_to_percentage(): void
    {
        $score = new Score(20.0);

        $percentage = $score->toPercentage(40);

        $this->assertSame(50.0, $percentage);
    }

    public function test_to_percentage_with_zero_max_returns_zero(): void
    {
        $score = new Score(10.0);

        $percentage = $score->toPercentage(0);

        $this->assertSame(0.0, $percentage);
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $score1 = new Score(5.0);
        $score2 = new Score(5.0);

        $this->assertTrue($score1->equals($score2));
    }

    public function test_equals_returns_false_for_different_values(): void
    {
        $score1 = new Score(5.0);
        $score2 = new Score(3.0);

        $this->assertFalse($score1->equals($score2));
    }
}
