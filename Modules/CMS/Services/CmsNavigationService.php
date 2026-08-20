<?php

namespace Modules\CMS\Services;

use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsWebsite;

/** Keeps the public header in lockstep with the pages a school manages. */
class CmsNavigationService
{
    public static function sync(CmsWebsite $website): void
    {
        $items = CmsPage::withoutGlobalScopes()
            ->where('cms_website_id', $website->id)
            ->where('school_id', $website->school_id)
            ->where('is_published', true)
            ->where('hide_from_nav', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (CmsPage $page) => [
                'label' => $page->title,
                'url' => $page->is_homepage ? route('tenant.home') : route('cms-render', ['slug' => $page->slug]),
            ])
            ->all();

        $website->forceFill(['navigation_menu' => $items])->saveQuietly();
    }
}
