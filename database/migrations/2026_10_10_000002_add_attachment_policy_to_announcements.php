<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_announcements') && ! Schema::hasColumn('communication_announcements', 'attachment_policy')) {
            Schema::table('communication_announcements', function (Blueprint $table) {
                $table->string('attachment_policy', 20)->default('view_download')->after('display_style');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_announcements') && Schema::hasColumn('communication_announcements', 'attachment_policy')) {
            Schema::table('communication_announcements', function (Blueprint $table) {
                $table->dropColumn('attachment_policy');
            });
        }
    }
};