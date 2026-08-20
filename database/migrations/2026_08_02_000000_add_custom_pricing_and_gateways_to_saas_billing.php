<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add Custom Pricing, Tokenized Card Offsetting, and Expirations to Subscriptions
        Schema::table('saas_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_subscriptions', 'custom_price_monthly')) {
                $table->decimal('custom_price_monthly', 12, 2)->nullable();
                $table->decimal('custom_price_quarterly', 12, 2)->nullable();
                $table->decimal('custom_price_yearly', 12, 2)->nullable();
                $table->date('next_payment_date')->nullable()->index();
                $table->date('last_payment_date')->nullable();
                $table->string('gateway_customer_token')->nullable(); // Tokenized cards for Spotify-like auto-billing
                $table->integer('auto_deactivate_after_days')->default(5);
                $table->boolean('is_auto_billing_enabled')->default(true);
            }
        });

        // 2. Platform Billing Configuration Settings Table (Bank Details, Gateway Credentials, SLA Days)
        if (! Schema::hasTable('saas_billing_settings')) {
            Schema::create('saas_billing_settings', function (Blueprint $table) {
                $table->id();
                $table->string('bank_name');
                $table->string('bank_account_name');
                $table->string('bank_account_number');
                $table->string('bank_branch_code')->nullable();
                $table->string('bank_swift_code')->nullable();
                $table->string('paynow_integration_id')->nullable();
                $table->string('paynow_integration_key')->nullable();
                $table->string('paypal_client_id')->nullable();
                $table->string('paypal_client_secret')->nullable();
                $table->boolean('paypal_sandbox_mode')->default(true);
                $table->string('support_email')->default('billing@schoolcore.test');
                $table->timestamps();
            });

            // Seed Default platform settings
            DB::table('saas_billing_settings')->insert([
                'bank_name' => 'Steward Bank Zimbabwe',
                'bank_account_name' => 'SchoolCore Technologies Pvt Ltd',
                'bank_account_number' => '1002345678',
                'bank_branch_code' => '2105',
                'bank_swift_code' => 'STWBTX2X',
                'paynow_integration_id' => '12345',
                'paynow_integration_key' => 'sample-key-uuid-9923',
                'paypal_client_id' => 'paypal-client-id-sample',
                'paypal_client_secret' => 'paypal-secret-sample',
                'paypal_sandbox_mode' => true,
                'support_email' => 'billing@schoolcore.test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_billing_settings');
        Schema::table('saas_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'custom_price_monthly',
                'custom_price_quarterly',
                'custom_price_yearly',
                'next_payment_date',
                'last_payment_date',
                'gateway_customer_token',
                'auto_deactivate_after_days',
                'is_auto_billing_enabled',
            ]);
        });
    }
};
