<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('hostel_rooms', 'hostel_id')) {
                $table->foreignId('hostel_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('hostels')
                    ->cascadeOnDelete();
            }
        });

        DB::table('hostel_rooms')
            ->whereNull('hostel_id')
            ->orderBy('id')
            ->chunkById(500, function ($rooms) {
                foreach ($rooms as $room) {
                    $hostelId = DB::table('hostel_floors')
                        ->join('hostel_buildings', 'hostel_buildings.id', '=', 'hostel_floors.building_id')
                        ->where('hostel_floors.id', $room->floor_id)
                        ->value('hostel_buildings.hostel_id');

                    if ($hostelId !== null) {
                        DB::table('hostel_rooms')
                            ->where('id', $room->id)
                            ->update(['hostel_id' => $hostelId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('hostel_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('hostel_rooms', 'hostel_id')) {
                $table->dropForeign(['hostel_id']);
                $table->dropColumn('hostel_id');
            }
        });
    }
};
