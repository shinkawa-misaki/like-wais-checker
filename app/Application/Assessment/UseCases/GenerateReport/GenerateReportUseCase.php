<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\GenerateReport;

use App\Application\Assessment\DTOs\IndexScoreDto;
use App\Application\Assessment\DTOs\ReportDto;
use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use App\Domain\Assessment\Services\ScoringDomainService;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\IndexType;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;
use App\Services\ClaudeApiService;
use DateTimeImmutable;
use DomainException;

final class GenerateReportUseCase
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessmentRepository,
        private readonly ScoringDomainService $scoringService,
        private readonly ClaudeApiService $claudeApiService,
    ) {
    }

    /**
     * @param array<string, string> $condition ['sleep' => '十分', 'fatigue' => '低', 'anxiety' => '低', 'focus' => '安定']
     */
    public function execute(string $assessmentId, array $condition = []): ReportDto
    {
        $id         = new AssessmentId($assessmentId);
        $assessment = $this->assessmentRepository->findById($id);

        if ($assessment === null) {
            throw new DomainException("Assessment not found: {$assessmentId}");
        }

        if (! $assessment->getStatus()->isCompleted()) {
            throw new DomainException('Assessment is not yet completed. Complete all subtests first.');
        }

        /** @var array<string, list<Answer>> $answersBySubtest */
        $answersBySubtest = [];

        foreach ($assessment->getAnswers() as $answer) {
            $answersBySubtest[$answer->getSubtestType()->value][] = $answer;
        }

        /** @var array<string, Score> $subtestScores */
        $subtestScores = [];

        foreach (SubtestType::orderedList() as $subtestType) {
            $subtestAnswers                     = $answersBySubtest[$subtestType->value] ?? [];
            $subtestScores[$subtestType->value] = $this->scoringService->calculateSubtestScore($subtestAnswers);
        }

        // 指数スコアを計算（静的解釈で初期化）keyは indexType 文字列
        /** @var array<string, IndexScoreDto> $indexScoreDtos */
        $indexScoreDtos = [];

        foreach (IndexType::cases() as $indexType) {
            $score            = $this->scoringService->calculateIndexScore($indexType, $subtestScores);
            $percentage       = $score->toPercentage($indexType->maxScore());
            $level            = $this->scoringService->percentageLevel($percentage);
            $pseudoIQ         = $this->scoringService->calculatePseudoIQ($percentage);
            $iqInterpretation = $this->scoringService->interpretIQ($pseudoIQ);
            $isStrength       = $percentage >= 61;
            $interpretation   = $isStrength
                ? $indexType->strengthDescription()
                : $indexType->weaknessDescription();

            $indexScoreDtos[$indexType->value] = new IndexScoreDto(
                indexType: $indexType->value,
                label: $indexType->label(),
                rawScore: $score->getValue(),
                maxScore: $indexType->maxScore(),
                percentage: $percentage,
                level: $level,
                pseudoIQ: $pseudoIQ,
                iqInterpretation: $iqInterpretation,
                interpretation: $interpretation,
            );
        }

        // パーセンテージ降順でソートして強み / 弱点を抽出
        $sorted = array_values($indexScoreDtos);
        usort($sorted, fn ($a, $b) => $b->percentage <=> $a->percentage);

        $strengthIndices = array_slice($sorted, 0, 2);
        $weaknessIndices = array_slice(array_reverse($sorted), 0, 2);

        // Claude API でレポート生成（失敗時は静的フォールバック）
        $strategies = $this->buildStrategies($strengthIndices, $weaknessIndices);
        $nextSteps  = $this->buildNextSteps($weaknessIndices);
        $aiAdvice   = null;

        if (config('services.anthropic.api_key')) {
            try {
                [$indexScoreDtos, $strengthIndices, $weaknessIndices, $strategies, $nextSteps, $aiAdvice]
                    = $this->enrichWithClaude($indexScoreDtos, $strengthIndices, $weaknessIndices, $condition);
            } catch (\Throwable $e) {
                \Log::warning('Claude API failed, falling back to static report', ['error' => $e->getMessage()]);
            }
        }

        // メイン表示用にアルファベット順でソート
        $orderedDtos = array_values($indexScoreDtos);
        usort($orderedDtos, fn ($a, $b) => strcmp($a->indexType, $b->indexType));

        return new ReportDto(
            assessmentId: $assessmentId,
            disclaimer: $this->buildDisclaimer(),
            indexScores: $orderedDtos,
            strengthIndices: $strengthIndices,
            weaknessIndices: $weaknessIndices,
            strategies: $strategies,
            nextSteps: $nextSteps,
            aiAdvice: $aiAdvice,
            generatedAt: (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @param  array<string, IndexScoreDto> $indexScoreDtos
     * @param  array<IndexScoreDto>         $strengthIndices
     * @param  array<IndexScoreDto>         $weaknessIndices
     * @param  array<string, string>        $condition
     * @return array{0: array<string, IndexScoreDto>, 1: array<IndexScoreDto>, 2: array<IndexScoreDto>, 3: array<string, string>, 4: array<string>, 5: string|null}
     */
    private function enrichWithClaude(
        array $indexScoreDtos,
        array $strengthIndices,
        array $weaknessIndices,
        array $condition,
    ): array {
        $scores = [];

        foreach ($indexScoreDtos as $type => $dto) {
            $scores[$type] = ['raw' => $dto->rawScore, 'max' => $dto->maxScore, 'pct' => $dto->percentage];
        }

        $strengthOrder = array_map(
            fn ($dto) => ['type' => $dto->indexType, 'label' => $dto->label, 'pct' => $dto->percentage],
            $strengthIndices,
        );

        $weaknessOrder = array_map(
            fn ($dto) => ['type' => $dto->indexType, 'label' => $dto->label, 'pct' => $dto->percentage],
            $weaknessIndices,
        );

        $resolvedCondition = array_merge(
            ['sleep' => '不明', 'fatigue' => '不明', 'anxiety' => '不明', 'focus' => '不明'],
            $condition,
        );

        $result = $this->claudeApiService->generateReport($scores, $resolvedCondition, $strengthOrder, $weaknessOrder);

        // 強み TOP2 の解釈を Claude の説明で上書き
        $updatedStrengths = [];

        foreach ($strengthIndices as $i => $dto) {
            $desc = $result['strength_top2'][$i]['description'] ?? $dto->interpretation;
            $updated = $this->replaceInterpretation($dto, $desc);
            $updatedStrengths[] = $updated;
            $indexScoreDtos[$dto->indexType] = $this->replaceInterpretation($indexScoreDtos[$dto->indexType], $desc);
        }

        // 弱み TOP2 の解釈を Claude の説明で上書き
        $updatedWeaknesses = [];

        foreach ($weaknessIndices as $i => $dto) {
            $desc = $result['weakness_top2'][$i]['description'] ?? $dto->interpretation;
            $updated = $this->replaceInterpretation($dto, $desc);
            $updatedWeaknesses[] = $updated;
            $indexScoreDtos[$dto->indexType] = $this->replaceInterpretation($indexScoreDtos[$dto->indexType], $desc);
        }

        $strategies = [
            'work'     => $result['work_strategy']     ?? '',
            'life'     => $result['life_strategy']     ?? '',
            'strength' => $result['strength_strategy'] ?? '',
        ];

        $nextSteps = array_values(array_filter([
            $result['work_strategy']     ?? null,
            $result['life_strategy']     ?? null,
            $result['strength_strategy'] ?? null,
        ]));

        $aiAdvice = isset($result['ai_advice']) && is_string($result['ai_advice']) ? $result['ai_advice'] : null;

        return [$indexScoreDtos, $updatedStrengths, $updatedWeaknesses, $strategies, $nextSteps, $aiAdvice];
    }

    private function replaceInterpretation(IndexScoreDto $dto, string $interpretation): IndexScoreDto
    {
        return new IndexScoreDto(
            indexType: $dto->indexType,
            label: $dto->label,
            rawScore: $dto->rawScore,
            maxScore: $dto->maxScore,
            percentage: $dto->percentage,
            level: $dto->level,
            pseudoIQ: $dto->pseudoIQ,
            iqInterpretation: $dto->iqInterpretation,
            interpretation: $interpretation,
        );
    }

    private function buildDisclaimer(): string
    {
        return '【重要な注意】これはWAIS（標準化検査）ではありません。年齢別ノームがないため、正式なIQは算出できません。'
            . '結果は「強み／負荷ポイントの理解」など自己理解・戦略設計の参考指標です。'
            . '診断や断定、ラベリングには使いません。'
            . '体調（睡眠不足、不安、疲労、血糖など）でWMI/PSIが落ちやすいため、必要なら別日に再実施してください。';
    }

    /**
     * @param  array<IndexScoreDto> $strengthIndices
     * @param  array<IndexScoreDto> $weaknessIndices
     * @return array<string, string>
     */
    private function buildStrategies(array $strengthIndices, array $weaknessIndices): array
    {
        $strategies = [];

        foreach ($strengthIndices as $dto) {
            $indexType = IndexType::from($dto->indexType);
            $strategies["strength_{$dto->indexType}"] = "【強み: {$indexType->label()}】{$indexType->strengthDescription()}";
        }

        foreach ($weaknessIndices as $dto) {
            $indexType = IndexType::from($dto->indexType);
            $strategies["weakness_{$dto->indexType}"] = "【負荷ポイント: {$indexType->label()}】{$indexType->weaknessDescription()}";
        }

        return $strategies;
    }

    /**
     * @param  array<IndexScoreDto> $weaknessIndices
     * @return array<string>
     */
    private function buildNextSteps(array $weaknessIndices): array
    {
        $steps = [];

        foreach ($weaknessIndices as $dto) {
            $steps[] = match (IndexType::from($dto->indexType)) {
                IndexType::VCI => '図→短文→箇条書きの順で考えを外在化する練習を取り入れましょう。',
                IndexType::PRI => '複雑な問題は1軸ずつ条件を分解して検証する習慣をつけましょう。',
                IndexType::WMI => 'タスクを小さく分割し、途中メモを活用する環境を整えましょう。',
                IndexType::PSI => '締切を前倒しで小分けに設定し、速度より正確さを優先する役割配置を検討しましょう。',
            };
        }

        $steps[] = '体調管理（睡眠・食事・運動）を整えると、特にWMIとPSIのパフォーマンスが向上しやすいです。';
        $steps[] = '気になった場合は数日後に再実施し、結果の変動を確認してください。';

        return $steps;
    }
}
