<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff loans: pick the bank account the loan is funded from (and repaid
        // into by default), choose the interest model (fixed total repayable vs
        // reducing balance), allow the monthly deduction as a % of the total,
        // track when interest was last applied and when funds were disbursed.
        Schema::table('staff_loans', function (Blueprint $table) {
            $table->string('repayment_method')->default('fixed')->after('interest_type');
            $table->string('monthly_deduction_type')->default('fixed')->after('monthly_deduction');
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('monthly_deduction_type');
            $table->unsignedBigInteger('last_interest_payroll_period_id')->nullable()->after('bank_account_id');
            $table->timestamp('funded_at')->nullable()->after('status');
        });

        Schema::table('staff_loans', function (Blueprint $table) {
            $table->foreign('bank_account_id')->references('id')->on('school_bank_accounts')->nullOnDelete();
            $table->foreign('last_interest_payroll_period_id')->references('id')->on('payroll_periods')->nullOnDelete();
        });

        // Payslip lines know whether a deduction is a tax (money leaves the
        // school) or a loan recovery (money returns to the loan account).
        Schema::table('payslip_items', function (Blueprint $table) {
            $table->string('deduction_type')->nullable()->after('type');
        });

        // The bank account salaries are paid out of, chosen at approval time.
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('status');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->foreign('bank_account_id')->references('id')->on('school_bank_accounts')->nullOnDelete();
        });

        // Existing percentage/fixed_amount interest rows map to the "fixed total
        // repayable" model (both fix the total at issuance).
        DB::table('staff_loans')->update([
            'repayment_method' => 'fixed',
            'monthly_deduction_type' => 'fixed',
        ]);
    }

    public function down(): void
    {
        Schema::table('staff_loans', function (Blueprint $table) {
            $table->dropForeign(['last_interest_payroll_period_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['last_interest_payroll_period_id', 'bank_account_id', 'monthly_deduction_type', 'repayment_method', 'funded_at']);
        });

        Schema::table('payslip_items', function (Blueprint $table) {
            $table->dropColumn('deduction_type');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn('bank_account_id');
        });
    }
};
