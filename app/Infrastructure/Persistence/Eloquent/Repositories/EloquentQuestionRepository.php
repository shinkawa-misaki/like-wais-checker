<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Assessment\Entities\Question;
use App\Domain\Assessment\Repositories\QuestionRepositoryInterface;
use App\Domain\Assessment\ValueObjects\QuestionId;
use App\Domain\Assessment\ValueObjects\QuestionType;
use App\Domain\Assessment\ValueObjects\SubtestType;
use App\Infrastructure\Persistence\Eloquent\Models\QuestionModel;

final class EloquentQuestionRepository implements QuestionRepositoryInterface
{
    /** @return array<Question> */
    public function findBySubtestType(SubtestType $subtestType): array
    {
        // 各サブテストで必要な問題数を取得
        $requiredCount = $subtestType->questionCount();

        // データベースから該当するサブテストタイプの全問題を取得
        $models = QuestionModel::where('subtest_type', $subtestType->value)
            ->get()
            ->shuffle() // Collectionのshuffleメソッドでランダム化
            ->take($requiredCount); // 必要数だけ取得

        // sequence_numberを再割り当て
        $questions = [];
        foreach ($models as $index => $model) {
            $questions[] = $this->toDomainEntity($model, $index + 1);
        }

        return $questions;
    }

    private function toDomainEntity(QuestionModel $model, int $sequenceNumber): Question
    {
        return new Question(
            id: new QuestionId($model->id),
            subtestType: SubtestType::from($model->subtest_type),
            sequenceNumber: $sequenceNumber,
            content: $model->content,
            questionType: QuestionType::from($model->question_type),
            correctAnswer: $model->correct_answer,
            options: $model->options,
            maxPoints: $model->max_points,
            hint: $model->hint,
        );
    }
}
