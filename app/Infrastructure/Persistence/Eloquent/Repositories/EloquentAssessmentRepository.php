<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Assessment\Entities\Answer;
use App\Domain\Assessment\Entities\Assessment;
use App\Domain\Assessment\Repositories\AssessmentRepositoryInterface;
use App\Domain\Assessment\ValueObjects\AssessmentId;
use App\Domain\Assessment\ValueObjects\AssessmentStatus;
use App\Domain\Assessment\ValueObjects\QuestionId;
use App\Domain\Assessment\ValueObjects\Score;
use App\Infrastructure\Persistence\Eloquent\Models\AnswerModel;
use App\Infrastructure\Persistence\Eloquent\Models\AssessmentModel;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class EloquentAssessmentRepository implements AssessmentRepositoryInterface
{
    public function save(Assessment $assessment): void
    {
        $data = [
            'id'                 => $assessment->getId()->getValue(),
            'status'             => $assessment->getStatus()->value,
            'completed_subtests' => $assessment->getCompletedSubtests(),
            'created_at'         => $assessment->getCreatedAt()->format('Y-m-d H:i:s'),
            'completed_at'       => $assessment->getCompletedAt()?->format('Y-m-d H:i:s'),
        ];

        AssessmentModel::updateOrCreate(['id' => $data['id']], $data);
    }

    public function saveAnswer(Answer $answer): void
    {
        $model = AnswerModel::where('assessment_id', $answer->getAssessmentId()->getValue())
            ->where('question_id', $answer->getQuestionId()->getValue())
            ->first();

        if ($model !== null) {
            $model->update([
                'response'      => $answer->getResponse(),
                'awarded_score' => $answer->getAwardedScore()->getValue(),
            ]);
        } else {
            AnswerModel::create([
                'id'            => (string) Str::uuid(),
                'assessment_id' => $answer->getAssessmentId()->getValue(),
                'question_id'   => $answer->getQuestionId()->getValue(),
                'response'      => $answer->getResponse(),
                'awarded_score' => $answer->getAwardedScore()->getValue(),
            ]);
        }
    }

    public function findById(AssessmentId $id): ?Assessment
    {
        $model = AssessmentModel::with('answers')->find($id->getValue());

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    private function toDomainEntity(AssessmentModel $model): Assessment
    {
        $answers = $model->answers->map(
            fn (AnswerModel $a) => new Answer(
                questionId: new QuestionId($a->question_id),
                assessmentId: new AssessmentId($a->assessment_id),
                response: $a->response,
                awardedScore: new Score($a->awarded_score),
            )
        )->all();

        return new Assessment(
            id: new AssessmentId($model->id),
            status: AssessmentStatus::from($model->status),
            createdAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
            completedAt: $model->completed_at !== null
                ? new DateTimeImmutable($model->completed_at->toDateTimeString())
                : null,
            completedSubtests: $model->completed_subtests ?? [],
            answers: $answers,
        );
    }
}
