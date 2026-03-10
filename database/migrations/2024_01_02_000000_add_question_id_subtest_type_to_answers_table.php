<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('answers', 'question_id')) {
                $table->uuid('question_id')->after('assessment_id');
                $table->foreign('question_id')->references('id')->on('questions');
                $table->index(['assessment_id', 'question_id']);
            }

            if (! Schema::hasColumn('answers', 'subtest_type')) {
                $table->string('subtest_type', 1)->after('question_id');
                $table->index(['assessment_id', 'subtest_type']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            if (Schema::hasColumn('answers', 'subtest_type')) {
                $table->dropIndex(['assessment_id', 'subtest_type']);
                $table->dropColumn('subtest_type');
            }
            if (Schema::hasColumn('answers', 'question_id')) {
                $table->dropForeign(['question_id']);
                $table->dropIndex(['assessment_id', 'question_id']);
                $table->dropColumn('question_id');
            }
        });
    }
};
