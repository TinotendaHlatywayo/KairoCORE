<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks the per-school "System Administrator" shadow accounts that the platform
 * uses to enter a tenant workspace.
 *
 * These are real users rows (so every existing permission, role and tenant
 * check keeps working untouched), but they are provisioned by the platform
 * rather than by the school, which is why they need to be distinguishable:
 * excluded from the school's admin counts and identifiable in the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_platform_managed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_platform_managed')
                    ->default(false)
                    ->after('account_status')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_platform_managed')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['is_platform_managed']);
                $table->dropColumn('is_platform_managed');
            });
        }
    }
};
