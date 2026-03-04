<?php

declare(strict_types=1);

use App\Interfaces\Http\Controllers\AssessmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WAIS風・4指数ミニチェック API ルート
|--------------------------------------------------------------------------
*/

Route::prefix('assessments')->group(function (): void {
    // アセスメント開始
    Route::post('/', [AssessmentController::class, 'start'])
        ->name('assessments.start');

    Route::prefix('{assessmentId}')->group(function (): void {
        // サブテスト問題取得
        Route::get('subtests/{subtestType}/questions', [AssessmentController::class, 'getQuestions'])
            ->name('assessments.subtests.questions');

        // サブテスト回答提出
        Route::post('subtests/{subtestType}/answers', [AssessmentController::class, 'submitAnswers'])
            ->name('assessments.subtests.answers');

        // 結果レポート取得
        Route::get('report', [AssessmentController::class, 'report'])
            ->name('assessments.report');
    });
});
