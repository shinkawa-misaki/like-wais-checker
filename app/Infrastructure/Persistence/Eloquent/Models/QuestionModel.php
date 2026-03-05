<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class QuestionModel extends Model
{
    protected $table = 'questions';

    public $incrementing = false;
    protected $keyType = 'string';

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

    protected $casts = [
        'options' => 'array',
        'max_points' => 'integer',
        'sequence_number' => 'integer',
    ];
}

