<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('promotion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained();
            $table->foreignId('promotion_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('source_enrollment_id')->constrained('enrollments');
            $table->enum('decision', ['promoted', 'repeated', 'needs_screening']);
            $table->foreignId('target_course_id')->nullable()->constrained('courses');
            $table->foreignId('target_section_id')->nullable()->constrained('sections');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['promotion_run_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('promotion_items');
    }
};
