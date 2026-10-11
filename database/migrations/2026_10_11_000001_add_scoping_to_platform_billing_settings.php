<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_billing_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_billing_settings', 'scope_type')) {
                $table->string('scope_type', 32)->default('all')->after('id');
            }
            if (! Schema::hasColumn('platform_billing_settings', 'target_school_ids')) {
                $table->json('target_school_ids')->nullable()->after('scope_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_billing_settings', function (Blueprint $table) {
            foreach (['scope_type', 'target_school_ids'] as $column) {
                if (Schema::hasColumn('platform_billing_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
