<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_orders', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('total_amount')->constrained('school_bank_accounts')->nullOnDelete();
            $table->unsignedBigInteger('approved_by_id')->nullable()->after('bank_account_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by_id');
            $table->decimal('refunded_amount', 15, 2)->default(0.00)->after('approved_at');
        });

        Schema::table('procurement_orders', function (Blueprint $table) {
            $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_orders', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['approved_by_id']);
            $table->dropColumn(['bank_account_id', 'approved_by_id', 'approved_at', 'refunded_amount']);
        });
    }
};