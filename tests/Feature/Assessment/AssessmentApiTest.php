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

    public function test_submit_answers_requires_answers_field(): void
    {
        $assessmentId = $this->startAssessment();

        $response = $this->postJson("/api/assessments/{$assessmentId}/subtests/A/answers", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
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
