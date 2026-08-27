<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_allocations', function (Blueprint $table) {
            $table->foreignId('room_id')
                ->nullable()
                ->after('bed_id')
                ->constrained('hostel_rooms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hostel_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
        });
    }
};
