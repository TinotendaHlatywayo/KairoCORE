<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL error messages can embed entire failing statements, which overflowed
 * the TEXT (64 KB) column and triggered a secondary "Data too long for column
 * 'error_details'" error that masked the real restore failure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_restore_logs', function (Blueprint $table) {
            $table->mediumText('error_details')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('platform_restore_logs', function (Blueprint $table) {
            $table->text('error_details')->nullable()->change();
        });
    }
};
