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
        Schema::dropIfExists('hr_audit_logs');
        Schema::dropIfExists('employee_assets');
        Schema::dropIfExists('disciplinary_cases');
        Schema::dropIfExists('staff_loans');

        // 1. Staff Loans & Salary Advances Ledger
        Schema::create('staff_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('loan_type', 100);
            $table->decimal('principal_amount', 15, 4)->default(0.0000);
            $table->decimal('balance_remaining', 15, 4)->default(0.0000);
            $table->decimal('monthly_deduction', 15, 4)->default(0.0000);
            $table->string('status', 50)->default('pending');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Disciplinary Actions Queue
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->text('offense');
            $table->date('incident_date');
            $table->string('status', 50)->default('under_investigation');
            $table->string('severity', 50)->default('verbal_warning');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('action_taken_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 3. Inventory Asset Assignments
        Schema::create('employee_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('asset_name');
            $table->string('serial_number');
            $table->date('issued_date');
            $table->date('returned_date')->nullable();
            $table->string('status', 50)->default('issued');
            $table->timestamps();
        });

        // 4. Immutable HR Security Ledger
        Schema::create('hr_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('ip_address', 45);
            $table->string('user_agent');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->text('transition_reason')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'auditable_type', 'auditable_id'], 'uq_audit_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_audit_logs');
        Schema::dropIfExists('employee_assets');
        Schema::dropIfExists('disciplinary_cases');
        Schema::dropIfExists('staff_loans');
    }
};
