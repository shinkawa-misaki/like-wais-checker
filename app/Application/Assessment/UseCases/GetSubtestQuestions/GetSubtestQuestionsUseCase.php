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
            SubtestType::SIMILARITIES =>
                '2つの言葉の共通点を「一文＋補足一文まで」で答えてください。抽象的なカテゴリで答えるとより高得点になります。（例：「犬と猫」→「どちらも哺乳類のペット」）',
            SubtestType::VOCABULARY =>
                '提示された単語を短く説明してください（定義＋例1つまで）。簡潔かつ本質を捉えた説明が高得点です。',
            SubtestType::PATTERN_RECOGNITION =>
                '並びの規則を見つけ、次に来るものを4択から選んでください。A, B, C, Dのいずれかで答えてください。',
            SubtestType::MATRIX_REASONING =>
                '縦横の規則が同時に走る問題です。4択から正しい答えを選んでください。A, B, C, Dのいずれかで答えてください。',
            SubtestType::DIGIT_SPAN =>
                '提示された数字列を指示通りに答えてください。順唱はそのまま、逆唱は逆から、並べ替えは小さい順に。答えは1回のみ、やり直し不可です。',
            SubtestType::ARITHMETIC =>
                '暗算で答えてください（紙の筆算禁止）。各問20秒以内を目安にしてください。',
            SubtestType::SYMBOL_SEARCH =>
                '【タイマー必須】タイマーを90秒にセットしてから開始してください。ターゲット2文字が検索列に「両方ある」なら○、片方/両方ないなら×で答えてください。迷ったら次へ進んでください。',
            SubtestType::CODING =>
                '【タイマー必須】対応表（1=A, 2=B, 3=C, 4=D, 5=E, 6=F, 7=G, 8=H, 9=I）を見て、90秒以内にできるだけ多くの数字を文字に変換してください。',
        };
    }
}
