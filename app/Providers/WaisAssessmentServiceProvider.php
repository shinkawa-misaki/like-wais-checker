<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Assessment\UseCases\GenerateReport\GenerateReportUseCase;
use App\Application\Assessment\UseCases\GetSubtestQuestions\GetSubtestQuestionsUseCase;
use App\Application\Assessment\UseCases\StartAssessment\StartAssessmentUseCase;
use App\Application\Assessment\UseCases\SubmitSubtestAnswers\SubmitSubtestAnswersUseCase;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use App\Domain\Assessment\Repositories\QuestionRepositoryInterface;
use App\Domain\Assessment\Services\ScoringDomainService;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssessmentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentQuestionRepository;
use Illuminate\Support\ServiceProvider;

final class WaisAssessmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            AssessmentRepositoryInterface::class,
            EloquentAssessmentRepository::class,
        );

        $this->app->bind(
            QuestionRepositoryInterface::class,
            EloquentQuestionRepository::class,
        );

        // Domain Services
        $this->app->singleton(ScoringDomainService::class);

        // Use Cases
        $this->app->bind(StartAssessmentUseCase::class, fn ($app) => new StartAssessmentUseCase(
            assessmentRepository: $app->make(AssessmentRepositoryInterface::class),
        ));

        $this->app->bind(GetSubtestQuestionsUseCase::class, fn ($app) => new GetSubtestQuestionsUseCase(
            questionRepository: $app->make(QuestionRepositoryInterface::class),
            assessmentRepository: $app->make(AssessmentRepositoryInterface::class),
        ));

        $this->app->bind(SubmitSubtestAnswersUseCase::class, fn ($app) => new SubmitSubtestAnswersUseCase(
            assessmentRepository: $app->make(AssessmentRepositoryInterface::class),
            questionRepository: $app->make(QuestionRepositoryInterface::class),
            scoringService: $app->make(ScoringDomainService::class),
        ));

        $this->app->bind(GenerateReportUseCase::class, fn ($app) => new GenerateReportUseCase(
            assessmentRepository: $app->make(AssessmentRepositoryInterface::class),
            scoringService: $app->make(ScoringDomainService::class),
        ));
    }
}
