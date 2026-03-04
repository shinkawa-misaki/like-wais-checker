<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subtest_type', 2);  // A-H
            $table->integer('sequence_number');
            $table->text('content');
            $table->string('question_type', 30);
            $table->string('correct_answer');
            $table->json('options')->nullable();
            $table->integer('max_points')->default(1);
            $table->text('hint')->nullable();

            $table->index(['subtest_type', 'sequence_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
