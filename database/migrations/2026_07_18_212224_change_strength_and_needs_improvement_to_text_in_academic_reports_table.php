<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_reports', function (Blueprint $table) {
            // Converts VARCHAR(255) columns to TEXT to support multi-bullet ties
            $table->text('strength')->nullable()->change();
            $table->text('needs_improvement')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('academic_reports', function (Blueprint $table) {
            // Fallback to standard string column if rolled back
            $table->string('strength', 255)->nullable()->change();
            $table->string('needs_improvement', 255)->nullable()->change();
        });
    }
};
