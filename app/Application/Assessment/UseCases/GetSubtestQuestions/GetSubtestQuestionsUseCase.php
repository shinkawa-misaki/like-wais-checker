<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\GetSubtestQuestions;

use App\Application\Assessment\DTOs\QuestionDto;
use App\Domain\Assessment\Repositories\QuestionRepositoryInterface;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\SubtestType;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use DomainException;

final class GetSubtestQuestionsUseCase
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly AssessmentRepositoryInterface $assessmentRepository,
    ) {
    }

    /**
     * @return array{
     *   subtestType: string,
     *   subtestLabel: string,
     *   indexType: string,
     *   timeLimitSeconds: int|null,
     *   questions: array<QuestionDto>,
     *   instructions: string
     * }
     */
    public function execute(string $assessmentId, string $subtestTypeValue): array
    {
        $id = new AssessmentId($assessmentId);
        $assessment = $this->assessmentRepository->findById($id);

        if ($assessment === null) {
            throw new DomainException("Assessment not found: {$assessmentId}");
        }

        if ($assessment->getStatus()->isCompleted()) {
            throw new DomainException('Assessment is already completed.');
        }

        $subtestType = SubtestType::from($subtestTypeValue);
        $questions = $this->questionRepository->findBySubtestType($subtestType);

        $questionDtos = array_map(
            fn ($q) => QuestionDto::fromEntity($q),
            $questions
        );

        return [
            'subtestType'      => $subtestType->value,
            'subtestLabel'     => $subtestType->label(),
            'indexType'        => $subtestType->indexType()->value,
            'timeLimitSeconds' => $subtestType->timeLimitSeconds(),
            'questions'        => $questionDtos,
            'instructions'     => $this->buildInstructions($subtestType),
        ];
    }

    private function buildInstructions(SubtestType $subtestType): string
    {
        return match ($subtestType) {
            SubtestType::VERBAL_ORGANIZATION =>
                '言語的な類推・カテゴリー分類・言い換え・共通点把握の問題です。'
                . 'A〜Dの選択肢から最も適切なものを1つ選んでください。',
            SubtestType::STRUCTURAL_UNDERSTANDING =>
                '数列や記号列の規則発見・パターン認識・論理的分類の問題です。'
                . 'A〜Dの選択肢から正解を1つ選んでください。',
            SubtestType::RETENTION_MANIPULATION =>
                '頭の中で数を操作したり、情報を保持しながら処理する問題です。'
                . 'A〜Dの選択肢から正解を1つ選んでください。',
            SubtestType::SPEED_RESILIENCE =>
                '【60秒の制限時間】左の記号が右のグループに含まれているか素早く判断してください。'
                . '○（はい）か×（いいえ）で答えてください。迷ったら飛ばして次へ進んでください。',
        };
    }
}
