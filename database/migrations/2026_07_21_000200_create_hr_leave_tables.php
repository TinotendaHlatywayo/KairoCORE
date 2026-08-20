<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safe Drop Guards
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');

        // 1. Leave Type Configuration Models
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('code', 100);
            $table->integer('days_per_year')->default(21);
            $table->boolean('carry_forward')->default(false);
            $table->integer('max_accumulation')->default(30);
            $table->integer('probation_restricted_days')->default(0);
            $table->string('gender_restriction', 50)->nullable();
            $table->integer('service_length_months_required')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'uq_leave_type_code');
        });

        // 2. Leave Requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->string('status', 50)->default('pending');
            $table->string('supporting_document_path')->nullable();
            $table->text('hr_remarks')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['school_id', 'employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};
