<?php

declare(strict_types=1);

namespace App\Domain\Assessment\ValueObjects;

enum IndexType: string
{
    case VCI = 'VCI'; // 言語理解
    case PRI = 'PRI'; // 知覚推理
    case WMI = 'WMI'; // ワーキングメモリー
    case PSI = 'PSI'; // 処理速度

    public function label(): string
    {
        return match ($this) {
            self::VCI => '言語理解 (VCI)',
            self::PRI => '知覚推理 (PRI)',
            self::WMI => 'ワーキングメモリー (WMI)',
            self::PSI => '処理速度 (PSI)',
        };
    }

    public function maxScore(): int
    {
        return match ($this) {
            self::VCI => 40, // A(20) + B(20)
            self::PRI => 22, // C(12) + D(10)
            self::WMI => 28, // E(18) + F(10)
            self::PSI => 84, // G(24) + H(60)
        };
    }

    public function strengthDescription(): string
    {
        return match ($this) {
            self::VCI => '言語で整理・抽象化・説明が得意。書く／話す／命名で思考が進む。手順書・仕様化が武器になる。',
            self::PRI => '規則発見・構造理解・枠組み作りが得意。先に型（フレーム）を置き、分類・テンプレ化で速くなる。',
            self::WMI => '保持しながら操作できる。同時並行に強いが過負荷には注意。',
            self::PSI => '単純作業の速度が出る・切替が速い。ルーチン処理や短時間集中と相性が良い。',
        };
    }

    public function weaknessDescription(): string
    {
        return match ($this) {
            self::VCI => '言語化にエネルギーが要る可能性。図解→短文→箇条書きの順で外在化し、最初から綺麗に言わない戦略が有効。',
            self::PRI => '情報が散りやすい・規則が見える前に疲れる可能性。条件を減らす／例を増やす／一軸ずつ検証する戦略が有効。',
            self::WMI => '保持容量が先に埋まって処理が落ちる。途中メモ・チェックリスト・分割（1タスク=1画面）が有効。',
            self::PSI => '焦ると崩れる・慎重さが勝つ可能性。速度勝負の役割から離し、締切は前倒し×小分けで守る戦略が有効。',
        };
    }
}
