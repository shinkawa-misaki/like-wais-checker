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
            $interpretation = $indexType->interpretationByLevel($level);

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
        return '【重要な注意】これは標準化された心理検査ではありません。年齢別ノームがないため、正式なIQは算出できません。'
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
        // 静的フォールバック用: Claude API 未使用時はスコア段階に応じたアドバイスを生成
        $workParts     = [];
        $lifeParts     = [];
        $strengthParts = [];

        foreach ($strengthIndices as $dto) {
            $strengthParts[] = "【{$dto->label} / {$dto->level}】{$dto->interpretation}";
        }

        foreach ($weaknessIndices as $dto) {
            [$work, $life] = $this->buildWorkLifeAdvice(IndexType::from($dto->indexType), $dto->level);
            $workParts[]   = $work;
            $lifeParts[]   = $life;
        }

        return [
            'work'     => implode(' ', $workParts) ?: '負荷がかかる場面を事前に把握し、余裕を持ったスケジュール設計を心掛けましょう。',
            'life'     => implode(' ', $lifeParts) ?: '睡眠・食事・運動を整えることで、認知資源の回復を助けられます。',
            'strength' => implode(' ', $strengthParts) ?: '得意な資源を意識的に使う場面を増やしていきましょう。',
        ];
    }

    /** @return array{0: string, 1: string} [仕事のアドバイス, 生活のアドバイス] */
    private function buildWorkLifeAdvice(IndexType $indexType, string $level): array
    {
        $urgent = in_array($level, ['かなり弱い', '弱め'], true);

        return match ($indexType) {
            IndexType::VCI => [
                $urgent
                    ? '指示・会議内容はその場で3行の箇条書きメモに落とし、後で清書する習慣を作りましょう。テンプレートを活用してゼロから書く負担を減らすことが重要です。'
                    : '言語化の負荷が高い場面では、図解から始めて徐々に言葉に変換する手順を取り入れてみましょう。',
                $urgent
                    ? '日記や音声メモなど、気軽に言語を使う機会を日常に組み込み、言語化を「完成品ではなく作業」と捉える練習が助けになります。'
                    : '読む・書く・話す機会を意識的に増やすことで、言語整理の力を日常的に鍛えられます。',
            ],
            IndexType::PRI => [
                $urgent
                    ? '複雑なタスクは「条件を1つずつ書き出す→例を探す→1軸ずつ検証する」の手順を必ず踏みましょう。いきなり答えを出そうとせず、プロセスを外に出すことが重要です。'
                    : '作業前に「型（フレーム）」を作り、パターンを先に決めてから詰める進め方を試してみましょう。',
                $urgent
                    ? '情報が多い環境（ニュース・SNS）に一気に触れる時間を減らし、一つのテーマを深く考える時間を確保するとリセットになります。'
                    : 'パズルやパターン認識ゲームなど、構造を読む練習を日常の中に取り入れると効果的です。',
            ],
            IndexType::WMI => [
                $urgent
                    ? '「1タスク＝1画面」を徹底し、複数のブラウザタブや作業を同時に開かない環境を作りましょう。途中の気づきはすべてメモに外出しして頭を空ける意識が重要です。'
                    : 'タスクを小分けにして、こまめにメモを取る習慣を作ると安定感が増します。',
                $urgent
                    ? '睡眠を優先することが最大の対策です。ワーキングメモリーは睡眠不足で著しく低下するため、今夜の就寝時間を最優先に設定してください。'
                    : '十分な睡眠と適度な運動を心掛けると、ワーキングメモリーのパフォーマンスが維持しやすくなります。',
            ],
            IndexType::PSI => [
                $urgent
                    ? '速度が求められる役割・会議・締切を可能な限り避け、「正確さ優先」を前提に仕事設計を見直しましょう。締切は実際の期日より2〜3日前倒しで自分に設定することが有効です。'
                    : '締切を細かく小分けにして「今日やること」を絞り込むことで、焦りを減らして安定した作業ができます。',
                $urgent
                    ? '急かされる状況（即レス・当日対応）は心身の消耗が大きいため、返信時間のバッファ（例：原則〇時間以内）を周囲と合意しておくことを検討しましょう。'
                    : '時間を意識した作業（ポモドーロ法など）を練習として取り入れると、時間圧への耐性を徐々に高められます。',
            ],
        };
    }

    /**
     * @param  array<IndexScoreDto> $weaknessIndices
     * @return array<string>
     */
    private function buildNextSteps(array $weaknessIndices): array
    {
        $steps = [];

        foreach ($weaknessIndices as $dto) {
            $level = $dto->level;
            $steps[] = match (IndexType::from($dto->indexType)) {
                IndexType::VCI => match ($level) {
                    'かなり弱い' => '言語化の負荷を下げるため、テンプレート・定型文・音声メモを積極活用し、ゼロから書く機会を減らしましょう。',
                    '弱め'       => '図解→短文→箇条書きの順で考えを外在化する練習を日常に取り入れましょう。',
                    default      => '言語整理の機会（議事録・日記など）を意識的に設けると、徐々に負荷が軽くなります。',
                },
                IndexType::PRI => match ($level) {
                    'かなり弱い' => '型やテンプレートを借りることから始め、自分でゼロから構造を作ろうとしないことが最初の一手です。',
                    '弱め'       => '複雑な問題は1軸ずつ条件を書き出して検証する習慣をつけましょう。',
                    default      => '作業前にフレーム（型）を先に決める練習を取り入れると、構造理解が安定します。',
                },
                IndexType::WMI => match ($level) {
                    'かなり弱い' => '「覚えない」を前提に、すべてをメモ・ツール・チェックリストに外出しする仕組みを今日から作りましょう。',
                    '弱め'       => 'タスクを小さく分割し、途中でメモを取る環境を整えましょう。',
                    default      => '作業の合間にメモを見直す習慣をつけると、情報の取りこぼしが減ります。',
                },
                IndexType::PSI => match ($level) {
                    'かなり弱い' => '速度勝負の役割配置を避け、正確さ優先の環境と十分なバッファを持つ締切設定を優先しましょう。',
                    '弱め'       => '締切を前倒しで小分けに設定し、速度より正確さを優先する役割配置を検討しましょう。',
                    default      => '時間を区切った集中作業（ポモドーロ法など）を試すと、効率と安定感のバランスが取りやすくなります。',
                },
            };
        }

        $steps[] = '体調管理（睡眠・食事・運動）を整えると、特にWMIとPSIのパフォーマンスが向上しやすいです。';
        $steps[] = '気になった場合は数日後に再実施し、結果の変動を確認してください。';

        return $steps;
    }
}
