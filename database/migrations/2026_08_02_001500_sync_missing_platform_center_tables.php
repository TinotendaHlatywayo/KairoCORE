<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Global Platform Configurations & Branding
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

        // 2. Platform-Wide Immutable Audit Trail
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

        // 3. Platform Maintenance Bulletins & Banners
        if (! Schema::hasTable('platform_announcements')) {
            Schema::create('platform_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('content');
                $table->string('type', 50)->default('info'); // info, warning, danger, maintenance
                $table->json('target_plans')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Global Blueprints Catalog (Website, Reports, ID cards)
        if (! Schema::hasTable('platform_templates')) {
            Schema::create('platform_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('category', 100); // website, report_card, id_card, email, sms
                $table->string('preview_image')->nullable();
                $table->json('configuration_blueprint');
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
    }
};
