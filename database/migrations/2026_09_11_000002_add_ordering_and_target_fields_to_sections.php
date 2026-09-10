<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Target size tells auto-screening how many students a class should
        // receive (falls back to the existing `capacity` column). rank_order
        // captures "A is the best stream, B next" ordering within a level.
        Schema::table('sections', function (Blueprint $table) {
            $table->integer('target_size')->nullable();
            $table->integer('rank_order')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['target_size', 'rank_order']);
        });
    }
};