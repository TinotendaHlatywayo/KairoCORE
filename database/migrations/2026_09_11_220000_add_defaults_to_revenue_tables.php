<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('revenue_categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
        });

        Schema::connection('mysql')->table('revenue_streams', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('revenue_categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(null)->change();
        });

        Schema::connection('mysql')->table('revenue_streams', function (Blueprint $table) {
            $table->boolean('is_active')->default(null)->change();
        });
    }
};