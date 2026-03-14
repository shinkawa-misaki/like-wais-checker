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
                '要点の抽出・言い換え・優先事項の整理・共通点の把握を行います。'
                . '各問 0〜2点で自己採点してください。'
                . '2点: 要点が合っており実用的な表現ができている。'
                . '1点: 方向性は合っているが曖昧・具体的すぎる。'
                . '0点: ズレている、意味不明、無回答。',
            SubtestType::STRUCTURAL_UNDERSTANDING =>
                '規則の発見・分類・手順の構造を読み取る問題です。'
                . 'A〜Dの選択肢から正解を1つ選んでください。',
            SubtestType::RETENTION_MANIPULATION =>
                '情報を保持しながら並べ替えたり、条件を扱う問題です。'
                . '指示に従って正確に回答してください。完全一致で1点です。',
            SubtestType::SPEED_RESILIENCE =>
                '【60秒の制限時間】左の記号が右のグループに含まれているか素早く判断してください。'
                . '○（はい）か×（いいえ）で答えてください。迷ったら飛ばして次へ進んでください。',
        };
    }
}
