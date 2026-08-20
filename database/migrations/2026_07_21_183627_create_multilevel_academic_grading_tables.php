<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Add Level tracking to existing Courses table
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'level')) {
                $table->string('level')->default('primary')->after('name'); // primary, lower_secondary, upper_secondary
            }
        });

        // 2. Create Subject Papers Table (Supports multi-paper A-Level subjects)
        Schema::create('subject_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name'); // e.g. Paper 1, Paper 2, Paper 3, Core
            $table->timestamps();
        });

        // 3. Create Consolidated Mark Records Table
        Schema::create('mark_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('subject_paper_id')->constrained('subject_papers')->onDelete('cascade');

            // Primary & Standard Exams Marks
            $table->decimal('bot_mark', 5, 2)->nullable(); // Beginning of Term
            $table->decimal('mot_mark', 5, 2)->nullable(); // Mid of Term
            $table->decimal('eot_mark', 5, 2)->nullable(); // End of Term

            // Continuous Assessment Marks (O-Level C1, C2, C3)
            $table->decimal('c1_mark', 5, 2)->nullable();
            $table->decimal('c2_mark', 5, 2)->nullable();
            $table->decimal('c3_mark', 5, 2)->nullable();

            $table->string('teacher_initials', 10)->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'subject_paper_id'], 'uq_student_paper_mark');
        });

        // 4. Create Co-Curricular Competencies Table (Primary / ECD Support)
        Schema::create('student_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->string('skill_area'); // e.g. Baking, Gardening, Reading, Swimming, Art
            $table->decimal('score', 4, 2)->default(0.00); // Score out of 10
            $table->string('remark')->nullable(); // e.g. Outstanding, Satisfactory
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('student_competencies');
        Schema::dropIfExists('mark_records');
        Schema::dropIfExists('subject_papers');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
