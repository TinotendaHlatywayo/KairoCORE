<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_floors', function (Blueprint $table) {
            if (!Schema::hasColumn('hostel_floors', 'hostel_id')) {
                $table->foreignId('hostel_id')->nullable()->after('school_id')->constrained('hostels')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hostel_floors', function (Blueprint $table) {
            if (Schema::hasColumn('hostel_floors', 'hostel_id')) {
                $table->dropForeign(['hostel_id']);
                $table->dropColumn('hostel_id');
            }
        });
    }
};
