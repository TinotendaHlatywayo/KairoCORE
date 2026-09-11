<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('procurement_order_items') && !Schema::hasColumn('procurement_order_items', 'is_fixed_asset')) {
            Schema::table('procurement_order_items', function (Blueprint $table) {
                $table->boolean('is_fixed_asset')->default(false)->after('unit_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('procurement_order_items') && Schema::hasColumn('procurement_order_items', 'is_fixed_asset')) {
            Schema::table('procurement_order_items', function (Blueprint $table) {
                $table->dropColumn('is_fixed_asset');
            });
        }
    }
};
