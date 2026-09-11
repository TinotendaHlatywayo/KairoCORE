<?php

namespace App\Support;

use App\Navigation\ModuleNavigationService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side registry of every workspace page the current user may open.
 *
 * The sidebar search merges this index with DOM-collected sidebar entries so
 * pages that are NOT in the navigation tree stay discoverable (e.g. resources
 * registered with shouldRegisterNavigation = false like Kairo CORE Messages,
 * contextual module tab pages, hidden utility pages).
 *
 * Security: every item passes through canAccess()/isNavigable-style checks, so
 * a user can never see or reach a URL belonging to a panel or module they are
 * not allowed into.
 */
class WorkspaceSearchIndex
{
    /**
     * @return array<int, array{label: string, group: string, url: string, icon: string}>
     */
    public static function items(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        try {
            $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
        } catch (\Throwable) {
            return [];
        }

        $items = [];

        // ── Resources (including non-navigating ones) ────────────────────
        foreach ($panel->getResources() as $resource) {
            try {
                if (! $resource::canAccess()) {
                    continue;
                }

                // Skip resources explicitly removed from navigation ONLY when
                // they are reachable elsewhere? No — include them; that is the
                // whole point of this index.
                $url = $resource::getUrl();

                if (blank($url)) {
                    continue;
                }

                $items[] = [
                    'label' => $resource::getNavigationLabel() ?? class_basename($resource),
                    'group' => $resource::getNavigationGroup() ?? __('General'),
                    'url' => $url,
                    'icon' => $resource::getNavigationIcon() ?? 'heroicon-o-square-3-stack-3d',
                ];
            } catch (\Throwable) {
                // Resource unavailable in this context (missing route, tenant
                // scoping, etc.) — simply not searchable.
            }
        }

        // ── Pages ────────────────────────────────────────────────────────
        foreach ($panel->getPages() as $page) {
            try {
                if (! $page::canAccess()) {
                    continue;
                }

                $url = $page::getUrl();

                if (blank($url)) {
                    continue;
                }

                // Exclude auth screens from search results.
                if (str_contains($url, '/login') || str_contains($url, '/password')) {
                    continue;
                }

                $items[] = [
                    'label' => $page::getNavigationLabel() ?? $page::getTitle() ?? class_basename($page),
                    'group' => $page::getNavigationGroup() ?? __('General'),
                    'url' => $url,
                    'icon' => $page::getNavigationIcon() ?? 'heroicon-o-document-text',
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        // ── Module sub-pages (contextual tab bar + "more" menu) ──────────
        // Whatever page you are on, every sub-page underneath any visible
        // module stays reachable from the search bar. Tabs are permission-
        // filtered by ModuleNavigationService (module visibility + Resource::
        // canViewAny / Page::canAccess), exactly like the module header the
        // user would see on that page.
        try {
            $moduleNav = app(ModuleNavigationService::class);

            foreach ($moduleNav->modules() as $module) {
                foreach (array_merge($moduleNav->moduleTabs($module), $moduleNav->moduleMoreTabs($module)) as $tab) {
                    $url = $tab['url'] ?? null;

                    if (blank($url)) {
                        continue;
                    }

                    $path = parse_url($url, PHP_URL_PATH) ?? $url;

                    $items[] = [
                        'label' => $tab['label'] ?? $module['label'] ?? 'Module',
                        'group' => ($module['label'] ?? '').' · '.($tab['group'] ?? 'Overview'),
                        'url' => '/'.ltrim($path, '/'),
                        'icon' => $tab['icon'] ?? $module['icon'] ?? 'heroicon-o-square-3-stack-3d',
                    ];
                }
            }
        } catch (\Throwable) {
            // Module registry unavailable — sub-pages are simply not searchable.
        }

        // Dedupe by URL (resources may also appear via DOM collection).
        $seen = [];
        $unique = [];
        foreach ($items as $item) {
            $key = parse_url($item['url'], PHP_URL_PATH);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $item;
        }

        return $unique;
    }
}
