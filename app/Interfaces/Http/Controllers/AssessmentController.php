<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use App\Application\Assessment\DTOs\AnswerInputDto;
use App\Application\Assessment\UseCases\GenerateReport\GenerateReportUseCase;
use App\Application\Assessment\UseCases\GetSubtestQuestions\GetSubtestQuestionsUseCase;
use App\Application\Assessment\UseCases\StartAssessment\StartAssessmentUseCase;
use App\Application\Assessment\UseCases\SubmitSubtestAnswers\SubmitSubtestAnswersInput;
use App\Application\Assessment\UseCases\SubmitSubtestAnswers\SubmitSubtestAnswersUseCase;
use App\Interfaces\Http\Requests\SubmitSubtestAnswersRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class AssessmentController extends Controller
{
    public function __construct(
        private readonly StartAssessmentUseCase $startAssessmentUseCase,
        private readonly GetSubtestQuestionsUseCase $getSubtestQuestionsUseCase,
        private readonly SubmitSubtestAnswersUseCase $submitSubtestAnswersUseCase,
        private readonly GenerateReportUseCase $generateReportUseCase,
    ) {
    }

    /**
     * POST /api/assessments
     * アセスメントを開始する
     */
    public function start(): JsonResponse
    {
        $assessment = $this->startAssessmentUseCase->execute();

        return response()->json([
            'data'    => $assessment,
            'message' => 'アセスメントを開始しました。VCIから順に各サブテストを実施してください。',
        ], 201);
    }

    /**
     * GET /api/assessments/{assessmentId}/subtests/{subtestType}/questions
     * サブテストの問題を取得する
     */
    public function getQuestions(string $assessmentId, string $subtestType): JsonResponse
    {
        try {
            $result = $this->getSubtestQuestionsUseCase->execute($assessmentId, strtoupper($subtestType));

            return response()->json(['data' => $result]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\ValueError $e) {
            return response()->json(['error' => "Invalid subtest type: {$subtestType}"], 422);
        }
    }

    /**
     * POST /api/assessments/{assessmentId}/subtests/{subtestType}/answers
     * サブテストの回答を提出する
     */
    public function submitAnswers(
        SubmitSubtestAnswersRequest $request,
        string $assessmentId,
        string $subtestType,
    ): JsonResponse {
        try {
            $answerInputs = array_map(
                fn (array $a) => new AnswerInputDto(
                    questionId: $a['question_id'],
                    response: $a['response'] ?? '', // nullの場合は空文字列を使用
                    awardedScore: isset($a['awarded_score']) ? (float) $a['awarded_score'] : null,
                ),
                $request->validated('answers')
            );

            $input = new SubmitSubtestAnswersInput(
                assessmentId: $assessmentId,
                subtestType: strtoupper($subtestType),
                answers: $answerInputs,
                elapsedSeconds: $request->validated('elapsed_seconds'),
            );

            $assessment = $this->submitSubtestAnswersUseCase->execute($input);

            return response()->json([
                'data'    => $assessment,
                'message' => "サブテスト {$subtestType} の回答を受け付けました。",
            ]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\ValueError $e) {
            return response()->json(['error' => "Invalid subtest type: {$subtestType}"], 422);
        } catch (\Throwable $e) {
            \Log::error('submitAnswers error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['error' => '採点処理中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/assessments/{assessmentId}/report
     * 結果レポートを取得する
     */
    public function report(string $assessmentId): JsonResponse
    {
        try {
            $report = $this->generateReportUseCase->execute($assessmentId);

            return response()->json(['data' => $report]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
