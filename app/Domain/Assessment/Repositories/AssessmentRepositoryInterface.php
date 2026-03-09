<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Repositories;

use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Entities\Assessment;
use App\Domain\Assessment\ValueObjects\AssessmentId;

interface AssessmentRepositoryInterface
{
    public function save(Assessment $assessment): void;

    public function saveAnswer(Answer $answer): void;

    public function findById(AssessmentId $id): ?Assessment;
}
