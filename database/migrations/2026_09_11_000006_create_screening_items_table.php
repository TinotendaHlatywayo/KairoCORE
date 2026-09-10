<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('screening_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained();
            $table->foreignId('screening_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('source_enrollment_id')->constrained('enrollments');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->enum('decision', ['placed', 'unplaced']);
            $table->foreignId('target_course_id')->nullable()->constrained('courses');
            $table->foreignId('target_section_id')->nullable()->constrained('sections');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['screening_run_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('screening_items');
    }
};