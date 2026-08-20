<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_marks_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');

            // Nullable if marks are recorded on a sub-component paper level instead of the parent assessment directly
            $table->foreignId('assessment_sub_component_id')->nullable()->constrained('assessment_sub_components')->onDelete('cascade');

            $table->decimal('marks_obtained', 5, 2)->nullable();

            // Attendance/Execution Exception Flags
            $table->string('status')->default('present'); // present, absent, excused, cheated, late_submission
            $table->text('teacher_comment')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'assessment_id', 'assessment_sub_component_id'], 'unique_student_mark_registration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_marks_ledger');
    }
};
