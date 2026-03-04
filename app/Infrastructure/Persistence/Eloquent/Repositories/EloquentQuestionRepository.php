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
        $models = QuestionModel::where('subtest_type', $subtestType->value)
            ->orderBy('sequence_number')
            ->get();

        return $models->map(fn (QuestionModel $m) => $this->toDomainEntity($m))->all();
    }

    private function toDomainEntity(QuestionModel $model): Question
    {
        return new Question(
            id: new QuestionId($model->id),
            subtestType: SubtestType::from($model->subtest_type),
            sequenceNumber: $model->sequence_number,
            content: $model->content,
            questionType: QuestionType::from($model->question_type),
            correctAnswer: $model->correct_answer,
            options: $model->options,
            maxPoints: $model->max_points,
            hint: $model->hint,
        );
    }
}
