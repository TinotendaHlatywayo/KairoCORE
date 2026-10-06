<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a backup be either the whole system (scope = system) or a single tenant
 * (scope = tenant, with the owning school_id). A tenant restore only replaces
 * that tenant's rows instead of dropping and recreating every table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_backups', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_backups', 'scope')) {
                $table->string('scope', 20)->default('system')->after('filename');
            }
            if (! Schema::hasColumn('platform_backups', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('scope')->index();
            }
        });

        Schema::table('platform_restore_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_restore_logs', 'scope')) {
                $table->string('scope', 20)->default('system')->after('backup_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_backups', function (Blueprint $table) {
            foreach (['scope', 'school_id'] as $column) {
                if (Schema::hasColumn('platform_backups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('platform_restore_logs', function (Blueprint $table) {
            if (Schema::hasColumn('platform_restore_logs', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};