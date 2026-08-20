<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. System Settings Registry
        if (! Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->string('group', 100);
                $table->string('key', 150);
                $table->longText('value')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'group', 'key'], 'uq_sys_setting_key');
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            });
        }

        // 2. Custom Roles with JSON permission storage
        if (! Schema::hasTable('custom_roles')) {
            Schema::create('custom_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->json('permissions'); // Array of active permission strings
                $table->boolean('is_system')->default(false);
                $table->timestamps();

                $table->unique(['school_id', 'name'], 'uq_custom_role_name');
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            });
        }

        // 3. Departments & Budget Centers
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->string('name', 255);
                $table->string('code', 100);
                $table->string('type', 50)->default('academic'); // academic, administrative, support
                $table->unsignedBigInteger('head_user_id')->nullable();
                $table->string('budget_code', 100)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();

                $table->unique(['school_id', 'code'], 'uq_dept_code');
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            });
        }

        // 4. Comprehensive Audit & Security Logs
        if (! Schema::hasTable('system_audit_logs')) {
            Schema::create('system_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 255);
                $table->string('module', 100);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->string('outcome', 50)->default('success');
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // 5. Connect Users to Custom Roles safely
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'custom_role_id')) {
                    $table->unsignedBigInteger('custom_role_id')->nullable()->after('school_id');
                    $table->foreign('custom_role_id')->references('id')->on('custom_roles')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['custom_role_id']);
                $table->dropColumn('custom_role_id');
            });
        }
        Schema::dropIfExists('system_audit_logs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('custom_roles');
        Schema::dropIfExists('system_settings');
    }
};
