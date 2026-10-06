<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes three schema gaps that the application code already writes to but the
 * tables never received, each of which produces a hard SQL error in production:
 *
 *  - saas_plans.price_quarterly / price_yearly / is_popular: the Subscription
 *    Plan edit form saves them, so saving any plan 500s ("Unknown column").
 *    BillingService also reads price_quarterly/price_yearly for those periods.
 *  - saas_invoices.tax_amount: BillingService writes it on every invoice, so
 *    invoice generation has always thrown — which is why "Select Target
 *    Invoice" is empty. SaaSBillingOverview::mount() hides the failure in a
 *    try/catch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_plans', 'price_quarterly')) {
                $table->decimal('price_quarterly', 12, 2)->default(0.00)->after('price_monthly');
            }
            if (! Schema::hasColumn('saas_plans', 'price_yearly')) {
                $table->decimal('price_yearly', 12, 2)->default(0.00)->after('price_quarterly');
            }
            if (! Schema::hasColumn('saas_plans', 'is_popular')) {
                $table->boolean('is_popular')->default(false)->after('is_active');
            }
        });

        Schema::table('saas_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0.00)->after('discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            foreach (['price_quarterly', 'price_yearly', 'is_popular'] as $column) {
                if (Schema::hasColumn('saas_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('saas_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('saas_invoices', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
        });
    }
};