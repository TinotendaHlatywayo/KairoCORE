<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Add workflow tracking to academic_years
        Schema::table('academic_years', function (Blueprint $table) {
            $table->timestamp('workflow_completed_at')->nullable();
            $table->string('workflow_status', 50)->default('draft');
            $table->json('workflow_metadata')->nullable();
        });

        // Add workflow tracking to terms
        Schema::table('terms', function (Blueprint $table) {
            $table->timestamp('workflow_completed_at')->nullable();
        });

        // Add workflow tracking to courses
        Schema::table('courses', function (Blueprint $table) {
            $table->timestamp('workflow_completed_at')->nullable();
            $table->string('workflow_status', 50)->default('pending');
        });

        // Add workflow tracking to sections
        Schema::table('sections', function (Blueprint $table) {
            $table->timestamp('workflow_completed_at')->nullable();
        });

        // Add workflow tracking to subjects
        Schema::table('subjects', function (Blueprint $table) {
            $table->timestamp('workflow_completed_at')->nullable();
        });

        // Create academic workflow steps tracking table
        Schema::create('academic_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('step_key');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->integer('step_order')->default(0);
            $table->string('depends_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('skipped_until')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'step_key']);
        });

        // Create academic workflow history table
        Schema::create('academic_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action');
            $table->string('workflow_step')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('browser')->nullable();
            $table->string('ip_address')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('workflow_step');
        });

        // Create academic validation rules table
        Schema::create('academic_validation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('rule_key')->unique();
            $table->string('entity_type');
            $table->string('action');
            $table->text('error_message');
            $table->text('warning_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_critical')->default(false);
            $table->json('conditions')->nullable();
            $table->timestamps();
        });

        // Create academic automation rules table
        Schema::create('academic_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_event');
            $table->json('conditions');
            $table->json('actions');
            $table->string('frequency', 50)->default('instant');
            $table->integer('delay_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Create academic workflow templates table
        Schema::create('academic_workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('workflow_definition');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('workflow_completed_at');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('workflow_completed_at');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['workflow_completed_at', 'workflow_status']);
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('workflow_completed_at');
        });

        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['workflow_completed_at', 'workflow_status', 'workflow_metadata']);
        });

        Schema::dropIfExists('academic_workflow_steps');
        Schema::dropIfExists('academic_workflow_history');
        Schema::dropIfExists('academic_validation_rules');
        Schema::dropIfExists('academic_automation_rules');
        Schema::dropIfExists('academic_workflow_templates');

        Schema::enableForeignKeyConstraints();
    }
};
