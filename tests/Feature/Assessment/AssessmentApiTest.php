<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Domain\Assessment\ValueObjects\SubtestType;
use App\Infrastructure\Persistence\Eloquent\Models\QuestionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AssessmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedQuestions();
    }

    public function test_start_assessment_returns_201(): void
    {
        $response = $this->postJson('/api/assessments');

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'statusLabel',
                    'completedSubtests',
                    'remainingSubtests',
                    'createdAt',
                    'completedAt',
                ],
                'message',
            ]);
    }

    public function test_start_assessment_has_in_progress_status(): void
    {
        $response = $this->postJson('/api/assessments');

        $response->assertJsonPath('data.status', 'in_progress');
    }

    public function test_start_assessment_has_4_remaining_subtests(): void
    {
        $response = $this->postJson('/api/assessments');

        $this->assertCount(4, $response->json('data.remainingSubtests'));
    }

    public function test_get_questions_for_verbal_organization_subtest(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->getJson("/api/assessments/{$assessmentId}/subtests/A/questions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'subtestType',
                    'subtestLabel',
                    'indexType',
                    'timeLimitSeconds',
                    'questions',
                    'instructions',
                ],
            ]);
    }

    public function test_get_questions_returns_6_for_verbal_organization(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->getJson("/api/assessments/{$assessmentId}/subtests/A/questions");

        $this->assertCount(6, $response->json('data.questions'));
    }

    public function test_get_questions_for_invalid_subtest_returns_422(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->getJson("/api/assessments/{$assessmentId}/subtests/Z/questions");

        $response->assertStatus(422);
    }

    public function test_submit_answers_for_structural_understanding_subtest(): void
    {
        $assessmentId = $this->startAssessment();
        $questions = $this->getQuestions($assessmentId, 'B');

        $answers = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'A'],
            $questions
        );

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answers", [
            'answers' => $answers,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_submit_answers_marks_subtest_as_completed(): void
    {
        $assessmentId = $this->startAssessment();
        $questions = $this->getQuestions($assessmentId, 'A');

        $answers = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '回答', 'awarded_score' => 1],
            $questions
        );

        $submitResponse = $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", [
            'answers' => $answers,
        ]);

        $submitResponse->assertStatus(200);
        $this->assertContains('A', $submitResponse->json('data.completedSubtests'));
    }

    public function test_submit_duplicate_subtest_returns_422(): void
    {
        $assessmentId = $this->startAssessment();
        $questions = $this->getQuestions($assessmentId, 'B');

        $answers = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'A'],
            $questions
        );

        $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answers", [
            'answers' => $answers,
        ]);

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answers", [
            'answers' => $answers,
        ]);

        $response->assertStatus(422);
    }

    public function test_report_cannot_be_generated_for_incomplete_assessment(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->getJson("/api/assessments/{$assessmentId}/report");

        $response->assertStatus(422);
    }

    public function test_invalid_assessment_id_returns_422(): void
    {
        $response = $this->getJson('/api/assessments/invalid-id/subtests/A/questions');

        $response->assertStatus(422);
    }

    public function test_submit_without_answers_completes_subtest(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", []);

        $response->assertStatus(200);
    }

    public function test_submit_answers_requires_valid_question_uuid(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", [
            'answers' => [
                ['question_id' => 'not-a-uuid', 'response' => 'test'],
            ],
        ]);

        $response->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // Per-question save (1問ずつ保存) tests
    // -----------------------------------------------------------------------

    public function test_save_single_answer_returns_200(): void
    {
        $assessmentId = $this->startAssessment();
        $questions = $this->getQuestions($assessmentId, 'A');

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answer", [
            'question_id'   => $questions[0]['id'],
            'response'      => 'テスト回答',
            'awarded_score' => 2,
        ]);

        $response->assertStatus(200);
    }

    public function test_per_question_save_then_complete_gives_correct_vci(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest A (言語整理): 1問ずつ保存 (全問2点)
        $questionsA = $this->getQuestions($assessmentId, 'A');
        foreach ($questionsA as $q) {
            $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answer", [
                'question_id'   => $q['id'],
                'response'      => '回答',
                'awarded_score' => 2,
            ])->assertStatus(200);
        }
        // 完了マーク (回答なし)
        $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", [])
            ->assertStatus(200);

        // 残りのサブテストを完了
        $this->completeRemainingSubtests($assessmentId, ['A']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // A: 6×2=12 → VCI = 12
        $vci = $this->findIndexScore($report->json('data.indexScores'), 'VCI');
        $this->assertEquals(12.0, $vci['rawScore'], 'VCI raw score should be 12 (per-question save)');
    }

    // -----------------------------------------------------------------------
    // End-to-end scoring pipeline tests
    // -----------------------------------------------------------------------

    public function test_vci_scoring_uses_user_awarded_score_for_free_text(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest A (言語整理 / FREE_TEXT) with full score
        $questionsA = $this->getQuestions($assessmentId, 'A');
        $answersA = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '要点回答', 'awarded_score' => 2],
            $questionsA
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", ['answers' => $answersA])
            ->assertStatus(200);

        $this->completeRemainingSubtests($assessmentId, ['A']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // VCI raw score = 6 questions × 2 pts = 12
        $vci = $this->findIndexScore($report->json('data.indexScores'), 'VCI');
        $this->assertEquals(12.0, $vci['rawScore'], 'VCI raw score should be 12 (full marks)');
    }

    public function test_vci_partial_awarded_score_is_preserved(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest A: alternate 2 and 1 points → 6 questions: 3×2 + 3×1 = 9
        $questionsA = $this->getQuestions($assessmentId, 'A');
        $answersA = [];
        foreach ($questionsA as $i => $q) {
            $answersA[] = [
                'question_id'  => $q['id'],
                'response'     => '回答',
                'awarded_score' => $i % 2 === 0 ? 2 : 1,
            ];
        }
        $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", ['answers' => $answersA])
            ->assertStatus(200);

        $this->completeRemainingSubtests($assessmentId, ['A']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // A: 3×2 + 3×1 = 9 → VCI = 9
        $vci = $this->findIndexScore($report->json('data.indexScores'), 'VCI');
        $this->assertEquals(9.0, $vci['rawScore'], 'VCI raw score should be 9 (partial scoring)');
    }

    public function test_wmi_scoring_auto_grades_sequence(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest C (保持操作 / SEQUENCE): correct answer is '123'
        $questionsC = $this->getQuestions($assessmentId, 'C');
        $answersC = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '123', 'awarded_score' => null],
            $questionsC
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/C/answers", ['answers' => $answersC])
            ->assertStatus(200);

        $this->completeRemainingSubtests($assessmentId, ['C']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // C: 6 correct × 1pt = 6 → WMI = 6
        $wmi = $this->findIndexScore($report->json('data.indexScores'), 'WMI');
        $this->assertEquals(6.0, $wmi['rawScore'], 'WMI raw score should be 6 (all correct)');
    }

    public function test_wmi_wrong_answers_score_zero(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest C: all wrong
        $questionsC = $this->getQuestions($assessmentId, 'C');
        $answersC = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '999', 'awarded_score' => null],
            $questionsC
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/C/answers", ['answers' => $answersC])
            ->assertStatus(200);

        $this->completeRemainingSubtests($assessmentId, ['C']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // All wrong → WMI = 0
        $wmi = $this->findIndexScore($report->json('data.indexScores'), 'WMI');
        $this->assertEquals(0.0, $wmi['rawScore'], 'WMI raw score should be 0 (all wrong)');
    }

    public function test_complete_assessment_full_scores(): void
    {
        $assessmentId = $this->startAssessment();

        // A: 言語整理 (FREE_TEXT, 6q × 2pts = 12)
        $questionsA = $this->getQuestions($assessmentId, 'A');
        $answersA = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '最高得点回答', 'awarded_score' => 2],
            $questionsA
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", ['answers' => $answersA])
            ->assertStatus(200);

        // B: 構造理解 (MULTIPLE_CHOICE, correct='B', 6q × 1pt = 6)
        $questionsB = $this->getQuestions($assessmentId, 'B');
        $answersB = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'B', 'awarded_score' => null],
            $questionsB
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answers", ['answers' => $answersB])
            ->assertStatus(200);

        // C: 保持操作 (SEQUENCE, correct='123', 6q × 1pt = 6)
        $questionsC = $this->getQuestions($assessmentId, 'C');
        $answersC = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '123', 'awarded_score' => null],
            $questionsC
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/C/answers", ['answers' => $answersC])
            ->assertStatus(200);

        // D: 速度耐性 (TIME_BASED, 6q)
        $questionsD = $this->getQuestions($assessmentId, 'D');
        $answersD = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '○', 'awarded_score' => null],
            $questionsD
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/D/answers", [
            'answers' => $answersD, 'elapsed_seconds' => 60,
        ])->assertStatus(200);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        $indexScores = $report->json('data.indexScores');

        // VCI: 6×2 = 12 (max 12)
        $vci = $this->findIndexScore($indexScores, 'VCI');
        $this->assertEquals(12.0, $vci['rawScore'], 'VCI should be 12 (perfect score)');
        $this->assertEquals(12, $vci['maxScore'], 'VCI max score should be 12');
        $this->assertEquals(100.0, $vci['percentage'], 'VCI percentage should be 100%');

        // WMI: 6×1 = 6 (max 6)
        $wmi = $this->findIndexScore($indexScores, 'WMI');
        $this->assertEquals(6.0, $wmi['rawScore'], 'WMI should be 6 (perfect score)');
        $this->assertEquals(6, $wmi['maxScore'], 'WMI max score should be 6');
        $this->assertEquals(100.0, $wmi['percentage'], 'WMI percentage should be 100%');
    }

    /**
     * Complete subtests not in $alreadyCompleted with passing answers.
     *
     * @param array<string> $alreadyCompleted
     */
    private function completeRemainingSubtests(string $assessmentId, array $alreadyCompleted): void
    {
        $defaultAnswers = [
            'A' => ['response' => '回答', 'awarded_score' => 1],
            'B' => ['response' => 'B', 'awarded_score' => null],
            'C' => ['response' => '123', 'awarded_score' => null],
            'D' => ['response' => '○', 'awarded_score' => null],
        ];

        foreach ($defaultAnswers as $subtest => $defaults) {
            if (in_array($subtest, $alreadyCompleted, true)) {
                continue;
            }

            $questions = $this->getQuestions($assessmentId, $subtest);
            $answers = array_map(
                fn ($q) => array_merge(['question_id' => $q['id']], $defaults),
                $questions
            );

            $payload = ['answers' => $answers];
            if ($subtest === 'D') {
                $payload['elapsed_seconds'] = 60;
            }

            $this->postJson("/api/assessments/{$assessmentId}/subtests/{$subtest}/answers", $payload)
                ->assertStatus(200);
        }
    }

    /**
     * Find a specific index score by its indexType key.
     *
     * @param array<int, array<string, mixed>> $indexScores
     * @return array<string, mixed>
     */
    private function findIndexScore(array $indexScores, string $indexType): array
    {
        foreach ($indexScores as $score) {
            if ($score['indexType'] === $indexType) {
                return $score;
            }
        }
        $this->fail("IndexScore for '{$indexType}' not found in report.");
    }

    private function startAssessment(): string
    {
        $response = $this->postJson('/api/assessments');

        return $response->json('data.id');
    }

    /** @return array<int, array<string, mixed>> */
    private function getQuestions(string $assessmentId, string $subtestType): array
    {
        $response = $this->getJson("/api/assessments/{$assessmentId}/subtests/{$subtestType}/questions");

        return $response->json('data.questions');
    }

    private function seedQuestions(): void
    {
        $subtestData = [
            // A: 言語整理 (24 pool, free_text, max 2 pts)
            'A' => ['count' => 24, 'type' => 'free_text', 'max_points' => 2, 'correct' => '正解'],
            // B: 構造理解 (24 pool, multiple_choice, max 1 pt)
            'B' => ['count' => 24, 'type' => 'multiple_choice', 'max_points' => 1, 'correct' => 'B'],
            // C: 保持操作 (24 pool, sequence, max 1 pt)
            'C' => ['count' => 24, 'type' => 'sequence', 'max_points' => 1, 'correct' => '123'],
            // D: 速度耐性 (24 pool, time_based, max 1 pt)
            'D' => ['count' => 24, 'type' => 'time_based', 'max_points' => 1, 'correct' => '○'],
        ];

        foreach ($subtestData as $subtestType => $data) {
            for ($i = 1; $i <= $data['count']; $i++) {
                QuestionModel::create([
                    'id'              => (string) Str::uuid(),
                    'subtest_type'    => $subtestType,
                    'sequence_number' => $i,
                    'content'         => "テスト問題 {$subtestType}-{$i}",
                    'question_type'   => $data['type'],
                    'correct_answer'  => $data['correct'],
                    'options'         => $data['type'] === 'multiple_choice'
                        ? ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D']
                        : null,
                    'max_points'      => $data['max_points'],
                    'hint'            => null,
                ]);
            }
        }
    }
}
