<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_websites', function (Blueprint $table) {
            $table->string('cms_mode', 10)->default('simple')->after('active_template')
                ->comment('simple = content-only editing; advanced = full visual builder');
        });
    }

    public function down(): void
    {
        Schema::table('cms_websites', function (Blueprint $table) {
            $table->dropColumn('cms_mode');
        });
    }
};
