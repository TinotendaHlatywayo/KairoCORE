<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_announcements')) {
            return;
        }

        Schema::table('platform_announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_announcements', 'audience')) {
                // all | admins | tenants | users
                $table->string('audience', 20)->default('all')->after('type')->index();
            }

            if (! Schema::hasColumn('platform_announcements', 'target_school_ids')) {
                $table->json('target_school_ids')->nullable()->after('target_plans');
            }

            if (! Schema::hasColumn('platform_announcements', 'target_user_ids')) {
                $table->json('target_user_ids')->nullable()->after('target_school_ids');
            }

            if (! Schema::hasColumn('platform_announcements', 'target_roles')) {
                $table->json('target_roles')->nullable()->after('target_user_ids');
            }

            if (! Schema::hasColumn('platform_announcements', 'show_in_banner')) {
                $table->boolean('show_in_banner')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_announcements')) {
            return;
        }

        Schema::table('platform_announcements', function (Blueprint $table) {
            $columns = array_values(array_filter([
                'audience',
                'target_school_ids',
                'target_user_ids',
                'target_roles',
                'show_in_banner',
            ], fn (string $column): bool => Schema::hasColumn('platform_announcements', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
