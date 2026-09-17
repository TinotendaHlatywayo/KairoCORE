<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('fee_structures', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->change();
            $table->foreignId('term_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('fee_structures', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable(false)->change();
            $table->foreignId('term_id')->nullable(false)->change();
        });
    }
};
