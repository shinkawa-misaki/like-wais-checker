<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\StartAssessment;

use App\Application\Assessment\DTOs\AssessmentDto;
use App\Domain\Assessment\Entities\Assessment;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;

final class StartAssessmentUseCase
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessmentRepository,
    ) {
    }

    public function execute(): AssessmentDto
    {
        $assessment = Assessment::start();
        $this->assessmentRepository->save($assessment);

        return AssessmentDto::fromEntity($assessment);
    }
}
