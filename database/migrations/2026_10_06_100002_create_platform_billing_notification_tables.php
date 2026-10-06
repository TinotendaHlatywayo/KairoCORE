<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Super-admin configuration for the billing notification schedule, the
 * editable default messages it uses, and an idempotency ledger so a message
 * is never sent twice for the same tenant, template and billing date.
 *
 * Schedule (offsets configurable, defaults as specified by the operator):
 *   D-3  billing reminder with the amount
 *   D-1  day-before reminder with the amount (+ alert to the super admin)
 *   D0   billing day, with the amount                       (+ super admin)
 *   D+1  overdue reminder
 *   D+4  notice that the tenant will be suspended
 *   D+5  suspend access and email the reason + data-retention promise
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_billing_settings')) {
            Schema::create('platform_billing_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('remind_days_before')->default(3);
                $table->unsignedSmallInteger('day_before_reminder_offset')->default(1);
                $table->unsignedSmallInteger('overdue_reminder_offset')->default(1);
                $table->unsignedSmallInteger('suspension_warning_offset')->default(4);
                $table->unsignedSmallInteger('suspension_offset')->default(5);
                $table->unsignedSmallInteger('data_retention_months')->default(6);
                $table->boolean('notify_super_admin_on_billing')->default(true);
                $table->boolean('notify_super_admin_on_registration')->default(true);
                $table->string('super_admin_billing_email')->nullable();
                $table->unsignedSmallInteger('default_free_days')->default(30);
                $table->timestamps();
            });
        }

        if (DB::table('platform_billing_settings')->count() === 0) {
            DB::table('platform_billing_settings')->insert([
                'remind_days_before' => 3,
                'day_before_reminder_offset' => 1,
                'overdue_reminder_offset' => 1,
                'suspension_warning_offset' => 4,
                'suspension_offset' => 5,
                'data_retention_months' => 6,
                'notify_super_admin_on_billing' => true,
                'notify_super_admin_on_registration' => true,
                'super_admin_billing_email' => 'hlatywayotw@gmail.com',
                'default_free_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('platform_billing_message_templates')) {
            Schema::create('platform_billing_message_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('subject');
                $table->text('body');
                $table->boolean('send_platform_message')->default(true);
                $table->boolean('send_email')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (DB::table('platform_billing_message_templates')->count() === 0) {
            DB::table('platform_billing_message_templates')->insert($this->defaultTemplates());
        }

        if (! Schema::hasTable('platform_billing_notification_logs')) {
            Schema::create('platform_billing_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('saas_subscription_id')->nullable();
                $table->string('template_key');
                $table->date('billing_date');
                $table->string('channel', 30);
                $table->string('subject')->nullable();
                $table->unsignedSmallInteger('recipient_count')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['school_id', 'template_key', 'billing_date'],
                    'billing_notification_once_per_cycle'
                );
                // Explicit short name: the generated one is 65 chars and MySQL
                // refuses identifiers longer than 64.
                $table->index(['template_key', 'billing_date'], 'billing_notification_cycle_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_billing_notification_logs');
        Schema::dropIfExists('platform_billing_message_templates');
        Schema::dropIfExists('platform_billing_settings');
    }

    /**
     * Placeholders resolved at send time: {school_name} {amount} {currency}
     * {billing_date} {due_date} {plan_name} {days} {retention_months}
     * {invoice_number} {subdomain} {url}
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultTemplates(): array
    {
        $templates = [
            [
                'key' => 'billing_reminder',
                'name' => 'Billing reminder (before due date)',
                'subject' => 'Billing for {school_name} is due in {days} days',
                'body' => "Hello {school_name},\n\nThis is a reminder that your Kairo CORE subscription payment of {amount} {currency} is due on {billing_date}.\n\nPlan: {plan_name}\nAmount due: {amount} {currency}\nDue date: {billing_date}\n\nYou can pay online or upload a bank deposit slip from your billing page:\n{url}\n\nKind regards,\nKairo CORE Billing",
            ],
            [
                'key' => 'billing_day_before',
                'name' => 'Billing day-before notice',
                'subject' => 'Reminder: {amount} {currency} due tomorrow for {school_name}',
                'body' => "Hello {school_name},\n\nYour Kairo CORE subscription payment of {amount} {currency} falls due tomorrow, {billing_date}.\n\nPlan: {plan_name}\nAmount due: {amount} {currency}\nDue date: {billing_date}\nInvoice: {invoice_number}\n\nSettle it online or upload a bank deposit slip from your billing page:\n{url}\n\nKind regards,\nKairo CORE Billing",
            ],
            [
                'key' => 'billing_due_today',
                'name' => 'Billing day notice',
                'subject' => 'Billing today for {school_name}: {amount} {currency}',
                'body' => "Hello {school_name},\n\nToday, {billing_date}, is your Kairo CORE billing date.\n\nPlan: {plan_name}\nAmount due: {amount} {currency}\nInvoice: {invoice_number}\n\nYou can pay online or upload a bank deposit slip from your billing page:\n{url}\n\nKind regards,\nKairo CORE Billing",
            ],
            [
                'key' => 'billing_overdue',
                'name' => 'Overdue payment reminder',
                'subject' => 'Payment overdue: {amount} {currency} for {school_name}',
                'body' => "Hello {school_name},\n\nYour Kairo CORE subscription payment of {amount} {currency} is now {days} day(s) overdue.\n\nPlan: {plan_name}\nAmount due: {amount} {currency}\nDue date: {billing_date}\nInvoice: {invoice_number}\n\nPlease settle the amount to keep your school's access active:\n{url}\n\nKind regards,\nKairo CORE Billing",
            ],
            [
                'key' => 'billing_suspension_warning',
                'name' => 'Suspension warning',
                'subject' => 'Urgent: {school_name} will be suspended in {days} days',
                'body' => "Hello {school_name},\n\nYour Kairo CORE subscription payment of {amount} {currency} remains unpaid.\n\nYour school will be suspended in {days} days, on {billing_date}.\n\nPlan: {plan_name}\nAmount due: {amount} {currency}\nInvoice: {invoice_number}\n\nPay now to avoid suspension:\n{url}\n\nKind regards,\nKairo CORE Billing",
            ],
            [
                'key' => 'billing_suspended',
                'name' => 'Account suspension notice',
                'subject' => '{school_name} has been suspended',
                'body' => "Hello {school_name},\n\nYour Kairo CORE account has been suspended.\n\nReason: the subscription payment of {amount} {currency} for {billing_date} was not received within the allowed grace period.\n\nYour data will be kept safe for at least {retention_months} months. Restoring access is straightforward once payment is received — contact us with your payment reference and we will reactivate your school immediately.\n\nAmount due: {amount} {currency}\nInvoice: {invoice_number}\n\nKind regards,\nKairo CORE Billing",
            ],
            [
                'key' => 'super_admin_billing_alert',
                'name' => 'Super admin billing alert',
                'subject' => 'Billing today: {school_name} — {amount} {currency}',
                'body' => "Billing alert.\n\nTenant: {school_name} ({subdomain})\nAmount: {amount} {currency}\nBilling date: {billing_date}\nPlan: {plan_name}\nInvoice: {invoice_number}\nStatus: {status}",
            ],
            [
                'key' => 'school_registered_alert',
                'name' => 'New school registration alert',
                'subject' => 'New school registered: {school_name}',
                'body' => "A new school has registered on Kairo CORE.\n\nSchool: {school_name}\nSubdomain: {subdomain}\nContact email: {contact_email}\nRegistered: {registration_date}\n\nReview it in the Institutions area of the platform.",
            ],
        ];

        return array_map(fn (array $template) => $template + [
            'send_platform_message' => $template['key'] === 'super_admin_billing_alert'
                || $template['key'] === 'school_registered_alert' ? false : true,
            'send_email' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $templates);
    }
};
