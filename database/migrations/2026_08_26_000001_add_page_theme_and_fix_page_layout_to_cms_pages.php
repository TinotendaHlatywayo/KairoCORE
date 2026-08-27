<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cols = DB::getSchemaBuilder()->getColumnListing('cms_pages');

        // Convert page_layout from JSON/longtext to string (if needed)
        if (in_array('page_layout', $cols)) {
            $type = DB::getSchemaBuilder()->getColumnType('cms_pages', 'page_layout');
            if ($type !== 'string') {
                Schema::table('cms_pages', fn (Blueprint $t) => $t->dropColumn('page_layout'));
                Schema::table('cms_pages', fn (Blueprint $t) => $t->string('page_layout', 64)->nullable()->after('page_template'));
            }
        } else {
            Schema::table('cms_pages', fn (Blueprint $t) => $t->string('page_layout', 64)->nullable()->after('page_template'));
        }

        // Add page_theme column (if missing)
        if (! in_array('page_theme', $cols)) {
            Schema::table('cms_pages', fn (Blueprint $t) => $t->string('page_theme', 64)->nullable()->after('page_layout'));
        }

        // Migrate existing data from page_template:
        // - Template keys (e.g. 'coastal-fresh') → page_theme
        // - Layout IDs (e.g. 'about_2', 'home_3') → page_layout
        $validTemplates = [
            'heritage-editorial', 'cinematic-immersive', 'modern-vibrant',
            'minimalist-academic', 'community-warm', 'coastal-fresh',
            'playful-garden', 'emerald-heritage', 'neon-frontier',
            'sunset-international',
            // Legacy keys
            'modern_international', 'minimal_academic', 'warm_christian',
            'stem_academy', 'kindergarten',
        ];

        $pages = DB::table('cms_pages')
            ->whereNotNull('page_template')
            ->where('page_template', '!=', '')
            ->whereNull('page_theme')
            ->get();

        foreach ($pages as $page) {
            $val = $page->page_template;
            $updates = [];
            if (in_array($val, $validTemplates)) {
                $updates['page_theme'] = $val;
            } else {
                $updates['page_layout'] = $val;
            }
            DB::table('cms_pages')->where('id', $page->id)->update($updates);
        }
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('page_theme');
        });
    }
};
