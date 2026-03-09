<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Services;

use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Entities\Question;
use App\Domain\Assessment\Services\ScoringDomainService;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\IndexType;
use App\Domain\Assessment\ValueObjects\QuestionId;
use App\Domain\Assessment\ValueObjects\QuestionType;
use App\Domain\Assessment\ValueObjects\Score;
use App\Domain\Assessment\ValueObjects\SubtestType;
use PHPUnit\Framework\TestCase;

final class ScoringDomainServiceTest extends TestCase
{
    private ScoringDomainService $service;

    protected function setUp(): void
    {
        $this->service = new ScoringDomainService();
    }

    public function test_grade_multiple_choice_correct_answer(): void
    {
        $question = $this->createQuestion(QuestionType::MULTIPLE_CHOICE, 'B');
        $answer = $this->createAnswer($question, 'B');

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(1.0, $score->getValue());
    }

    public function test_grade_multiple_choice_wrong_answer(): void
    {
        $question = $this->createQuestion(QuestionType::MULTIPLE_CHOICE, 'B');
        $answer = $this->createAnswer($question, 'A');

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(0.0, $score->getValue());
    }

    public function test_grade_similarity_answer_with_high_level_keyword(): void
    {
        $question = new Question(
            id: QuestionId::generate(),
            subtestType: SubtestType::SIMILARITIES,
            sequenceNumber: 1,
            content: 'リンゴとバナナの共通点は何ですか？',
            questionType: QuestionType::FREE_TEXT,
            correctAnswer: '果物',
            maxPoints: 2,
            hint: '食べ物の種類を考えてください',
        );

        $answer = $this->createAnswer($question, '果物');

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(1.0, $score->getValue());
    }

    public function test_grade_similarity_answer_with_detailed_response(): void
    {
        $question = new Question(
            id: QuestionId::generate(),
            subtestType: SubtestType::SIMILARITIES,
            sequenceNumber: 1,
            content: '犬と猫の共通点は何ですか？',
            questionType: QuestionType::FREE_TEXT,
            correctAnswer: 'ペット',
            maxPoints: 2,
        );

        $answer = $this->createAnswer($question, 'ペット');

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(1.0, $score->getValue());
    }

    public function test_grade_similarity_answer_with_short_response(): void
    {
        $question = new Question(
            id: QuestionId::generate(),
            subtestType: SubtestType::SIMILARITIES,
            sequenceNumber: 1,
            content: '太陽と月の共通点は何ですか？',
            questionType: QuestionType::FREE_TEXT,
            correctAnswer: '天体',
            maxPoints: 2,
        );

        $answer = $this->createAnswer($question, '天体');

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(1.0, $score->getValue());
    }

    public function test_grade_similarity_answer_empty_response(): void
    {
        $question = new Question(
            id: QuestionId::generate(),
            subtestType: SubtestType::SIMILARITIES,
            sequenceNumber: 1,
            content: '車と自転車の共通点は何ですか？',
            questionType: QuestionType::FREE_TEXT,
            correctAnswer: null,
            maxPoints: 2,
        );

        $answer = $this->createAnswer($question, '');

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(0.0, $score->getValue());
    }

    public function test_grade_similarity_answer_too_short(): void
    {
        $question = new Question(
            id: QuestionId::generate(),
            subtestType: SubtestType::SIMILARITIES,
            sequenceNumber: 1,
            content: '本と新聞の共通点は何ですか？',
            questionType: QuestionType::FREE_TEXT,
            correctAnswer: null,
            maxPoints: 2,
        );

        $answer = $this->createAnswer($question, '紙');

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(0.0, $score->getValue());
    }

    public function test_calculate_index_score_for_vci(): void
    {
        $subtestScores = [
            'A' => new Score(15.0), // SIMILARITIES
            'B' => new Score(18.0), // VOCABULARY
        ];

        $score = $this->service->calculateIndexScore(IndexType::VCI, $subtestScores);

        $this->assertSame(33.0, $score->getValue());
    }

    public function test_percentage_level_returns_correct_level(): void
    {
        $this->assertSame('かなり弱い', $this->service->percentageLevel(10.0));
        $this->assertSame('弱め', $this->service->percentageLevel(30.0));
        $this->assertSame('平均域', $this->service->percentageLevel(50.0));
        $this->assertSame('強め', $this->service->percentageLevel(70.0));
        $this->assertSame('かなり強い', $this->service->percentageLevel(90.0));
    }

    public function test_calculate_subtest_score_sums_awarded_scores(): void
    {
        $questions = [
            $this->createQuestion(QuestionType::FREE_TEXT, '正解1', 2),
            $this->createQuestion(QuestionType::FREE_TEXT, '正解2', 2),
        ];

        $answers = [
            new Answer(
                questionId: $questions[0]->getId(),
                assessmentId: AssessmentId::generate(),
                response: '回答1',
                awardedScore: new Score(2.0),
            ),
            new Answer(
                questionId: $questions[1]->getId(),
                assessmentId: AssessmentId::generate(),
                response: '回答2',
                awardedScore: new Score(1.0),
            ),
        ];

        $score = $this->service->calculateSubtestScore(SubtestType::SIMILARITIES, $answers, $questions);

        $this->assertSame(3.0, $score->getValue());
    }

    private function createQuestion(QuestionType $type, string $correctAnswer, int $maxPoints = 1): Question
    {
        return new Question(
            id: QuestionId::generate(),
            subtestType: SubtestType::SIMILARITIES,
            sequenceNumber: 1,
            content: 'テスト問題',
            questionType: $type,
            correctAnswer: $correctAnswer,
            maxPoints: $maxPoints,
        );
    }

    private function createAnswer(Question $question, string $response): Answer
    {
        return new Answer(
            questionId: $question->getId(),
            assessmentId: AssessmentId::generate(),
            response: $response,
            awardedScore: Score::zero(),
        );
    }
}
