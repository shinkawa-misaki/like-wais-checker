<?php

declare(strict_types=1);

namespace App\Domain\Assessment\ValueObjects;

/**
 * 認知の手すりチェック Lite — 4資源モデル
 *
 * A: 言語整理（旧 VCI）  6問 最大12点 (0/1/2)
 * B: 構造理解（旧 PRI）  6問 最大 6点 (0/1)
 * C: 保持操作（旧 WMI）  6問 最大 6点 (0/1)
 * D: 速度耐性（旧 PSI）  6問 最大 6点 (speed_score)
 */
enum SubtestType: string
{
    case VERBAL_ORGANIZATION        = 'A'; // 言語整理
    case STRUCTURAL_UNDERSTANDING   = 'B'; // 構造理解
    case RETENTION_MANIPULATION     = 'C'; // 保持操作
    case SPEED_RESILIENCE           = 'D'; // 速度耐性

    public function label(): string
    {
        return match ($this) {
            self::VERBAL_ORGANIZATION      => '言語整理',
            self::STRUCTURAL_UNDERSTANDING => '構造理解',
            self::RETENTION_MANIPULATION   => '保持操作',
            self::SPEED_RESILIENCE         => '速度耐性',
        };
    }

    public function indexType(): IndexType
    {
        return match ($this) {
            self::VERBAL_ORGANIZATION      => IndexType::VCI,
            self::STRUCTURAL_UNDERSTANDING => IndexType::PRI,
            self::RETENTION_MANIPULATION   => IndexType::WMI,
            self::SPEED_RESILIENCE         => IndexType::PSI,
        };
    }

    /** 1回のテストで出題する問題数 */
    public function questionCount(): int
    {
        return 6;
    }

    public function maxScore(): int
    {
        return match ($this) {
            self::VERBAL_ORGANIZATION => 12, // 6問 × 2点
            default                   => 6,  // 6問 × 1点
        };
    }

    public function timeLimitSeconds(): ?int
    {
        return match ($this) {
            self::SPEED_RESILIENCE => 60,
            default                => null,
        };
    }

    public function isTimeBased(): bool
    {
        return $this->timeLimitSeconds() !== null;
    }

    public function questionType(): QuestionType
    {
        return match ($this) {
            self::VERBAL_ORGANIZATION      => QuestionType::FREE_TEXT,
            self::STRUCTURAL_UNDERSTANDING => QuestionType::MULTIPLE_CHOICE,
            self::RETENTION_MANIPULATION   => QuestionType::SEQUENCE,
            self::SPEED_RESILIENCE         => QuestionType::TIME_BASED,
        };
    }

    /** @return array<self> */
    public static function orderedList(): array
    {
        return [
            self::VERBAL_ORGANIZATION,
            self::STRUCTURAL_UNDERSTANDING,
            self::RETENTION_MANIPULATION,
            self::SPEED_RESILIENCE,
        ];
    }
}
