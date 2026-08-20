<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Academic Years Table
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g. "2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. Terms Table (Term 1, Term 2, Term 3)
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('name'); // e.g. "Term 1"
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        // 3. Courses (Grade Levels / Forms)
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g. Grade 1, Form 1
            $table->string('code')->nullable();
            $table->string('level')->default('primary'); // primary, lower_secondary, upper_secondary
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'uq_school_course_name');
        });

        // 4. Sections (Class Streams e.g. Grade 1 Red, Form 1 Arts)
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('name'); // e.g. Red, Blue, Arts, Sciences
            $table->string('code')->nullable();
            $table->integer('capacity')->default(40);
            $table->timestamps();

            $table->unique(['school_id', 'course_id', 'name'], 'uq_school_section_name');
        });

        // 5. Academic Subjects
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('theory'); // theory, practical
            $table->decimal('credit_weight', 4, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'uq_school_subject_code');
        });

        // 6. Classrooms (Physical Rooms)
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->integer('capacity')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'uq_school_classroom_name');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
    }
};
