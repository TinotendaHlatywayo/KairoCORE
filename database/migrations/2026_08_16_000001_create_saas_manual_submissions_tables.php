<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Manual bank-deposit / proof-of-payment submissions made by tenants
        Schema::create('saas_manual_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('saas_invoice_id')->nullable()->constrained('saas_invoices')->onDelete('set null');
            $table->string('reference_number');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->date('payment_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_file_path')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('rejection_reason')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
        });

        // 2. Plan feature flags (saas_plans.features relationship)
        Schema::create('saas_plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_plan_id')->constrained('saas_plans')->onDelete('cascade');
            $table->string('feature_key');
            $table->string('feature_value')->nullable();
            $table->timestamps();
        });

        // 3. Billing / invoice addresses for schools
        Schema::create('saas_billing_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('ZW');
            $table->timestamps();
        });

        // 4. SaaS subscription audit trail
        Schema::create('saas_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('event_type')->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload_before')->nullable();
            $table->json('payload_after')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_audit_logs');
        Schema::dropIfExists('saas_billing_addresses');
        Schema::dropIfExists('saas_plan_features');
        Schema::dropIfExists('saas_manual_submissions');
    }
};
