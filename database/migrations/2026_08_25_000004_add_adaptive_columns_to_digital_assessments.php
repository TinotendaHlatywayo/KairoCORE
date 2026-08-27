<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_assessments', function (Blueprint $table) {
            $table->boolean('adaptive_mode')->default(false)->after('auto_submit');
            $table->unsignedInteger('adaptive_base_difficulty')->default(50)->after('adaptive_mode');
            $table->unsignedInteger('adaptive_window_size')->default(3)->after('adaptive_base_difficulty');
            $table->decimal('adaptive_adjustment_rate', 5, 2)->default(10.00)->after('adaptive_window_size');
        });
    }

    public function down(): void
    {
        Schema::table('digital_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'adaptive_mode',
                'adaptive_base_difficulty',
                'adaptive_window_size',
                'adaptive_adjustment_rate',
            ]);
        });
    }
};
