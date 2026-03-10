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

    public function test_start_assessment_has_8_remaining_subtests(): void
    {
        $response = $this->postJson('/api/assessments');

        $this->assertCount(8, $response->json('data.remainingSubtests'));
    }

    public function test_get_questions_for_similarities_subtest(): void
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

    public function test_get_questions_returns_10_for_similarities(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->getJson("/api/assessments/{$assessmentId}/subtests/A/questions");

        $this->assertCount(10, $response->json('data.questions'));
    }

    public function test_get_questions_for_invalid_subtest_returns_422(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->getJson("/api/assessments/{$assessmentId}/subtests/Z/questions");

        $response->assertStatus(422);
    }

    public function test_submit_answers_for_pattern_recognition_subtest(): void
    {
        $assessmentId = $this->startAssessment();
        $questions = $this->getQuestions($assessmentId, 'C');

        $answers = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'A'],
            $questions
        );

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/C/answers", [
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
            fn ($q) => ['question_id' => $q['id'], 'response' => '動物', 'awarded_score' => 1],
            $questions
        );

        $submitResponse = $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", [
            'answers' => $answers,
        ]);

        $submitResponse->assertStatus(200);
        // Subtest A should now be in completed list
        $this->assertContains('A', $submitResponse->json('data.completedSubtests'));
    }

    public function test_submit_duplicate_subtest_returns_422(): void
    {
        $assessmentId = $this->startAssessment();
        $questions = $this->getQuestions($assessmentId, 'C');

        $answers = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'A'],
            $questions
        );

        $this->postJson("/api/assessments/{$assessmentId}/subtests/C/answers", [
            'answers' => $answers,
        ]);

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/C/answers", [
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

        // 回答なしでもサブテスト完了マークとして200が返る
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

        // Subtest A: 1問ずつ保存 (全問2点)
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

        // Subtest B: 1問ずつ保存 (全問1点)
        $questionsB = $this->getQuestions($assessmentId, 'B');
        foreach ($questionsB as $q) {
            $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answer", [
                'question_id'   => $q['id'],
                'response'      => '回答',
                'awarded_score' => 1,
            ])->assertStatus(200);
        }
        $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answers", [])
            ->assertStatus(200);

        // 残りのサブテストを完了
        $this->completeRemainingSubtests($assessmentId, ['A', 'B']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // A: 10×2=20, B: 10×1=10 → VCI = 30
        $vci = $this->findIndexScore($report->json('data.indexScores'), 'VCI');
        $this->assertEquals(30.0, $vci['rawScore'], 'VCI raw score should be 30 (per-question save)');
    }

    // -----------------------------------------------------------------------
    // End-to-end scoring pipeline tests for VCI (言語理解) and WMI (ワーキングメモリ)
    // -----------------------------------------------------------------------

    public function test_vci_scoring_uses_user_awarded_score_for_free_text(): void
    {
        $assessmentId = $this->startAssessment();

        // Submit subtest A (SIMILARITIES / FREE_TEXT) with full score
        $questionsA = $this->getQuestions($assessmentId, 'A');
        $answersA = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '動物', 'awarded_score' => 2],
            $questionsA
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", ['answers' => $answersA])
            ->assertStatus(200);

        // Submit subtest B (VOCABULARY / FREE_TEXT) with full score
        $questionsB = $this->getQuestions($assessmentId, 'B');
        $answersB = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '物体', 'awarded_score' => 2],
            $questionsB
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answers", ['answers' => $answersB])
            ->assertStatus(200);

        // Complete remaining subtests to allow report generation
        $this->completeRemainingSubtests($assessmentId, ['A', 'B']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // VCI raw score = 10 questions × 2 pts (A) + 10 questions × 2 pts (B) = 40
        $vci = $this->findIndexScore($report->json('data.indexScores'), 'VCI');
        $this->assertEquals(40.0, $vci['rawScore'], 'VCI raw score should be 40 (full marks)');
    }

    public function test_vci_partial_awarded_score_is_preserved(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest A: alternate 2 and 1 points → 10 questions: 5×2 + 5×1 = 15
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

        // Subtest B: all zero points
        $questionsB = $this->getQuestions($assessmentId, 'B');
        $answersB = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '不正解', 'awarded_score' => 0],
            $questionsB
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/B/answers", ['answers' => $answersB])
            ->assertStatus(200);

        $this->completeRemainingSubtests($assessmentId, ['A', 'B']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // A: 5×2 + 5×1 = 15, B: 0 → VCI = 15
        $vci = $this->findIndexScore($report->json('data.indexScores'), 'VCI');
        $this->assertEquals(15.0, $vci['rawScore'], 'VCI raw score should be 15 (partial scoring)');
    }

    public function test_wmi_scoring_auto_grades_sequence_and_multiple_choice(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest E (DIGIT_SPAN / SEQUENCE): correct answer is '123'
        $questionsE = $this->getQuestions($assessmentId, 'E');
        $answersE = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '123', 'awarded_score' => null],
            $questionsE
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/E/answers", ['answers' => $answersE])
            ->assertStatus(200);

        // Subtest F (ARITHMETIC / MULTIPLE_CHOICE): correct answer is 'A'
        $questionsF = $this->getQuestions($assessmentId, 'F');
        $answersF = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'A', 'awarded_score' => null],
            $questionsF
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/F/answers", ['answers' => $answersF])
            ->assertStatus(200);

        $this->completeRemainingSubtests($assessmentId, ['E', 'F']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // E: 18 correct × 1pt = 18, F: 10 correct × 1pt = 10 → WMI = 28
        $wmi = $this->findIndexScore($report->json('data.indexScores'), 'WMI');
        $this->assertEquals(28.0, $wmi['rawScore'], 'WMI raw score should be 28 (all correct)');
    }

    public function test_wmi_wrong_answers_score_zero(): void
    {
        $assessmentId = $this->startAssessment();

        // Subtest E: all wrong (response does not match correct answer '123')
        $questionsE = $this->getQuestions($assessmentId, 'E');
        $answersE = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '999', 'awarded_score' => null],
            $questionsE
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/E/answers", ['answers' => $answersE])
            ->assertStatus(200);

        // Subtest F: all wrong (response does not match correct answer 'A')
        $questionsF = $this->getQuestions($assessmentId, 'F');
        $answersF = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'D', 'awarded_score' => null],
            $questionsF
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/F/answers", ['answers' => $answersF])
            ->assertStatus(200);

        $this->completeRemainingSubtests($assessmentId, ['E', 'F']);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        // All wrong → WMI = 0
        $wmi = $this->findIndexScore($report->json('data.indexScores'), 'WMI');
        $this->assertEquals(0.0, $wmi['rawScore'], 'WMI raw score should be 0 (all wrong)');
    }

    public function test_complete_assessment_full_vci_and_wmi_scores(): void
    {
        $assessmentId = $this->startAssessment();

        // VCI: A (FREE_TEXT, 10 questions × 2pts) + B (FREE_TEXT, 10 questions × 2pts)
        foreach (['A', 'B'] as $subtest) {
            $questions = $this->getQuestions($assessmentId, $subtest);
            $answers = array_map(
                fn ($q) => ['question_id' => $q['id'], 'response' => '最高得点回答', 'awarded_score' => 2],
                $questions
            );
            $this->postJson("/api/assessments/{$assessmentId}/subtests/{$subtest}/answers", ['answers' => $answers])
                ->assertStatus(200);
        }

        // PRI: C (MULTIPLE_CHOICE correct='B', 12q) + D (MULTIPLE_CHOICE correct='B', 10q)
        foreach (['C', 'D'] as $subtest) {
            $questions = $this->getQuestions($assessmentId, $subtest);
            $answers = array_map(
                fn ($q) => ['question_id' => $q['id'], 'response' => 'B', 'awarded_score' => null],
                $questions
            );
            $this->postJson("/api/assessments/{$assessmentId}/subtests/{$subtest}/answers", ['answers' => $answers])
                ->assertStatus(200);
        }

        // WMI: E (SEQUENCE correct='123', 18q) + F (MULTIPLE_CHOICE correct='A', 10q)
        $questionsE = $this->getQuestions($assessmentId, 'E');
        $answersE = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '123', 'awarded_score' => null],
            $questionsE
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/E/answers", ['answers' => $answersE])
            ->assertStatus(200);

        $questionsF = $this->getQuestions($assessmentId, 'F');
        $answersF = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'A', 'awarded_score' => null],
            $questionsF
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/F/answers", ['answers' => $answersF])
            ->assertStatus(200);

        // PSI: G (TIME_BASED / Symbol Search) + H (TIME_BASED / Coding)
        $questionsG = $this->getQuestions($assessmentId, 'G');
        $answersG = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => '○', 'awarded_score' => null],
            $questionsG
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/G/answers", [
            'answers' => $answersG, 'elapsed_seconds' => 120,
        ])->assertStatus(200);

        $questionsH = $this->getQuestions($assessmentId, 'H');
        $answersH = array_map(
            fn ($q) => ['question_id' => $q['id'], 'response' => 'A', 'awarded_score' => null],
            $questionsH
        );
        $this->postJson("/api/assessments/{$assessmentId}/subtests/H/answers", [
            'answers' => $answersH, 'elapsed_seconds' => 120,
        ])->assertStatus(200);

        $report = $this->getJson("/api/assessments/{$assessmentId}/report")->assertStatus(200);

        $indexScores = $report->json('data.indexScores');

        // VCI: 10×2 + 10×2 = 40 (max 40)
        $vci = $this->findIndexScore($indexScores, 'VCI');
        $this->assertEquals(40.0, $vci['rawScore'], 'VCI should be 40 (perfect score)');
        $this->assertEquals(40, $vci['maxScore'], 'VCI max score should be 40');
        $this->assertEquals(100.0, $vci['percentage'], 'VCI percentage should be 100%');

        // WMI: 18×1 + 10×1 = 28 (max 28)
        $wmi = $this->findIndexScore($indexScores, 'WMI');
        $this->assertEquals(28.0, $wmi['rawScore'], 'WMI should be 28 (perfect score)');
        $this->assertEquals(28, $wmi['maxScore'], 'WMI max score should be 28');
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
            'B' => ['response' => '回答', 'awarded_score' => 1],
            'C' => ['response' => 'B', 'awarded_score' => null],
            'D' => ['response' => 'B', 'awarded_score' => null],
            'E' => ['response' => '123', 'awarded_score' => null],
            'F' => ['response' => 'A', 'awarded_score' => null],
            'G' => ['response' => '○', 'awarded_score' => null],
            'H' => ['response' => 'A', 'awarded_score' => null],
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
            if (in_array($subtest, ['G', 'H'], true)) {
                $payload['elapsed_seconds'] = 120;
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
            // A: Similarities (10 questions, free_text, max 2 pts)
            'A' => ['count' => 10, 'type' => 'free_text', 'max_points' => 2, 'correct' => '正解'],
            // B: Vocabulary (10 questions, free_text, max 2 pts)
            'B' => ['count' => 10, 'type' => 'free_text', 'max_points' => 2, 'correct' => '正解'],
            // C: Pattern Recognition (12 questions, multiple_choice)
            'C' => ['count' => 12, 'type' => 'multiple_choice', 'max_points' => 1, 'correct' => 'B'],
            // D: Matrix Reasoning (10 questions, multiple_choice)
            'D' => ['count' => 10, 'type' => 'multiple_choice', 'max_points' => 1, 'correct' => 'B'],
            // E: Digit Span (18 sequences, sequence)
            'E' => ['count' => 18, 'type' => 'sequence', 'max_points' => 1, 'correct' => '123'],
            // F: Arithmetic (10 questions, multiple_choice)
            'F' => ['count' => 10, 'type' => 'multiple_choice', 'max_points' => 1, 'correct' => 'A'],
            // G: Symbol Search (24 questions, time_based)
            'G' => ['count' => 24, 'type' => 'time_based', 'max_points' => 1, 'correct' => '○'],
            // H: Coding (60 questions, time_based)
            'H' => ['count' => 60, 'type' => 'time_based', 'max_points' => 1, 'correct' => 'A'],
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
