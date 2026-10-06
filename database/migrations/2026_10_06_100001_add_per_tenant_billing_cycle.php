<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant billing cycle.
 *
 * A school that registers on 5 September is free until a configurable start
 * date (5 October by default), and billing then runs on the day-of-month of
 * that start date every month after it.
 *
 *  - billing_start_date  the first day the tenant is billable. Before it the
 *                        subscription is in its free period and no reminders
 *                        or suspensions fire.
 *  - billing_day_of_month the day (1-28) the cycle repeats on, derived from
 *                        billing_start_date but editable by the super admin.
 *
 * Invoices additionally record the period they cover so a tenant can pay for a
 * whole year up front and the system knows how many months were bought and
 * when the next billing date falls.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_subscriptions', 'billing_start_date')) {
                $table->date('billing_start_date')->nullable()->after('grace_ends_at');
            }
            if (! Schema::hasColumn('saas_subscriptions', 'billing_day_of_month')) {
                $table->unsignedTinyInteger('billing_day_of_month')->nullable()->after('billing_start_date');
            }
        });

        Schema::table('saas_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_invoices', 'period_start')) {
                $table->date('period_start')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('saas_invoices', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            if (! Schema::hasColumn('saas_invoices', 'months_covered')) {
                $table->unsignedTinyInteger('months_covered')->default(1)->after('period_end');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saas_subscriptions', function (Blueprint $table) {
            foreach (['billing_start_date', 'billing_day_of_month'] as $column) {
                if (Schema::hasColumn('saas_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('saas_invoices', function (Blueprint $table) {
            foreach (['period_start', 'period_end', 'months_covered'] as $column) {
                if (Schema::hasColumn('saas_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};