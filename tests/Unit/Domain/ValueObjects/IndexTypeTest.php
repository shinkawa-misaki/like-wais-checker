<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Assessment\ValueObjects\IndexType;
use PHPUnit\Framework\TestCase;

final class IndexTypeTest extends TestCase
{
    public function test_vci_max_score_is_40(): void
    {
        $this->assertSame(40, IndexType::VCI->maxScore());
    }

    public function test_pri_max_score_is_22(): void
    {
        $this->assertSame(22, IndexType::PRI->maxScore());
    }

    public function test_wmi_max_score_is_28(): void
    {
        $this->assertSame(28, IndexType::WMI->maxScore());
    }

    public function test_psi_max_score_is_84(): void
    {
        $this->assertSame(84, IndexType::PSI->maxScore());
    }

    public function test_labels_are_in_japanese(): void
    {
        $this->assertSame('言語理解 (VCI)', IndexType::VCI->label());
        $this->assertSame('知覚推理 (PRI)', IndexType::PRI->label());
        $this->assertSame('ワーキングメモリー (WMI)', IndexType::WMI->label());
        $this->assertSame('処理速度 (PSI)', IndexType::PSI->label());
    }

    public function test_strength_description_is_not_empty(): void
    {
        foreach (IndexType::cases() as $indexType) {
            $this->assertNotEmpty($indexType->strengthDescription());
        }
    }

    public function test_weakness_description_is_not_empty(): void
    {
        foreach (IndexType::cases() as $indexType) {
            $this->assertNotEmpty($indexType->weaknessDescription());
        }
    }
}
