<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. SaaS Subscription Plans
        if (! Schema::hasTable('saas_plans')) {
            Schema::create('saas_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('code', 100)->unique();
                $table->text('description')->nullable();
                $table->decimal('price_monthly_usd', 15, 2)->default(0.00);
                $table->decimal('price_yearly_usd', 15, 2)->default(0.00);
                $table->unsignedInteger('max_students')->default(100);
                $table->unsignedInteger('max_users')->default(15);
                $table->unsignedBigInteger('storage_limit_gb')->default(10);
                $table->json('features'); // Allowed modules/features flags array
                $table->string('status', 50)->default('stable'); // stable, beta, preview, deprecated
                $table->timestamps();
            });
        }

        // 2. School Subscription Records
        if (! Schema::hasTable('school_subscriptions')) {
            Schema::create('school_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('saas_plan_id');
                $table->string('status', 50)->default('active'); // active, trial, expired, grace_period, suspended
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->unsignedBigInteger('storage_limit_gb')->default(10);
                $table->unsignedInteger('max_users')->default(15);
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('saas_plan_id')->references('id')->on('saas_plans')->onDelete('cascade');
            });
        }

        // 3. SaaS Revenue/Transaction Ledger
        if (! Schema::hasTable('saas_transactions')) {
            Schema::create('saas_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('saas_plan_id')->nullable();
                $table->string('transaction_number', 100)->unique();
                $table->decimal('amount_usd', 15, 2);
                $table->string('gateway', 100)->default('paynow'); // paynow, stripe, paypal
                $table->string('gateway_reference', 255)->nullable();
                $table->string('status', 50)->default('paid'); // paid, failed, refunded
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('saas_plan_id')->references('id')->on('saas_plans')->onDelete('set null');
            });
        }

        // 4. Tenant Health Monitoring Ledger
        if (! Schema::hasTable('tenant_healths')) {
            Schema::create('tenant_healths', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->decimal('uptime_percentage', 5, 2)->default(100.00);
                $table->unsignedInteger('response_time_ms')->default(150);
                $table->unsignedBigInteger('storage_used_bytes')->default(0);
                $table->unsignedInteger('active_users_count')->default(0);
                $table->json('health_logs')->nullable(); // CPU load, last error log, database size
                $table->timestamps();

                $table->unique('school_id');
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            });
        }

        // 5. Global Platform Configurations & Branding
        if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->id();
                $table->string('group', 100);
                $table->string('key', 150);
                $table->longText('value')->nullable();
                $table->timestamps();

                $table->unique(['group', 'key']);
            });
        }

        // 6. Platform-Wide Immutable Audit Trail
        if (! Schema::hasTable('platform_audit_logs')) {
            Schema::create('platform_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 255);
                $table->text('details')->nullable();
                $table->json('payload')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // 7. Platform Maintenance Bulletins & Banners
        if (! Schema::hasTable('platform_announcements')) {
            Schema::create('platform_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('content');
                $table->string('type', 50)->default('info'); // info, warning, danger, maintenance
                $table->json('target_plans')->nullable(); // Target subscription plans
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 8. Global Blueprints Catalog (Website, Reports, ID cards)
        if (! Schema::hasTable('platform_templates')) {
            Schema::create('platform_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('category', 100); // website, report_card, id_card, email, sms
                $table->string('preview_image')->nullable();
                $table->json('configuration_blueprint'); // Default schemas cloned by tenants
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_templates');
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('platform_audit_logs');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('tenant_healths');
        Schema::dropIfExists('saas_transactions');
        Schema::dropIfExists('school_subscriptions');
        Schema::dropIfExists('saas_plans');
    }
};
