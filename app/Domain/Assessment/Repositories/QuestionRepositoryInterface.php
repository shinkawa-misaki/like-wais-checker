<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Repositories;

use App\Domain\Assessment\Entities\Question;
use App\Domain\Assessment\ValueObjects\SubtestType;

interface QuestionRepositoryInterface
{
    /** @return array<Question> */
    public function findBySubtestType(SubtestType $subtestType): array;
}
