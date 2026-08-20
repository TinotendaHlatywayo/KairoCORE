<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ASSESSMENT PLANS (Blueprints created by HODs per Term/Subject)
        Schema::create('assessment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['term_id', 'course_id', 'subject_id'], 'unique_subject_term_plan');
        });

        // 2. PLAN COMPONENTS (e.g., Homework worth 5%, Exams worth 50% with custom evaluation rules)
        Schema::create('assessment_plan_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_plan_id')->constrained('assessment_plans')->onDelete('cascade');
            $table->string('name'); // e.g., "Homework", "Weekly Tests", "Final Exam"
            $table->decimal('weight_percentage', 5, 2); // e.g., 5.00, 15.00, 50.00 (Sums up to 100%)

            // Dynamic Rule Engine Definition
            $table->string('evaluation_rule')->default('average'); // average, best_x, drop_lowest, latest, highest
            $table->integer('rule_value_parameter')->nullable(); // e.g., "5" if evaluating "best_x" (Best 5)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_plan_components');
        Schema::dropIfExists('assessment_plans');
    }
};
