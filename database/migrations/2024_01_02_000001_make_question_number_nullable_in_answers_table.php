<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('answers', 'question_number')) {
            return;
        }

        Schema::table('answers', function (Blueprint $table): void {
            $table->unsignedInteger('question_number')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        //
    }
};
