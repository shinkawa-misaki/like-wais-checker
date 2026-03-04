<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\SubmitSubtestAnswers;

use App\Application\Assessment\DTOs\AssessmentDto;
use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use App\Domain\Assessment\Repositories\QuestionRepositoryInterface;
use App\Domain\Assessment\Services\ScoringDomainService;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\QuestionId;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;
use DomainException;

final class SubmitSubtestAnswersUseCase
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessmentRepository,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly ScoringDomainService $scoringService,
    ) {
    }

    public function execute(SubmitSubtestAnswersInput $input): AssessmentDto
    {
        $assessmentId = new AssessmentId($input->assessmentId);
        $assessment = $this->assessmentRepository->findById($assessmentId);

        if ($assessment === null) {
            throw new DomainException("Assessment not found: {$input->assessmentId}");
        }

        if ($assessment->getStatus()->isCompleted()) {
            throw new DomainException('Assessment is already completed.');
        }

        $subtestType = SubtestType::from($input->subtestType);

        if ($assessment->isSubtestCompleted($subtestType)) {
            throw new DomainException("Subtest {$subtestType->value} is already completed.");
        }

        $questions = $this->questionRepository->findBySubtestType($subtestType);
        $questionMap = [];

        foreach ($questions as $question) {
            $questionMap[$question->getId()->getValue()] = $question;
        }

        foreach ($input->answers as $answerInput) {
            $question = $questionMap[$answerInput->questionId] ?? null;

            if ($question === null) {
                throw new DomainException("Question not found: {$answerInput->questionId}");
            }

            // For FREE_TEXT questions, use the provided awardedScore (human grading)
            $awardedScore = match ($question->getQuestionType()) {
                \App\Domain\Assessment\ValueObjects\QuestionType::FREE_TEXT => new Score(
                    max(0.0, min((float) ($answerInput->awardedScore ?? 0), (float) $question->getMaxPoints()))
                ),
                default => Score::zero(), // Will be auto-graded by domain service
            };

            $answer = new Answer(
                questionId: new QuestionId($answerInput->questionId),
                assessmentId: $assessmentId,
                response: $answerInput->response,
                awardedScore: $awardedScore,
            );

            // Auto-grade non-free-text answers
            if ($question->getQuestionType() !== \App\Domain\Assessment\ValueObjects\QuestionType::FREE_TEXT) {
                $gradedScore = $this->scoringService->gradeAnswer($question, $answer);
                $answer->updateScore($gradedScore);
            }

            $assessment->addAnswer($answer);
        }

        $assessment->markSubtestCompleted($subtestType);

        if ($assessment->areAllSubtestsCompleted()) {
            $assessment->complete();
        }

        $this->assessmentRepository->save($assessment);

        return AssessmentDto::fromEntity($assessment);
    }
}
