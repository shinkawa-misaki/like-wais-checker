<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\SubmitSubtestAnswers;

use App\Application\Assessment\DTOs\AnswerInputDto;
use App\Application\Assessment\DTOs\AssessmentDto;
use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use App\Domain\Assessment\Repositories\QuestionRepositoryInterface;
use App\Domain\Assessment\Services\ScoringDomainService;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\QuestionType;
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

        // タイムド系サブテストの一括送信：回答を1件ずつDBへ直接保存
        if (count($input->answers) > 0) {
            $this->saveAnswersToDb($input->answers, $assessmentId);
        }

        $assessment->markSubtestCompleted($subtestType);

        if ($assessment->areAllSubtestsCompleted()) {
            $assessment->complete();
        }

        $this->assessmentRepository->save($assessment);

        return AssessmentDto::fromEntity($assessment);
    }

    /**
     * 回答を採点してDBへ直接保存する（インメモリのAssessmentには追加しない）
     *
     * @param array<AnswerInputDto> $answers
     */
    private function saveAnswersToDb(array $answers, AssessmentId $assessmentId): void
    {
        $submittedIds = array_map(fn (AnswerInputDto $a) => $a->questionId, $answers);
        $questionMap = $this->questionRepository->findByIds($submittedIds);

        foreach ($answers as $answerInput) {
            $question = $questionMap[$answerInput->questionId] ?? null;

            if ($question === null) {
                throw new DomainException("Question not found: {$answerInput->questionId}");
            }

            if ($question->getQuestionType() === QuestionType::FREE_TEXT) {
                // 自由記述：ユーザーの自己採点スコアを使用
                $awardedScore = new Score(
                    max(0.0, min((float) ($answerInput->awardedScore ?? 0), (float) $question->getMaxPoints()))
                );
            } elseif ($question->getQuestionType() === QuestionType::TIME_BASED) {
                // タイムド系（シンボルサーチ等）：フロントエンドから送信されたスコアを使用
                $awardedScore = new Score(
                    max(0.0, min((float) ($answerInput->awardedScore ?? 0), (float) $question->getMaxPoints()))
                );
            } else {
                // 選択式・配列式：自動採点
                $tempAnswer = new Answer(
                    questionId: $question->getId(),
                    assessmentId: $assessmentId,
                    response: $answerInput->response,
                    awardedScore: Score::zero(),
                );
                $awardedScore = $this->scoringService->gradeAnswer($question, $tempAnswer);
            }

            $answer = new Answer(
                questionId: $question->getId(),
                assessmentId: $assessmentId,
                response: $answerInput->response,
                awardedScore: $awardedScore,
            );

            $this->assessmentRepository->saveAnswer($answer);
        }
    }
}
