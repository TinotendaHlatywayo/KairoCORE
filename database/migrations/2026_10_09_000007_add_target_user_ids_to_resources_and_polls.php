<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_resources') && !Schema::hasColumn('communication_resources', 'target_user_ids')) {
            Schema::table('communication_resources', function (Blueprint $table) {
                $table->json('target_user_ids')->nullable()->after('visibility');
            });
        }

        if (Schema::hasTable('communication_polls') && !Schema::hasColumn('communication_polls', 'target_user_ids')) {
            Schema::table('communication_polls', function (Blueprint $table) {
                $table->json('target_user_ids')->nullable()->after('target_roles');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_resources') && Schema::hasColumn('communication_resources', 'target_user_ids')) {
            Schema::table('communication_resources', function (Blueprint $table) {
                $table->dropColumn('target_user_ids');
            });
        }

        if (Schema::hasTable('communication_polls') && Schema::hasColumn('communication_polls', 'target_user_ids')) {
            Schema::table('communication_polls', function (Blueprint $table) {
                $table->dropColumn('target_user_ids');
            });
        }
    }
};
