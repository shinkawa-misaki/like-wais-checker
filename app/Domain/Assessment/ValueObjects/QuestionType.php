<?php

declare(strict_types=1);

namespace App\Domain\Assessment\ValueObjects;

enum QuestionType: string
{
    case FREE_TEXT       = 'free_text';       // VCI: 自由記述（0/1/2点）
    case MULTIPLE_CHOICE = 'multiple_choice'; // PRI/WMI(暗算): 4択
    case SEQUENCE        = 'sequence';        // WMI: 数唱
    case TIME_BASED      = 'time_based';      // PSI: 時間制限あり
}
