<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Assessment\ValueObjects\IndexType;
use App\Domain\Assessment\ValueObjects\SubtestType;
use PHPUnit\Framework\TestCase;

final class SubtestTypeTest extends TestCase
{
    public function test_similarities_belongs_to_vci(): void
    {
        $this->assertSame(IndexType::VCI, SubtestType::SIMILARITIES->indexType());
    }

    public function test_vocabulary_belongs_to_vci(): void
    {
        $this->assertSame(IndexType::VCI, SubtestType::VOCABULARY->indexType());
    }

    public function test_pattern_recognition_belongs_to_pri(): void
    {
        $this->assertSame(IndexType::PRI, SubtestType::PATTERN_RECOGNITION->indexType());
    }

    public function test_matrix_reasoning_belongs_to_pri(): void
    {
        $this->assertSame(IndexType::PRI, SubtestType::MATRIX_REASONING->indexType());
    }

    public function test_digit_span_belongs_to_wmi(): void
    {
        $this->assertSame(IndexType::WMI, SubtestType::DIGIT_SPAN->indexType());
    }

    public function test_arithmetic_belongs_to_wmi(): void
    {
        $this->assertSame(IndexType::WMI, SubtestType::ARITHMETIC->indexType());
    }

    public function test_symbol_search_belongs_to_psi(): void
    {
        $this->assertSame(IndexType::PSI, SubtestType::SYMBOL_SEARCH->indexType());
    }

    public function test_coding_belongs_to_psi(): void
    {
        $this->assertSame(IndexType::PSI, SubtestType::CODING->indexType());
    }

    public function test_symbol_search_has_time_limit(): void
    {
        $this->assertTrue(SubtestType::SYMBOL_SEARCH->isTimeBased());
        $this->assertSame(90, SubtestType::SYMBOL_SEARCH->timeLimitSeconds());
    }

    public function test_coding_has_time_limit(): void
    {
        $this->assertTrue(SubtestType::CODING->isTimeBased());
        $this->assertSame(90, SubtestType::CODING->timeLimitSeconds());
    }

    public function test_similarities_has_no_time_limit(): void
    {
        $this->assertFalse(SubtestType::SIMILARITIES->isTimeBased());
        $this->assertNull(SubtestType::SIMILARITIES->timeLimitSeconds());
    }

    public function test_ordered_list_returns_8_subtests(): void
    {
        $list = SubtestType::orderedList();

        $this->assertCount(8, $list);
    }

    public function test_ordered_list_starts_with_similarities(): void
    {
        $list = SubtestType::orderedList();

        $this->assertSame(SubtestType::SIMILARITIES, $list[0]);
    }

    public function test_ordered_list_ends_with_coding(): void
    {
        $list = SubtestType::orderedList();

        $this->assertSame(SubtestType::CODING, $list[7]);
    }

    public function test_similarities_max_score_is_20(): void
    {
        $this->assertSame(20, SubtestType::SIMILARITIES->maxScore());
    }

    public function test_vocabulary_max_score_is_20(): void
    {
        $this->assertSame(20, SubtestType::VOCABULARY->maxScore());
    }

    public function test_symbol_search_max_score_is_24(): void
    {
        $this->assertSame(24, SubtestType::SYMBOL_SEARCH->maxScore());
    }

    public function test_coding_max_score_is_60(): void
    {
        $this->assertSame(60, SubtestType::CODING->maxScore());
    }
}
