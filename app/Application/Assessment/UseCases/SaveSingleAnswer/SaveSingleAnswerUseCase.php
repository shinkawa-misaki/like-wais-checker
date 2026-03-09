<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\SaveSingleAnswer;

use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use App\Domain\Assessment\Repositories\QuestionRepositoryInterface;
use App\Domain\Assessment\Services\ScoringDomainService;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\QuestionType;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;
use DomainException;

final class SaveSingleAnswerUseCase
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessmentRepository,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly ScoringDomainService $scoringService,
    ) {
    }

    public function execute(SaveSingleAnswerInput $input): void
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

        // 問題をDBから取得
        $questions = $this->questionRepository->findByIds([$input->questionId]);
        $question = $questions[$input->questionId] ?? null;

        if ($question === null) {
            throw new DomainException("Question not found: {$input->questionId}");
        }

        // 採点: FREE_TEXT / TIME_BASED はユーザーの自己採点、それ以外は自動採点
        if ($question->getQuestionType() === QuestionType::FREE_TEXT
            || $question->getQuestionType() === QuestionType::TIME_BASED) {
            $awardedScore = new Score(
                max(0.0, min((float) ($input->awardedScore ?? 0), (float) $question->getMaxPoints()))
            );
        } else {
            $tempAnswer = new Answer(
                questionId: $question->getId(),
                assessmentId: $assessmentId,
                response: $input->response,
                awardedScore: Score::zero(),
            );
            $awardedScore = $this->scoringService->gradeAnswer($question, $tempAnswer);
        }

        $answer = new Answer(
            questionId: $question->getId(),
            assessmentId: $assessmentId,
            response: $input->response,
            awardedScore: $awardedScore,
        );

        // 即座にDBへ保存
        $this->assessmentRepository->saveAnswer($answer);
    }
}
