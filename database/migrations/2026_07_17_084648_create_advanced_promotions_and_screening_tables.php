<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Academic Year Rollovers (Audit Trail)
        Schema::create('school_rollovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('from_academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('to_academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('strategy'); // 'level_to_level', 'stream_to_stream', 'screened'
            $table->integer('students_processed_count')->default(0);
            $table->timestamps();
        });

        // 2. Performance-Gated Screening Rules
        Schema::create('screening_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('source_course_id')->constrained('courses')->onDelete('cascade'); // e.g., Form 2
            $table->foreignId('target_course_id')->constrained('courses')->onDelete('cascade'); // e.g., Form 3
            $table->foreignId('target_section_id')->constrained('sections')->onDelete('cascade'); // e.g., Form 3 A (Sciences)

            $table->string('rule_type'); // 'overall_gpa' or 'subject_specific'
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade'); // NULL if overall GPA
            $table->decimal('min_percentage', 5, 2); // Cut-off mark (e.g., 75.00)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_rules');
        Schema::dropIfExists('school_rollovers');
    }
};
