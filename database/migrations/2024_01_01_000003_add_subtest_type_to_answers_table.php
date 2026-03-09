<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            $table->string('subtest_type', 1)->after('question_id');
            $table->index(['assessment_id', 'subtest_type']);
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            $table->dropIndex(['assessment_id', 'subtest_type']);
            $table->dropColumn('subtest_type');
        });
    }
};
