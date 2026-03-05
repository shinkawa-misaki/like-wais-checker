<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// テストデータ
$assessmentId = \App\Domain\Assessment\ValueObjects\AssessmentId::generate()->getValue();
$subtestType = 'A';

// アセスメントを作成
$assessment = new \App\Domain\Assessment\Entities\Assessment(
    id: new \App\Domain\Assessment\ValueObjects\AssessmentId($assessmentId),
    createdAt: new DateTime()
);

$repo = app(\App\Domain\Assessment\Repositories\AssessmentRepositoryInterface::class);
$repo->save($assessment);

// 問題を取得
$questionRepo = app(\App\Domain\Assessment\Repositories\QuestionRepositoryInterface::class);
$questions = $questionRepo->findBySubtestType(\App\Domain\Assessment\ValueObjects\SubtestType::SIMILARITIES);

if (empty($questions)) {
    echo "Error: No questions found\n";
    exit(1);
}

$firstQuestion = $questions[0];

// 回答データ
$answersData = [
    [
        'question_id' => $firstQuestion->getId()->getValue(),
        'response' => '果物',
    ]
];

echo "Assessment ID: {$assessmentId}\n";
echo "Question ID: {$firstQuestion->getId()->getValue()}\n";
echo "Subtest Type: {$subtestType}\n";
echo "Answers Data: " . json_encode($answersData, JSON_PRETTY_PRINT) . "\n\n";

// UseCaseを実行
try {
    $useCase = app(\App\Application\Assessment\UseCases\SubmitSubtestAnswers\SubmitSubtestAnswersUseCase::class);

    $answerInputs = array_map(
        fn (array $a) => new \App\Application\Assessment\DTOs\AnswerInputDto(
            questionId: $a['question_id'],
            response: $a['response'],
            awardedScore: null,
        ),
        $answersData
    );

    $input = new \App\Application\Assessment\UseCases\SubmitSubtestAnswers\SubmitSubtestAnswersInput(
        assessmentId: $assessmentId,
        subtestType: $subtestType,
        answers: $answerInputs,
        elapsedSeconds: null,
    );

    $result = $useCase->execute($input);

    echo "Success!\n";
    echo "Completed Subtests: " . json_encode($result->completedSubtests) . "\n";
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
}

