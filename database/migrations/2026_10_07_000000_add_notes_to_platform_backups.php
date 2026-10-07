<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_backups', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_backups', 'notes')) {
                $table->string('notes')->nullable()->after('filename');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_backups', function (Blueprint $table) {
            if (Schema::hasColumn('platform_backups', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};