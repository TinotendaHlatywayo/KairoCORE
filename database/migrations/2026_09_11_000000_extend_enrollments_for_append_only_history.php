<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only enrollment history. Class/level membership is never
        // rewritten in place: every change inserts a new row carrying the new
        // course/section plus the when/why/who of the change. Existing code
        // keeps reading the latest row through Student::currentEnrollment().
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('term_id')->nullable()->after('academic_year_id')->constrained('terms')->nullOnDelete();
            $table->string('status', 20)->default('active')->after('section_id');
            $table->date('effective_date')->nullable()->after('status');
            $table->string('reason')->nullable()->after('roll_number');
            $table->foreignId('performed_by_id')->nullable()->after('reason')->constrained('users')->nullOnDelete();
        });

        // Relax the one-enrollment-per-year guard so same-year moves can append.
        // A plain index keeps lookups fast without blocking repeats/transfers.
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'student_id', 'academic_year_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['school_id', 'student_id', 'academic_year_id']);
        });

        // Backfill: every pre-existing enrollment row records that student's
        // then-current class, so it becomes an "active" historical record rooted
        // at the start of its academic year.
        $rows = DB::table('enrollments')->get(['id', 'academic_year_id']);
        foreach ($rows as $row) {
            $startDate = DB::table('academic_years')->where('id', $row->academic_year_id)->value('start_date');

            DB::table('enrollments')->where('id', $row->id)->update([
                'status' => 'active',
                'effective_date' => $startDate ?? now()->toDateString(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'student_id', 'academic_year_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['school_id', 'student_id', 'academic_year_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['performed_by_id']);
            $table->dropForeign(['term_id']);
            $table->dropColumn(['performed_by_id', 'reason', 'effective_date', 'status', 'term_id']);
        });
    }
};