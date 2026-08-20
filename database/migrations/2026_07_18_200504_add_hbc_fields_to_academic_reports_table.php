<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_reports', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('student_id')->constrained('sections')->onDelete('set null');
            $table->decimal('overall_score', 4, 2)->default(0.00)->after('unhu_competencies');
            $table->string('strength')->nullable()->after('overall_score');
            $table->string('needs_improvement')->nullable()->after('strength');
            $table->string('status')->default('approved')->after('needs_improvement'); // approved, draft
            $table->foreignId('teacher_id')->nullable()->after('status')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('academic_reports', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['section_id', 'overall_score', 'strength', 'needs_improvement', 'status', 'teacher_id']);
        });
    }
};
