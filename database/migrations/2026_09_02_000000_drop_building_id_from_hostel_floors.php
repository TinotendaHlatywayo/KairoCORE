<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hostel_floors', 'building_id')) {
            return;
        }

        try {
            Schema::table('hostel_floors', function (Blueprint $table) {
                $table->dropForeign(['building_id']);
                $table->dropUnique('uq_hostel_flr_num');
                $table->dropColumn('building_id');
            });
        } catch (\Throwable $e) {
            // Fresh installs have no building_id column to drop; existing
            // installations are repaired by 2026_09_06_100000.
        }
    }

    public function down(): void
    {
    }
};