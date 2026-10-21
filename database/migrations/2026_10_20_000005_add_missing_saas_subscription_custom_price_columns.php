<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 2026_08_02 migration guarded its whole column block on
 * custom_price_monthly already being absent. Production already had that one
 * column (added separately), so the guard skipped the rest and
 * custom_price_quarterly / custom_price_yearly / gateway_customer_token /
 * is_auto_billing_enabled were never created — saving a subscription then
 * 500'd with "Unknown column". Add each column individually this time.
 */
return new class extends Migration
{
    private static function columns(): array
    {
        return [
            'custom_price_quarterly' => fn (Blueprint $table) => $table->decimal('custom_price_quarterly', 12, 2)->nullable(),
            'custom_price_yearly' => fn (Blueprint $table) => $table->decimal('custom_price_yearly', 12, 2)->nullable(),
            'gateway_customer_token' => fn (Blueprint $table) => $table->string('gateway_customer_token')->nullable(),
            'is_auto_billing_enabled' => fn (Blueprint $table) => $table->boolean('is_auto_billing_enabled')->default(true),
        ];
    }

    public function up(): void
    {
        Schema::table('saas_subscriptions', function (Blueprint $table) {
            foreach (self::columns() as $column => $add) {
                if (! Schema::hasColumn('saas_subscriptions', $column)) {
                    $add($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('saas_subscriptions', function (Blueprint $table) {
            $existing = array_filter(
                array_keys(self::columns()),
                fn (string $column) => Schema::hasColumn('saas_subscriptions', $column)
            );

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
