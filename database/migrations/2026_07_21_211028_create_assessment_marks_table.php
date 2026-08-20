<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->foreignId('assessment_type_id')->constrained('assessment_types')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->decimal('marks_obtained', 5, 2)->nullable();
            $table->string('teacher_initials', 10)->nullable();
            $table->timestamps();

            // Restricts duplicate score entries for a student on any single custom assessment
            $table->unique(['enrollment_id', 'assessment_type_id', 'subject_id'], 'unique_student_assessment_mark');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_marks');
    }
};
