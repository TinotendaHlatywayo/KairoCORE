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
        // Disable foreign key checks to allow surgical dropping of conflicting tables
        Schema::disableForeignKeyConstraints();

        // Drop all possible conflicting HR tables in the database
        Schema::dropIfExists('hr_audit_logs');
        Schema::dropIfExists('employee_assets');
        Schema::dropIfExists('disciplinary_cases');
        Schema::dropIfExists('staff_loans');
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('employee_contracts');
        Schema::dropIfExists('salary_grade_history');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('salary_grades');

        // Re-enable foreign key checks for schema safety
        Schema::enableForeignKeyConstraints();

        // 1. Salary Grades
        Schema::create('salary_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->decimal('base_salary', 15, 4)->default(0.0000);
            $table->decimal('hourly_rate', 15, 4)->default(0.0000);
            $table->decimal('housing_allowance', 15, 4)->default(0.0000);
            $table->decimal('transport_allowance', 15, 4)->default(0.0000);
            $table->decimal('duty_allowance', 15, 4)->default(0.0000);
            $table->boolean('overtime_eligible')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'uq_salary_grade_name');
        });

        // 2. Employees Master Ledger
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('employee_number');
            $table->string('national_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 50);
            $table->date('date_of_birth');
            $table->string('marital_status', 50)->default('single');
            $table->string('phone_number');
            $table->string('email');
            $table->text('physical_address');
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->string('department');
            $table->string('designation');
            $table->string('role')->default('Teacher');
            $table->string('employment_type')->default('Permanent');
            $table->date('contract_end_date')->nullable();
            $table->date('date_joined');
            $table->foreignId('current_grade_id')->nullable()->constrained('salary_grades')->onDelete('set null');
            $table->json('spouse_details')->nullable();
            $table->json('dependents')->nullable();
            $table->json('next_of_kin')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('emergency_medical_notes')->nullable();
            $table->string('status', 50)->default('active');
            $table->text('suspension_reason')->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'employee_number'], 'uq_emp_num');
            $table->unique(['school_id', 'national_id'], 'uq_emp_nat_id');
            $table->index(['school_id', 'status']);
        });

        // 3. Salary Grade Modification History
        Schema::create('salary_grade_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('previous_grade_id')->nullable()->constrained('salary_grades')->onDelete('set null');
            $table->foreignId('new_grade_id')->constrained('salary_grades')->onDelete('cascade');
            $table->decimal('base_salary', 15, 4);
            $table->date('effective_date');
            $table->string('reason');
            $table->foreignId('approved_by_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Employment Contracts
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('contract_number');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('probation_period_days')->default(90);
            $table->string('status', 50)->default('draft');
            $table->string('document_path')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'contract_number'], 'uq_contract_num');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('employee_contracts');
        Schema::dropIfExists('salary_grade_history');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('salary_grades');
        Schema::enableForeignKeyConstraints();
    }
};
