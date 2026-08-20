<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_report_templates', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('layout_settings');
            $table->boolean('is_favorite')->default(false)->after('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('enterprise_report_templates', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'is_favorite']);
        });
    }
};
