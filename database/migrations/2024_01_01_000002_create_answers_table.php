<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id');
            $table->uuid('question_id');
            $table->text('response');
            $table->decimal('awarded_score', 5, 2)->default(0);
            $table->timestamp('created_at');

            $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->index(['assessment_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
