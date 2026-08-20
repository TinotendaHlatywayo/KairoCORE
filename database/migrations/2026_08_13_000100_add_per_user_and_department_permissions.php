<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user permission overrides + department default permissions + the
 * department_user membership pivot (a user may belong to several departments,
 * each contributing its own default permission bundle).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                // Explicit per-user permission snapshot. NULL = inherit the
                // assigned role's permissions. An array overrides them.
                $table->json('permissions')->nullable()->after('custom_role_id');
            });
        }

        if (! Schema::hasColumn('departments', 'permissions')) {
            Schema::table('departments', function (Blueprint $table) {
                // Default permission bundle granted to every member of this
                // department (clinic, finance, inventory & assets, etc).
                $table->json('permissions')->nullable()->after('budget_code');
            });
        }

        if (! Schema::hasTable('department_user')) {
            Schema::create('department_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('department_id');
                $table->timestamps();

                $table->unique(['user_id', 'department_id'], 'uq_department_user_pair');
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('department_user')) {
            Schema::dropIfExists('department_user');
        }

        if (Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }

        if (Schema::hasColumn('departments', 'permissions')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }
};
