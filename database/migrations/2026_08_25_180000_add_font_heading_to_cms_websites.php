<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dedicated global heading font for CMS websites. Falls back to
     * font_secondary when empty, so existing sites keep their current look.
     */
    public function up(): void
    {
        Schema::table('cms_websites', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_websites', 'font_heading')) {
                $table->string('font_heading')->nullable()->after('font_secondary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_websites', function (Blueprint $table) {
            if (Schema::hasColumn('cms_websites', 'font_heading')) {
                $table->dropColumn('font_heading');
            }
        });
    }
};
