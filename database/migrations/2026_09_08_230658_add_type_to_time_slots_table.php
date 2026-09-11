<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->string('type')->default('teaching')->after('name');
        });

        DB::table('time_slots')->where('is_break', true)
            ->where('type', 'teaching')
            ->update(['type' => 'break']);
    }

    public function down(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};