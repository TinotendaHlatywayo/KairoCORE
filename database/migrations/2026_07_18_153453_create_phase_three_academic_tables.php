<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Grading Frameworks (e.g., ZIMSEC O-Level, Cambridge IGCSE)
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g. "Standard O-Level"
            $table->timestamps();
        });

        Schema::create('grading_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scale_id')->constrained('grading_scales')->onDelete('cascade');
            $table->string('symbol'); // A, B, C, U
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('remark')->nullable(); // Excellent
            $table->timestamps();
        });

        // 2. Exams & Weighted Papers
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->string('name'); // e.g. "End of Term 1 Exams"
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('exam_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name'); // Paper 1, Paper 2, Practical
            $table->integer('max_mark')->default(100);
            $table->decimal('weight_percentage', 5, 2)->default(100); // For Paper 1 (40%) + Paper 2 (60%)
            $table->timestamps();
        });

        // 3. The Marks (The Ledger)
        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('exam_paper_id')->constrained('exam_papers')->onDelete('cascade');
            $table->decimal('marks_obtained', 5, 2);
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['student_id', 'exam_paper_id'], 'uq_mark_student_paper');
        });

        // 4. SBP / CALA (The 20% Projects)
        Schema::create('sbp_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->decimal('score', 5, 2); // Typically out of 20
            $table->timestamps();
        });

        // 5. Academic Reports (The Anti-Fraud Hub)
        Schema::create('academic_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->json('unhu_competencies')->nullable(); // JSON for Respect, Honesty, etc.
            $table->text('teacher_comment')->nullable();
            $table->text('headmaster_comment')->nullable();
            $table->string('integrity_hash')->unique(); // Anti-fraud hash
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_reports');
        Schema::dropIfExists('sbp_assessments');
        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_papers');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('grading_points');
        Schema::dropIfExists('grading_scales');
    }
};
