<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $status
 * @property array<string> $completed_subtests
 * @property Carbon $created_at
 * @property Carbon|null $completed_at
 * @property Collection<int, AnswerModel> $answers
 */
class AssessmentModel extends Model
{
    protected $table = 'assessments';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'completed_subtests' => 'array',
        'created_at'         => 'datetime',
        'completed_at'       => 'datetime',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'completed_subtests' => '[]',
    ];

    /** @var list<string> */
    protected $fillable = [
        'id',
        'status',
        'completed_subtests',
        'created_at',
        'completed_at',
    ];

    /** @return HasMany<AnswerModel, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(AnswerModel::class, 'assessment_id', 'id');
    }
}
