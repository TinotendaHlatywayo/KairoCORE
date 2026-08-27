<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────────────
        // Extend `assessments` table with digital mode support
        // ──────────────────────────────────────────────────────
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('mode', 20)->default('traditional')->after('status');
            $table->unsignedBigInteger('digital_assessment_id')->nullable()->after('mode');
            $table->foreign('digital_assessment_id')->references('id')->on('digital_assessments')->nullOnDelete();

            $table->index(['mode']);
        });

        // ──────────────────────────────────────────────────────
        // Extend `assessment_marks_ledger` with digital linkage
        // ──────────────────────────────────────────────────────
        Schema::table('assessment_marks_ledger', function (Blueprint $table) {
            $table->boolean('is_digital')->default(false)->after('teacher_comment');
            $table->unsignedBigInteger('digital_assessment_attempt_id')->nullable()->after('is_digital');
            $table->foreign('digital_assessment_attempt_id')->references('id')->on('digital_assessment_attempts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_marks_ledger', function (Blueprint $table) {
            $table->dropForeign(['digital_assessment_attempt_id']);
            $table->dropColumn(['is_digital', 'digital_assessment_attempt_id']);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['digital_assessment_id']);
            $table->dropIndex(['mode']);
            $table->dropColumn(['mode', 'digital_assessment_id']);
        });
    }
};
