<?php

declare(strict_types=1);

namespace App\Application\Assessment\UseCases\GenerateReport;

use App\Application\Assessment\DTOs\IndexScoreDto;
use App\Application\Assessment\DTOs\ReportDto;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use App\Domain\Assessment\Repositories\QuestionRepositoryInterface;
use App\Domain\Assessment\Services\ScoringDomainService;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\IndexType;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;
use DateTimeImmutable;
use DomainException;

final class GenerateReportUseCase
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessmentRepository,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly ScoringDomainService $scoringService,
    ) {
    }

    public function execute(string $assessmentId): ReportDto
    {
        $id = new AssessmentId($assessmentId);
        $assessment = $this->assessmentRepository->findById($id);

        if ($assessment === null) {
            throw new DomainException("Assessment not found: {$assessmentId}");
        }

        if (!$assessment->getStatus()->isCompleted()) {
            throw new DomainException('Assessment is not yet completed. Complete all subtests first.');
        }

        // Group answers by subtest question
        $allAnswers = $assessment->getAnswers();
        $answersByQuestion = [];

        foreach ($allAnswers as $answer) {
            $answersByQuestion[$answer->getQuestionId()->getValue()] = $answer;
        }

        // 回答に含まれるquestion_idでDBから問題を取得する
        $allQuestionIds = array_keys($answersByQuestion);
        $allQuestions = $this->questionRepository->findByIds($allQuestionIds);

        // サブテストタイプ別に問題と回答をグループ化
        $questionsBySubtest = [];
        foreach ($allQuestions as $question) {
            $questionsBySubtest[$question->getSubtestType()->value][] = $question;
        }

        // Calculate subtest scores
        /** @var array<string, Score> $subtestScores */
        $subtestScores = [];

        foreach (SubtestType::orderedList() as $subtestType) {
            $questions = $questionsBySubtest[$subtestType->value] ?? [];
            $subtestAnswers = [];

            foreach ($questions as $question) {
                $answer = $answersByQuestion[$question->getId()->getValue()] ?? null;

                if ($answer !== null) {
                    $subtestAnswers[] = $answer;
                }
            }

            $subtestScores[$subtestType->value] = $this->scoringService->calculateSubtestScore(
                $subtestType,
                $subtestAnswers
            );
        }

        // Calculate index scores
        $indexScoreDtos = [];

        foreach (IndexType::cases() as $indexType) {
            $score = $this->scoringService->calculateIndexScore($indexType, $subtestScores);
            $percentage = $score->toPercentage($indexType->maxScore());
            $level = $this->scoringService->percentageLevel($percentage);

            // 擬似IQスコアを計算
            $pseudoIQ = $this->scoringService->calculatePseudoIQ($percentage);
            $iqInterpretation = $this->scoringService->interpretIQ($pseudoIQ);

            $isStrength = $percentage >= 61;
            $interpretation = $isStrength
                ? $indexType->strengthDescription()
                : $indexType->weaknessDescription();

            $indexScoreDtos[] = new IndexScoreDto(
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

        // Sort by percentage descending for top/bottom identification
        usort($indexScoreDtos, fn ($a, $b) => $b->percentage <=> $a->percentage);

        $strengthIndices = array_slice($indexScoreDtos, 0, 2);
        $weaknessIndices = array_slice(array_reverse($indexScoreDtos), 0, 2);

        // Re-sort in original order for main display
        usort($indexScoreDtos, fn ($a, $b) => strcmp($a->indexType, $b->indexType));

        return new ReportDto(
            assessmentId: $assessmentId,
            disclaimer: $this->buildDisclaimer(),
            indexScores: $indexScoreDtos,
            strengthIndices: $strengthIndices,
            weaknessIndices: $weaknessIndices,
            strategies: $this->buildStrategies($strengthIndices, $weaknessIndices),
            nextSteps: $this->buildNextSteps($weaknessIndices),
            generatedAt: (new DateTimeImmutable())->format('Y-m-d H:i:s'),
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
     * @param array<IndexScoreDto> $strengthIndices
     * @param array<IndexScoreDto> $weaknessIndices
     * @return array<string, string>
     */
    private function buildStrategies(array $strengthIndices, array $weaknessIndices): array
    {
        $strategies = [];

        foreach ($strengthIndices as $dto) {
            $indexType = IndexType::from($dto->indexType);
            $key = "strength_{$dto->indexType}";
            $strategies[$key] = "【強み: {$indexType->label()}】{$indexType->strengthDescription()}";
        }

        foreach ($weaknessIndices as $dto) {
            $indexType = IndexType::from($dto->indexType);
            $key = "weakness_{$dto->indexType}";
            $strategies[$key] = "【負荷ポイント: {$indexType->label()}】{$indexType->weaknessDescription()}";
        }

        return $strategies;
    }

    /**
     * @param array<IndexScoreDto> $weaknessIndices
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
