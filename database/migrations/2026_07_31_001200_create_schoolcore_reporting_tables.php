<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Self-healing drop hooks to prevent migration lockouts on partial runs
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('enterprise_report_schedules');
        Schema::dropIfExists('enterprise_report_templates');

        Schema::create('enterprise_report_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('module');
            $table->string('report_type');
            $table->string('orientation')->default('portrait');
            $table->json('selected_fields');
            $table->json('layout_settings');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['school_id', 'name'], 'uq_ent_rep_temp_school_name');
        });

        Schema::create('enterprise_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('enterprise_report_template_id');
            $table->string('name');
            $table->string('frequency');
            $table->json('recipients');
            $table->json('filter_overrides')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('enterprise_report_template_id', 'fk_ent_sched_temp_id')
                ->references('id')
                ->on('enterprise_report_templates')
                ->onDelete('cascade');
        });

        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('enterprise_report_template_id')->nullable();
            $table->string('name');
            $table->string('format');
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('generated_by_id')->nullable();
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('enterprise_report_template_id', 'fk_gen_rep_temp_id')
                ->references('id')
                ->on('enterprise_report_templates')
                ->onDelete('set null');
            $table->foreign('generated_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('enterprise_report_schedules');
        Schema::dropIfExists('enterprise_report_templates');
    }
};
