<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Core Plans Table
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->integer('trial_days')->default(14);
            $table->integer('grace_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. School Subscriptions Ledger
        Schema::create('saas_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('saas_plan_id')->constrained('saas_plans')->onDelete('cascade');
            $table->string('billing_period')->default('monthly'); // monthly, quarterly, yearly
            $table->string('status')->default('trialing'); // trialing, active, grace_period, expired, suspended
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();

            // Custom school overrides & credit balance parameters
            $table->decimal('custom_price_monthly', 12, 2)->nullable();
            $table->decimal('credit_balance', 12, 2)->default(0.00); // Running overpayment ledger
            $table->date('next_payment_date')->nullable()->index();
            $table->date('last_payment_date')->nullable();
            $table->integer('auto_deactivate_after_days')->default(5);
            $table->integer('dunning_days_before')->default(2); // Customizable reminder threshold

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id'], 'uq_school_saas_sub');
        });

        // 3. Subscription History Logs
        Schema::create('saas_subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('saas_subscription_id')->constrained('saas_subscriptions')->onDelete('cascade');
            $table->string('action_type');
            $table->foreignId('old_plan_id')->nullable()->constrained('saas_plans')->onDelete('set null');
            $table->foreignId('new_plan_id')->constrained('saas_plans')->onDelete('cascade');
            $table->text('change_notes')->nullable();
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 4. Invoices Ledger
        Schema::create('saas_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('saas_subscription_id')->constrained('saas_subscriptions')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0.00); // Tracks progressive payments
            $table->string('currency', 10)->default('USD');
            $table->string('status')->default('unpaid'); // unpaid, paid, partially_paid, void
            $table->boolean('is_locked')->default(false);
            $table->text('payment_instructions')->nullable();
            $table->string('integrity_hash', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Invoice Items
        Schema::create('saas_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_invoice_id')->constrained('saas_invoices')->onDelete('cascade');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        // 6. Platform Billing Configuration Settings Table (Merchant Parameters)
        Schema::create('saas_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('bank_account_name');
            $table->string('bank_account_number');
            $table->string('bank_branch_code')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('paynow_integration_id')->nullable();
            $table->string('paynow_integration_key')->nullable();
            $table->string('support_email')->default('billing@schoolcore.test');
            $table->timestamps();
        });

        // 7. Transactions Registry
        Schema::create('saas_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('saas_invoice_id')->nullable()->constrained('saas_invoices')->onDelete('set null');
            $table->string('payment_gateway_key')->index();
            $table->string('transaction_reference')->unique(); // Stores Paynow's poll URL or reference
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->timestamp('processed_at')->nullable();
            $table->json('gateway_raw_response')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 8. Platform Generated Receipts
        Schema::create('saas_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('saas_invoice_id')->constrained('saas_invoices')->onDelete('cascade');
            $table->foreignId('saas_transaction_id')->constrained('saas_transactions')->onDelete('cascade');
            $table->string('receipt_number')->unique();
            $table->decimal('amount_paid', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->timestamp('issued_at');
            $table->string('verification_token', 64)->unique();
            $table->timestamps();
        });

        // Seed default system configurations & initial base plan using environment variables
        DB::table('saas_billing_settings')->insert([
            'bank_name' => 'Steward Bank Zimbabwe',
            'bank_account_name' => 'SchoolCore Technologies Pvt Ltd',
            'bank_account_number' => '1002345678',
            'bank_branch_code' => '2105',
            'bank_swift_code' => 'STWBTX2X',
            'paynow_integration_id' => env('PAYNOW_INTEGRATION_ID', '12345'),
            'paynow_integration_key' => env('PAYNOW_INTEGRATION_KEY', 'sample-key-uuid-9923'),
            'support_email' => 'billing@schoolcore.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('saas_plans')->insert([
            'uuid' => (string) Str::uuid(),
            'name' => 'Standard SchoolCore License',
            'slug' => 'standard-schoolcore-license',
            'description' => 'Full administrative access package covering all ERP workspace modules.',
            'price_monthly' => 10.00,
            'currency' => 'USD',
            'trial_days' => 14,
            'grace_days' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_receipts');
        Schema::dropIfExists('saas_transactions');
        Schema::dropIfExists('saas_billing_settings');
        Schema::dropIfExists('saas_invoice_items');
        Schema::dropIfExists('saas_invoices');
        Schema::dropIfExists('saas_subscription_histories');
        Schema::dropIfExists('saas_subscriptions');
        Schema::dropIfExists('saas_plans');
    }
};
