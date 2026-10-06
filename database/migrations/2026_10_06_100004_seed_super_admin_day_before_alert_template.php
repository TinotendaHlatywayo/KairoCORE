<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a second super-admin billing alert so the operator is notified both the
 * day before and on the billing day itself (the existing
 * super_admin_billing_alert only fires on the billing day). A distinct template
 * key is required because the notification ledger is unique per
 * (school, template_key, billing_date) — the two alerts share a billing date.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_billing_message_templates')) {
            return;
        }

        if (! DB::table('platform_billing_message_templates')->where('key', 'super_admin_billing_alert_day_before')->exists()) {
            DB::table('platform_billing_message_templates')->insert([
                'key' => 'super_admin_billing_alert_day_before',
                'name' => 'Super admin billing alert (day before)',
                'subject' => 'Billing tomorrow: {school_name} — {amount} {currency}',
                'body' => "Billing alert (day before).\n\nTenant: {school_name} ({subdomain})\nAmount: {amount} {currency}\nBilling date: {billing_date}\nPlan: {plan_name}\nInvoice: {invoice_number}\nStatus: {status}",
                'send_platform_message' => false,
                'send_email' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_billing_message_templates')) {
            DB::table('platform_billing_message_templates')
                ->where('key', 'super_admin_billing_alert_day_before')
                ->delete();
        }
    }
};