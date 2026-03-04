<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Vue SPA のエントリポイント
// ハッシュルーティング（#/）を使用するため、すべてのリクエストを welcome.blade.php に転送する
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
