<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $assessment_id
 * @property string $question_id
 * @property string $response
 * @property float $awarded_score
 * @property string $created_at
 */
class AnswerModel extends Model
{
    protected $table = 'answers';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'awarded_score' => 'float',
        'created_at'    => 'datetime',
    ];

    /** @var list<string> */
    protected $fillable = [
        'id',
        'assessment_id',
        'question_id',
        'response',
        'awarded_score',
        'created_at',
    ];

    /** @return BelongsTo<AssessmentModel, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(AssessmentModel::class, 'assessment_id', 'id');
    }
}
