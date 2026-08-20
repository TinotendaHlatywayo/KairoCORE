<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ASSESSMENT INSTANCES (Created by Teachers, e.g., "Week 3 Test")
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('assessment_plan_component_id')->constrained('assessment_plan_components')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade'); // The specific class stream
            $table->string('name'); // e.g., "Week 3 Test", "End of Topic 2", "Experiment 1"
            $table->date('assessment_date');
            $table->decimal('max_mark', 5, 2)->default(100.00);
            $table->boolean('included_in_report')->default(true);

            // Workflow Kanban/Approval States
            $table->string('status')->default('draft'); // draft, scheduled, open, marking, submitted, reviewed, locked, published
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. ASSESSMENT SUB-COMPONENTS (Optional multi-paper/part structures)
        Schema::create('assessment_sub_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->string('name'); // e.g., "Paper 1", "Presentation", "Experiment notebook"
            $table->decimal('max_mark', 5, 2); // e.g. 40.00, 60.00
            $table->decimal('weight_percentage', 5, 2); // e.g. 40.00%, 60.00% (Sums up to 100% of parent Assessment)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_sub_components');
        Schema::dropIfExists('assessments');
    }
};
