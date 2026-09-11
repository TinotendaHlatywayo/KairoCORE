<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_subject', function (Blueprint $table) {
            $table->unsignedInteger('double_periods_per_week')->default(0)->after('periods_per_week');
            $table->unsignedInteger('triple_periods_per_week')->default(0)->after('double_periods_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('course_subject', function (Blueprint $table) {
            $table->dropColumn(['double_periods_per_week', 'triple_periods_per_week']);
        });
    }
};