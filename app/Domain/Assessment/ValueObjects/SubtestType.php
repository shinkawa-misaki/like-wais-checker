<?php

declare(strict_types=1);

namespace App\Domain\Assessment\ValueObjects;

enum SubtestType: string
{
    case SIMILARITIES        = 'A'; // 類似 (VCI)
    case VOCABULARY          = 'B'; // 語彙 (VCI)
    case PATTERN_RECOGNITION = 'C'; // 規則発見 (PRI)
    case MATRIX_REASONING    = 'D'; // 簡易マトリクス (PRI)
    case DIGIT_SPAN          = 'E'; // 数唱 (WMI)
    case ARITHMETIC          = 'F'; // 暗算 (WMI)
    case SYMBOL_SEARCH       = 'G'; // 探索 (PSI)
    case CODING              = 'H'; // 符号化 (PSI)

    public function label(): string
    {
        return match ($this) {
            self::SIMILARITIES        => '類似 (Similarities)',
            self::VOCABULARY          => '語彙 (Vocabulary)',
            self::PATTERN_RECOGNITION => '規則発見 (Pattern Recognition)',
            self::MATRIX_REASONING    => '簡易マトリクス (Matrix Reasoning)',
            self::DIGIT_SPAN          => '数唱 (Digit Span)',
            self::ARITHMETIC          => '暗算 (Arithmetic)',
            self::SYMBOL_SEARCH       => '探索 (Symbol Search)',
            self::CODING              => '符号化 (Coding)',
        };
    }

    public function indexType(): IndexType
    {
        return match ($this) {
            self::SIMILARITIES, self::VOCABULARY          => IndexType::VCI,
            self::PATTERN_RECOGNITION, self::MATRIX_REASONING => IndexType::PRI,
            self::DIGIT_SPAN, self::ARITHMETIC            => IndexType::WMI,
            self::SYMBOL_SEARCH, self::CODING             => IndexType::PSI,
        };
    }

    public function questionCount(): int
    {
        return match ($this) {
            self::SIMILARITIES        => 10,
            self::VOCABULARY          => 10,
            self::PATTERN_RECOGNITION => 12,
            self::MATRIX_REASONING    => 10,
            self::DIGIT_SPAN          => 18,
            self::ARITHMETIC          => 10,
            self::SYMBOL_SEARCH       => 24,
            self::CODING              => 60,
        };
    }

    public function maxScore(): int
    {
        return match ($this) {
            self::SIMILARITIES        => 20, // 2点 × 10問
            self::VOCABULARY          => 20, // 2点 × 10問
            self::PATTERN_RECOGNITION => 12, // 1点 × 12問
            self::MATRIX_REASONING    => 10, // 1点 × 10問
            self::DIGIT_SPAN          => 18, // 1点 × 18系列
            self::ARITHMETIC          => 10, // 1点 × 10問
            self::SYMBOL_SEARCH       => 24, // 正答数 - (誤答×0.5)
            self::CODING              => 60, // 正答数
        };
    }

    public function timeLimitSeconds(): ?int
    {
        return match ($this) {
            self::SYMBOL_SEARCH => 90,
            self::CODING        => 90,
            default             => null,
        };
    }

    public function isTimeBased(): bool
    {
        return $this->timeLimitSeconds() !== null;
    }

    public function questionType(): QuestionType
    {
        return match ($this) {
            self::SIMILARITIES, self::VOCABULARY => QuestionType::FREE_TEXT,
            self::PATTERN_RECOGNITION, self::MATRIX_REASONING, self::ARITHMETIC => QuestionType::MULTIPLE_CHOICE,
            self::DIGIT_SPAN   => QuestionType::SEQUENCE,
            self::SYMBOL_SEARCH, self::CODING => QuestionType::TIME_BASED,
        };
    }

    /** @return array<self> */
    public static function orderedList(): array
    {
        return [
            self::SIMILARITIES,
            self::VOCABULARY,
            self::PATTERN_RECOGNITION,
            self::MATRIX_REASONING,
            self::DIGIT_SPAN,
            self::ARITHMETIC,
            self::SYMBOL_SEARCH,
            self::CODING,
        ];
    }
}
