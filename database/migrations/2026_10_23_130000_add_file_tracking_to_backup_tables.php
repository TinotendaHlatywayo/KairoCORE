<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A recovery archive used to hold nothing but the SQL dump, so restoring one
 * brought the rows back but not a single photo, logo, favicon or library file.
 *
 * `include_files` records whether the archive also carries the uploaded files
 * (storage/app/** and the public website assets), and `summary` reports what a
 * restore actually put back so the operator can see media was covered too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_backups', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_backups', 'include_files')) {
                $table->boolean('include_files')->default(true)->after('notes');
            }
        });

        // Every archive that already exists was written when the dump was the
        // whole payload, so claiming otherwise would put a "files included"
        // badge on a SQL-only zip.
        DB::table('platform_backups')->update(['include_files' => false]);

        Schema::table('platform_restore_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_restore_logs', 'summary')) {
                $table->string('summary', 255)->nullable()->after('error_details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_backups', function (Blueprint $table) {
            if (Schema::hasColumn('platform_backups', 'include_files')) {
                $table->dropColumn('include_files');
            }
        });

        Schema::table('platform_restore_logs', function (Blueprint $table) {
            if (Schema::hasColumn('platform_restore_logs', 'summary')) {
                $table->dropColumn('summary');
            }
        });
    }
};
