<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('terms', 'is_active')) {
            Schema::table('terms', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('end_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('terms', 'is_active')) {
            Schema::table('terms', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};