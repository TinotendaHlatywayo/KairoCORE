<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_announcements') && !Schema::hasColumn('communication_announcements', 'target_user_ids')) {
            Schema::table('communication_announcements', function (Blueprint $table) {
                $table->json('target_user_ids')->nullable()->after('visibility');
            });
        }

        if (Schema::hasTable('communication_events') && !Schema::hasColumn('communication_events', 'target_user_ids')) {
            Schema::table('communication_events', function (Blueprint $table) {
                $table->json('target_user_ids')->nullable()->after('target_roles');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_announcements') && Schema::hasColumn('communication_announcements', 'target_user_ids')) {
            Schema::table('communication_announcements', function (Blueprint $table) {
                $table->dropColumn('target_user_ids');
            });
        }

        if (Schema::hasTable('communication_events') && Schema::hasColumn('communication_events', 'target_user_ids')) {
            Schema::table('communication_events', function (Blueprint $table) {
                $table->dropColumn('target_user_ids');
            });
        }
    }
};
