<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rebuild each bank account's stored balance to reflect the cash that has
     * actually been collected. The running balance was previously inflated by
     * phantom $5,000 opening seeds and by revenue-stream creation booking fake
     * payments before any money was received, so it could drift far from the
     * real collected figure. Net collected = non-refund payments minus refunds
     * (refunds are stored with a negative amount, so a plain SUM works).
     */
    public function up(): void
    {
        $accounts = DB::table('school_bank_accounts')->select('id', 'school_id')->get();

        foreach ($accounts as $account) {
            $collected = (float) DB::table('payments')
                ->where('school_id', $account->school_id)
                ->sum('amount');

            DB::table('school_bank_accounts')
                ->where('id', $account->id)
                ->update(['balance' => max(0, round($collected, 2))]);
        }
    }

    public function down(): void
    {
        // No reverse: old phantom balances were already incorrect.
    }
};