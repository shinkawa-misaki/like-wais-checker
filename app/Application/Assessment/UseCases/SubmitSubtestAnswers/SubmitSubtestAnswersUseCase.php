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
        $sequenceMap = [];

        foreach ($questions as $question) {
            $questionMap[$question->getId()->getValue()] = $question;
            $sequenceMap[$question->getSequenceNumber()] = $question;
        }

        $validAnswers = 0;
        $answersWithSequence = [];

        // まず、送信された回答に対応する問題のシーケンス番号を特定
        foreach ($input->answers as $index => $answerInput) {
            $answersWithSequence[] = [
                'input' => $answerInput,
                'sequence' => $index + 1, // 0-based index to 1-based sequence
            ];
        }

        foreach ($answersWithSequence as $answerData) {
            $answerInput = $answerData['input'];
            $expectedSequence = $answerData['sequence'];

            // まず、送信された問題IDで検索
            $question = $questionMap[$answerInput->questionId] ?? null;

            // 問題IDが見つからない場合は、シーケンス番号で照合
            if ($question === null) {
                $question = $sequenceMap[$expectedSequence] ?? null;

                if ($question === null) {
                    // シーケンス番号でも見つからない場合はスキップ
                    continue;
                }
            }

            // Create answer with the correct question ID
            $answer = new Answer(
                questionId: $question->getId(), // 正しい問題IDを使用
                assessmentId: $assessmentId,
                response: $answerInput->response,
                awardedScore: Score::zero(),
            );

            // Auto-grade all answers using the scoring service
            $gradedScore = $this->scoringService->gradeAnswer($question, $answer);
            $answer->updateScore($gradedScore);

            $assessment->addAnswer($answer);
            $validAnswers++;
        }

        // 有効な回答が1つもない場合はエラー
        if ($validAnswers === 0 && count($input->answers) > 0) {
            throw new DomainException(
                "No valid answers found. Please refresh the page and restart the subtest."
            );
        }

        $assessment->markSubtestCompleted($subtestType);

        if ($assessment->areAllSubtestsCompleted()) {
            $assessment->complete();
        }

        $this->assessmentRepository->save($assessment);

        return AssessmentDto::fromEntity($assessment);
    }
}
