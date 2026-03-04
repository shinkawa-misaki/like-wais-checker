<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $subtest_type
 * @property int $sequence_number
 * @property string $content
 * @property string $question_type
 * @property string $correct_answer
 * @property array<string>|null $options
 * @property int $max_points
 * @property string|null $hint
 */
class QuestionModel extends Model
{
    protected $table = 'questions';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'options'    => 'array',
        'max_points' => 'integer',
    ];

    /** @var list<string> */
    protected $fillable = [
        'id',
        'subtest_type',
        'sequence_number',
        'content',
        'question_type',
        'correct_answer',
        'options',
        'max_points',
        'hint',
    ];
}
