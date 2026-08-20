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
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');

        // 1. Payroll Operational Cycles
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 50)->default('draft');
            $table->timestamps();

            $table->unique(['school_id', 'start_date', 'end_date'], 'uq_payroll_period_dates');
        });

        // 2. Combined Operational Runs
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->onDelete('cascade');
            $table->string('status', 50)->default('calculated');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->decimal('gross_total', 15, 4)->default(0.0000);
            $table->decimal('deductions_total', 15, 4)->default(0.0000);
            $table->decimal('net_total', 15, 4)->default(0.0000);
            $table->timestamps();
        });

        // 3. Header Statement: Payslips
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('base_salary', 15, 4)->default(0.0000);
            $table->decimal('gross_pay', 15, 4)->default(0.0000);
            $table->decimal('total_deductions', 15, 4)->default(0.0000);
            $table->decimal('net_pay', 15, 4)->default(0.0000);
            $table->string('status', 50)->default('calculated');
            $table->string('payment_method', 50)->default('Bank Transfer');
            $table->date('payment_date')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('integrity_hash')->nullable();
            $table->string('qr_token')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'payroll_run_id', 'employee_id'], 'uq_payslip_unique_run_emp');
        });

        // 4. Individual Breakdown Lines: Payslip Items
        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('payslip_id')->constrained('payslips')->onDelete('cascade');
            $table->string('code', 100);
            $table->string('name');
            $table->string('type', 50);
            $table->decimal('amount', 15, 4)->default(0.0000);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_recurring')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
    }
};
