<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salary_grades')) {
            Schema::table('salary_grades', function (Blueprint $table) {
                if (!Schema::hasColumn('salary_grades', 'custom_allowances')) {
                    $table->json('custom_allowances')->nullable();
                }
                if (!Schema::hasColumn('salary_grades', 'custom_deductions')) {
                    $table->json('custom_deductions')->nullable();
                }
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'individual_allowances')) {
                    $table->json('individual_allowances')->nullable();
                }
            });
        }

        if (Schema::hasTable('staff_loans')) {
            Schema::table('staff_loans', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_loans', 'loan_type_other')) {
                    $table->string('loan_type_other')->nullable();
                }
                if (!Schema::hasColumn('staff_loans', 'interest_rate')) {
                    $table->decimal('interest_rate', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('staff_loans', 'interest_type')) {
                    $table->string('interest_type')->default('percentage'); // percentage or fixed_amount
                }
                if (!Schema::hasColumn('staff_loans', 'total_repayable')) {
                    $table->decimal('total_repayable', 10, 2)->default(0.00);
                }
            });
        }
    }

    public function down(): void
    {
    }
};
