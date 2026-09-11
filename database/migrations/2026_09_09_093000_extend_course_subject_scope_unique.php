<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow one course_subject row per (school, course, subject) scope so two
     * primary-school patterns can coexist:
     *
     *   - section_id = NULL  → a subject specialist teaches every stream of
     *                          that course (e.g. "Grade 5 Science — S. Nyamu").
     *   - section_id = <id>  → that stream's own teacher (e.g. a class teacher
     *                          teaching all subjects only to "Grade 4A").
     *
     * MySQL treats NULLs as distinct in unique indexes, so one course-level row
     * and one row per section can all exist for the same subject at once.
     */
    public function up(): void
    {
        Schema::table('course_subject', function (Blueprint $table) {
            // Add the wider index FIRST: MySQL needs a successor index that
            // still leads with course_id before the FK on course_id will
            // release the legacy unique index.
            $table->unique(
                ['school_id', 'course_id', 'subject_id', 'section_id'],
                'uq_course_subject_scope'
            );
            $table->dropUnique('uq_course_subject');
        });
    }

    public function down(): void
    {
        Schema::table('course_subject', function (Blueprint $table) {
            $table->dropUnique('uq_course_subject_scope');
            $table->unique(['school_id', 'course_id', 'subject_id'], 'uq_course_subject');
        });

        // The old composite index cannot express per-section rows safely;
        // drop any non-course-scoped duplicates on rollback.
        DB::statement(
            'DELETE cs FROM course_subject cs
             JOIN course_subject keep
               ON keep.school_id = cs.school_id
              AND keep.course_id = cs.course_id
              AND keep.subject_id = cs.subject_id
              AND keep.section_id IS NULL
             WHERE cs.section_id IS NOT NULL'
        );
    }
};