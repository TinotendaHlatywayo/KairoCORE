<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'communication_announcements' => 'system_only',
            'communication_events' => 'system_only',
            'communication_resources' => 'system_only',
            'communication_polls' => 'system_only',
        ] as $table => $default) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'channel')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($default) {
                $table->string('channel', 20)->default($default);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'communication_announcements',
            'communication_events',
            'communication_resources',
            'communication_polls',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'channel')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('channel');
                });
            }
        }
    }
};
