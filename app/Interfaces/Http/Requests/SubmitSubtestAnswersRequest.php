<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitSubtestAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'answers'                    => ['required', 'array', 'min:1'],
            'answers.*.question_id'      => ['required', 'string', 'uuid'],
            'answers.*.response'         => ['required', 'string'],
            'answers.*.awarded_score'    => ['nullable', 'numeric', 'min:0', 'max:2'],
            'elapsed_seconds'            => ['nullable', 'integer', 'min:0', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'answers.required'               => '回答データが必要です。',
            'answers.array'                  => '回答はリスト形式で送信してください。',
            'answers.*.question_id.required' => '問題IDが必要です。',
            'answers.*.question_id.uuid'     => '問題IDはUUID形式で指定してください。',
            'answers.*.response.required'    => '回答内容が必要です。',
            'answers.*.awarded_score.min'    => '採点スコアは0以上で入力してください。',
            'answers.*.awarded_score.max'    => '採点スコアは2以下で入力してください。',
        ];
    }
}
