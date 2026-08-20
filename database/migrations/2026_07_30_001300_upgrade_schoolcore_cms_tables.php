<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade cms_pages with advanced draft-control and ordering columns
        Schema::table('cms_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_pages', 'draft_blocks')) {
                $table->longText('draft_blocks')->nullable()->after('blocks');
            }
            if (! Schema::hasColumn('cms_pages', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_homepage');
            }
            if (! Schema::hasColumn('cms_pages', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('updated_at');
            }
        });

        // 2. Upgrade cms_websites with site-wide corporate design tokens
        Schema::table('cms_websites', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_websites', 'color_accent')) {
                $table->string('color_accent')->default('#f59e0b')->after('color_secondary');
            }
            if (! Schema::hasColumn('cms_websites', 'color_background')) {
                $table->string('color_background')->default('#ffffff')->after('color_accent');
            }
            if (! Schema::hasColumn('cms_websites', 'color_text')) {
                $table->string('color_text')->default('#1f2937')->after('color_background');
            }
            if (! Schema::hasColumn('cms_websites', 'color_card_bg')) {
                $table->string('color_card_bg')->default('#f9fafb')->after('color_text');
            }
            if (! Schema::hasColumn('cms_websites', 'design_radius')) {
                $table->string('design_radius')->default('md')->after('logo_dark_path');
            }
            if (! Schema::hasColumn('cms_websites', 'design_shadow')) {
                $table->string('design_shadow')->default('md')->after('design_radius');
            }
            if (! Schema::hasColumn('cms_websites', 'design_container')) {
                $table->string('design_container')->default('wide')->after('design_shadow');
            }
            if (! Schema::hasColumn('cms_websites', 'design_button_style')) {
                $table->string('design_button_style')->default('pill')->after('design_container');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn(['draft_blocks', 'sort_order', 'published_at']);
        });

        Schema::table('cms_websites', function (Blueprint $table) {
            $table->dropColumn([
                'color_accent', 'color_background', 'color_text', 'color_card_bg',
                'design_radius', 'design_shadow', 'design_container', 'design_button_style',
            ]);
        });
    }
};
