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

    public function test_grade_free_text_uses_awarded_score(): void
    {
        $question = $this->createQuestion(QuestionType::FREE_TEXT, '正解');
        $answer = new Answer(
            questionId: $question->getId(),
            assessmentId: AssessmentId::generate(),
            response: '部分的な回答',
            awardedScore: new Score(1.0),
        );

        $score = $this->service->gradeAnswer($question, $answer);

        $this->assertSame(1.0, $score->getValue());
    }

    public function test_symbol_search_score_with_all_correct(): void
    {
        $questions = [
            $this->createQuestion(QuestionType::TIME_BASED, '○'),
            $this->createQuestion(QuestionType::TIME_BASED, '×'),
        ];

        $answers = [
            $this->createAnswer($questions[0], '○'),
            $this->createAnswer($questions[1], '×'),
        ];

        $score = $this->service->calculateSymbolSearchScore($answers, $questions);

        $this->assertSame(2.0, $score->getValue());
    }

    public function test_symbol_search_score_with_penalty(): void
    {
        $questions = [
            $this->createQuestion(QuestionType::TIME_BASED, '○'),
            $this->createQuestion(QuestionType::TIME_BASED, '×'),
            $this->createQuestion(QuestionType::TIME_BASED, '○'),
        ];

        // 2正解、1誤答 → 2 - (1 × 0.5) = 1.5
        $answers = [
            $this->createAnswer($questions[0], '○'),
            $this->createAnswer($questions[1], '○'), // wrong
            $this->createAnswer($questions[2], '○'),
        ];

        $score = $this->service->calculateSymbolSearchScore($answers, $questions);

        $this->assertSame(1.5, $score->getValue());
    }

    public function test_symbol_search_score_never_goes_below_zero(): void
    {
        $questions = [
            $this->createQuestion(QuestionType::TIME_BASED, '○'),
            $this->createQuestion(QuestionType::TIME_BASED, '×'),
        ];

        // 0正解、2誤答 → 0 - (2 × 0.5) = -1 → 0
        $answers = [
            $this->createAnswer($questions[0], '×'),
            $this->createAnswer($questions[1], '○'),
        ];

        $score = $this->service->calculateSymbolSearchScore($answers, $questions);

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
