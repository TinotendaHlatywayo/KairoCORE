<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_issuances', function (Blueprint $table) {
            // Drop foreign key first, make nullable, and restore relation
            $table->dropForeign(['inventory_location_id']);
            $table->unsignedBigInteger('inventory_location_id')->nullable()->change();
            $table->foreign('inventory_location_id')
                ->references('id')
                ->on('inventory_locations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_issuances', function (Blueprint $table) {
            $table->dropForeign(['inventory_location_id']);
            $table->unsignedBigInteger('inventory_location_id')->nullable(false)->change();
            $table->foreign('inventory_location_id')
                ->references('id')
                ->on('inventory_locations')
                ->onDelete('cascade');
        });
    }
};
