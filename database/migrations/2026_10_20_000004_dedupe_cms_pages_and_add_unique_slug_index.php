<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Collapse admissions/apply-online aliases to one row per website
        //    (most recently edited wins), exactly like consolidateAdmissionsAlias.
        $aliasGroups = DB::table('cms_pages')
            ->select('cms_website_id')
            ->whereIn('slug', ['apply-online', 'admissions'])
            ->groupBy('cms_website_id')
            ->pluck('cms_website_id');

        foreach ($aliasGroups as $websiteId) {
            $keepId = DB::table('cms_pages')
                ->where('cms_website_id', $websiteId)
                ->whereIn('slug', ['apply-online', 'admissions'])
                ->orderByDesc('updated_at')
                ->orderByRaw("FIELD(slug, 'apply-online', 'admissions')")
                ->value('id');

            if (! $keepId) {
                continue;
            }

            DB::table('cms_pages')
                ->where('cms_website_id', $websiteId)
                ->whereIn('slug', ['apply-online', 'admissions'])
                ->where('id', '!=', $keepId)
                ->delete();

            DB::table('cms_pages')->where('id', $keepId)->update(['slug' => 'apply-online']);
        }

        // 2) Dedupe any other duplicated slug inside one website (keep newest).
        $dupes = DB::table('cms_pages')
            ->select('cms_website_id', 'slug')
            ->groupBy('cms_website_id', 'slug')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $dupe) {
            $keepId = DB::table('cms_pages')
                ->where('cms_website_id', $dupe->cms_website_id)
                ->where('slug', $dupe->slug)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('cms_pages')
                ->where('cms_website_id', $dupe->cms_website_id)
                ->where('slug', $dupe->slug)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        // 3) Rebuild every website's stored navigation menu so the public nav
        //    stops showing the deleted copies immediately. Mirrors
        //    CmsNavigationService::sync but without route() helpers, which
        //    need a live tenant context unavailable during migrations.
        $websiteIds = DB::table('cms_pages')->distinct()->pluck('cms_website_id');

        foreach ($websiteIds as $websiteId) {
            $website = DB::table('cms_websites')->where('id', $websiteId)->first();
            if (! $website) {
                continue;
            }

            $items = DB::table('cms_pages')
                ->where('cms_website_id', $websiteId)
                ->where('school_id', $website->school_id)
                ->where('is_published', true)
                ->where('hide_from_nav', false)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['title', 'slug', 'is_homepage'])
                ->map(fn ($page) => [
                    'label' => $page->title,
                    'url' => $page->is_homepage ? '/' : '/'.$page->slug,
                ])
                ->all();

            DB::table('cms_websites')->where('id', $websiteId)->update(['navigation_menu' => json_encode($items)]);
        }

        // 4) Guard every future race with a unique index.
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->unique(['cms_website_id', 'slug'], 'cms_pages_website_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropUnique('cms_pages_website_slug_unique');
        });
    }
};
