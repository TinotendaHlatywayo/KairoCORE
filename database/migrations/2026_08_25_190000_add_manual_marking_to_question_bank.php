<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-question marking mode: when enabled, the question is excluded from
     * auto-marking and graded by the teacher in the manual marking queue.
     * The stored correct answer becomes optional.
     */
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (! Schema::hasColumn('question_bank', 'manual_marking')) {
                $table->boolean('manual_marking')->default(false)->after('correct_answer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'manual_marking')) {
                $table->dropColumn('manual_marking');
            }
        });
    }
};
