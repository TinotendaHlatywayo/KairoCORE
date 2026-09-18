<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // revenue_streams.account_id previously pointed at the (now unused)
        // chart-of-accounts table. The school actually picks a bank account on
        // the revenue stream form, so retarget the FK to school_bank_accounts.
        Schema::table('revenue_streams', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
        });

        Schema::table('revenue_streams', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('school_bank_accounts')->nullOnDelete();
        });

        // Expenses get an optional bank account so financial views can scope
        // cash out by account (mirroring how payments carry bank_account_id).
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('status');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('bank_account_id')->references('id')->on('school_bank_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('revenue_streams', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
        });

        Schema::table('revenue_streams', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn('bank_account_id');
        });
    }
};
