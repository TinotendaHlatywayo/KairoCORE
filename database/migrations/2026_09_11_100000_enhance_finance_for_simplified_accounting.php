<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expenses: link straight to a category (readers no longer need the
        // intermediate "Expense Type" step) and carry a friendly expense name.
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_name')->nullable()->after('expense_type_id');
            $table->unsignedBigInteger('expense_category_id')->nullable()->after('expense_name');

            $table->foreign('expense_category_id')
                ->references('id')->on('expense_categories')
                ->nullOnDelete();
        });

        // Payments: overpayments can now be refunded (negative row) or carried
        // forward (is_refund = false, excess handling recorded), and each
        // payment can credit a specific school bank account.
        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('is_refund')->default(false)->after('currency');
            $table->string('excess_handling')->nullable()->after('amount'); // refund | credit
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('school_id');
        });

        // Bank accounts get a live running balance so fee payments increase it
        // and refunds decrease it, feeding bank statements/dashboards.
        Schema::table('school_bank_accounts', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->default(0)->after('is_default');
        });

        // Suppliers (both registries) gain an optional website field for the
        // inline "+ create supplier" flow.
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('website')->nullable()->after('email');
        });

        if (Schema::hasTable('inventory_suppliers')) {
            Schema::table('inventory_suppliers', function (Blueprint $table) {
                $table->string('website')->nullable()->after('email');
            });
        }

        // Revenue streams can carry notes.
        Schema::table('revenue_streams', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('account_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn(['expense_name', 'expense_category_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['is_refund', 'excess_handling', 'bank_account_id']);
        });

        Schema::table('school_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('balance');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('website');
        });

        if (Schema::hasTable('inventory_suppliers')) {
            Schema::table('inventory_suppliers', function (Blueprint $table) {
                $table->dropColumn('website');
            });
        }

        Schema::table('revenue_streams', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};