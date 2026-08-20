<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safety check: skip silently if the table has not been created by the modules yet
        if (Schema::hasTable('card_templates')) {
            Schema::table('card_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('card_templates', 'design_theme')) {
                    $table->string('design_theme')->default('classic')->after('name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('card_templates')) {
            Schema::table('card_templates', function (Blueprint $table) {
                if (Schema::hasColumn('card_templates', 'design_theme')) {
                    $table->dropColumn('design_theme');
                }
            });
        }
    }
};
