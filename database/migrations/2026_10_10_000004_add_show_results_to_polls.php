<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_polls') && ! Schema::hasColumn('communication_polls', 'show_results')) {
            Schema::table('communication_polls', function (Blueprint $table) {
                $table->boolean('show_results')->default(false)->after('is_anonymous');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_polls') && Schema::hasColumn('communication_polls', 'show_results')) {
            Schema::table('communication_polls', function (Blueprint $table) {
                $table->dropColumn('show_results');
            });
        }
    }
};