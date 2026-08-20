<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add targeted indexes for the hottest read paths identified in the
     * performance audit. Foreign keys already index the single columns; these
     * composite indexes cover the actual query patterns (tenant-scoped lookups,
     * report/assessment aggregation, unread notification filters).
     */
    public function up(): void
    {
        // StudentResults: where('student_id', ...) + tenant scope + term filter.
        Schema::table('academic_reports', function (Blueprint $table) {
            $table->index(['school_id', 'student_id', 'term_id'], 'idx_academic_reports_school_student_term');
        });

        // HomeworkResource: whereIn('section_id', ...) + tenant scope.
        Schema::table('homeworks', function (Blueprint $table) {
            $table->index(['school_id', 'section_id'], 'idx_homeworks_school_section');
        });

        // TopbarCommandCenter: unread notifications per notifiable, filtered
        // by type then ordered by created_at.
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_notifiable_read');
        });

        // AssessmentWorkspace: per-assessment type + subject aggregation.
        Schema::table('assessment_marks', function (Blueprint $table) {
            $table->index(['assessment_type_id', 'subject_id'], 'idx_assessment_marks_type_subject');
        });

        // AssessmentWorkspace / HomeworkResource: enrollment lookups by section.
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['school_id', 'section_id'], 'idx_enrollments_school_section');
        });
    }

    public function down(): void
    {
        Schema::table('academic_reports', function (Blueprint $table) {
            $table->dropIndex('idx_academic_reports_school_student_term');
        });

        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropIndex('idx_homeworks_school_section');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_notifiable_read');
        });

        Schema::table('assessment_marks', function (Blueprint $table) {
            $table->dropIndex('idx_assessment_marks_type_subject');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_school_section');
        });
    }
};
