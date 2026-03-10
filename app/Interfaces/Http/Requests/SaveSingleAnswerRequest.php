<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveSingleAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'question_id'    => ['required', 'string', 'uuid'],
            'response'       => ['nullable', 'string'],
            'awarded_score'  => ['nullable', 'numeric', 'min:0', 'max:2'],
        ];
    }
}
