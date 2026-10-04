<?php

namespace App\Navigation;

use App\Security\CapabilityCatalog;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;
use Filament\Resources\Resource;

/**
 * Resolves which module the current request belongs to and builds the
 * contextual tab list for that module.
 *
 * Tabs are resolved from real Filament Resource/Page classes so URLs are
 * always correct, and each tab is filtered by the user's permission using
 * Filament's own authorization (Resource::canViewAny / Page::canAccess).
 */
class ModuleNavigationService
{
    protected ?array $modules = null;

    /**
     * Memoized resolved tabs keyed by "moduleSlug|filterPermissions".
     *
     * @var array<string, array>
     */
    protected array $tabsCache = [];

    /**
     * Memoized module visibility results keyed by module slug, so the dozens
     * of per-nav-item visibility lookups collapse into one settings snapshot.
     *
     * @var array<string, bool>
     */
    protected array $moduleVisibilityCache = [];

    /**
     * Memoized URL path => module slug map for catalogued pages that are not
     * contextual tabs, so the sidebar can resolve them without rebuilding the
     * map on every item.
     *
     * @var array<string, string>|null
     */
    protected ?array $unlistedCache = null;

    public function modules(): array
    {
        if ($this->modules === null) {
            $this->modules = [];

            foreach (ModuleNavigation::modules() as $module) {
                $this->modules[$module['slug']] = $module;
            }
        }

        return $this->modules;
    }

    public function moduleBySlug(string $slug): ?array
    {
        return $this->modules()[$slug] ?? null;
    }

    public function moduleForClass(string $class): ?array
    {
        foreach ($this->modules() as $module) {
            foreach (array_merge($module['tabs'] ?? [], $module['more'] ?? []) as $tab) {
                $tabClass = $tab['resource'] ?? $tab['page'] ?? null;

                if ($tabClass === $class) {
                    return $module;
                }
            }
        }

        return null;
    }

    public function currentModule(?string $path = null): ?array
    {
        $path = $path ?? request()->path();

        if (str_contains($path, 'livewire/update') || request()->ajax()) {
            $referer = request()->header('referer');
            if ($referer) {
                $parsedPath = parse_url($referer, PHP_URL_PATH);
                if ($parsedPath) {
                    $path = trim(str_replace('/workspace', '', $parsedPath), '/');
                }
            }
        }

        foreach ($this->modules() as $module) {
            foreach (array_merge($this->moduleTabs($module, false), $this->moduleMoreTabs($module, false)) as $tab) {
                if ($this->pathMatchesTab($path, $tab)) {
                    return $module;
                }
            }
        }

        return null;
    }

    public function pathBelongsToModule(string $path, array $module): bool
    {
        foreach ($this->moduleTabs($module, false) as $tab) {
            if ($this->pathMatchesTab($path, $tab)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve which module slug a navigation URL/path belongs to, or null
     * when the path is not part of any registered module (dashboard, admin,
     * standalone pages). Used by the sidebar filter to hide module landings.
     *
     * Every catalogued page is considered, not only the ones that appear as a
     * contextual tab. A page can sit in the sidebar without being a tab, and if the
     * sidebar filter only knew about tabs then such a page would look unrelated to
     * any module and would stay on screen after its module was switched off.
     */
    public function moduleSlugForPath(string $path): ?string
    {
        foreach ($this->modules() as $module) {
            foreach (array_merge($this->moduleTabs($module, false), $this->moduleMoreTabs($module, false)) as $tab) {
                if ($this->pathMatchesTab($path, $tab)) {
                    return $module['slug'];
                }
            }
        }

        return $this->moduleSlugForUnlistedPage($path);
    }

    /**
     * The module a page belongs to when that page is catalogued but is not one of
     * the module's contextual tabs.
     *
     * The capability catalogue is the single source of truth for which class belongs
     * to which module, so the sidebar asks it rather than keeping its own list. A
     * page's URL is resolved from its class, so both the page's own landing URL and
     * anything nested beneath it match.
     */
    protected function moduleSlugForUnlistedPage(string $path): ?string
    {
        $path = trim($path, '/');

        if ($path === '') {
            return null;
        }

        foreach ($this->unlistedPageUrls() as $url => $slug) {
            if ($path === $url || str_starts_with($path, $url.'/')) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * URL path => module slug for every catalogued page that is not already a tab.
     *
     * @return array<string, string>
     */
    protected function unlistedPageUrls(): array
    {
        if ($this->unlistedCache !== null) {
            return $this->unlistedCache;
        }

        $tabbed = [];

        foreach ($this->modules() as $module) {
            foreach (array_merge($this->moduleTabs($module, false), $this->moduleMoreTabs($module, false)) as $tab) {
                if (isset($tab['class'])) {
                    $tabbed[$tab['class']] = true;
                }
            }
        }

        $urls = [];

        foreach (CapabilityCatalog::classMap() as $class => [$slug, $pageKey]) {
            if (isset($tabbed[$class])) {
                continue;
            }

            $url = $this->urlForClass($class);

            if ($url === null) {
                continue;
            }

            // The first class to claim a path wins, so a module already listed in the
            // navigation keeps it even if another module also offers the same page.
            $urls[$url] ??= $slug;
        }

        // Longest URL first, so a nested page is not swallowed by a shorter prefix.
        uksort($urls, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $this->unlistedCache = $urls;
    }

    /**
     * The URL path a Filament page or resource lands on, or null when it cannot be
     * resolved without a record (e.g. an edit form for a specific model).
     */
    protected function urlForClass(string $class): ?string
    {
        try {
            if (is_subclass_of($class, Resource::class)) {
                $url = $class::getUrl('index');
            } elseif (is_subclass_of($class, Page::class)) {
                $url = $class::getUrl();
            } else {
                return null;
            }

            $path = parse_url((string) $url, PHP_URL_PATH);

            return filled($path) ? $this->normalizeUrl((string) $path) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function moduleTabs(array $module, bool $filterPermissions = true): array
    {
        $cacheKey = ($module['slug'] ?? 'module').'|tabs|'.($filterPermissions ? '1' : '0');

        if (isset($this->tabsCache[$cacheKey])) {
            return $this->tabsCache[$cacheKey];
        }

        $tabs = [];

        foreach (($module['tabs'] ?? []) as $tab) {
            $tab = $this->resolveTab($tab);
            if ($tab === null || ($filterPermissions && ! $this->tabAccessible($tab, $module['slug'] ?? null))) {
                continue;
            }
            $tabs[] = $tab;
        }

        return $this->tabsCache[$cacheKey] = $tabs;
    }

    public function moduleMoreTabs(array $module, bool $filterPermissions = true): array
    {
        $cacheKey = ($module['slug'] ?? 'module').'|more|'.($filterPermissions ? '1' : '0');

        if (isset($this->tabsCache[$cacheKey])) {
            return $this->tabsCache[$cacheKey];
        }

        $tabs = [];

        foreach (($module['more'] ?? []) as $tab) {
            $tab = $this->resolveTab($tab);
            if ($tab === null || ($filterPermissions && ! $this->tabAccessible($tab, $module['slug'] ?? null))) {
                continue;
            }
            $tabs[] = $tab;
        }

        return $this->tabsCache[$cacheKey] = $tabs;
    }

    public function activeTabLabel(array $module, ?string $path = null): ?string
    {
        $path = $path ?? request()->path();

        foreach (array_merge($this->moduleTabs($module, false), $this->moduleMoreTabs($module)) as $tab) {
            if ($this->pathMatchesTab($path, $tab)) {
                return $tab['label'];
            }
        }

        return null;
    }

    /**
     * Resolve the category group of the tab currently active for a module.
     * Category hubs use this to keep their sidebar item highlighted while any
     * page within the same category group is open.
     */
    public function activeTabGroup(array $module, ?string $path = null): ?string
    {
        $path = $path ?? request()->path();

        foreach (array_merge($this->moduleTabs($module, false), $this->moduleMoreTabs($module, false)) as $tab) {
            if ($this->pathMatchesTab($path, $tab)) {
                return $tab['group'] ?? null;
            }
        }

        return null;
    }

    /**
     * Whether the tab currently active for a module belongs to the given
     * category group (so a category hub can stay highlighted as its user
     * moves between pages in that category).
     */
    public function currentTabInGroup(array $module, string $group, ?string $path = null): bool
    {
        $activeGroup = $this->activeTabGroup($module, $path);

        return $activeGroup !== null && $activeGroup === $group;
    }

    protected function pathMatchesTab(string $path, array $tab): bool
    {
        $url = parse_url((string) ($tab['url'] ?? ''), PHP_URL_PATH) ?? '';
        $url = trim($url, '/');

        if ($url === '') {
            return false;
        }

        return $path === $url || str_starts_with($path, $url.'/');
    }

    protected function tabAccessible(array $tab, ?string $moduleSlug = null): bool
    {
        // Gate the tab by the module master (and sub-page) visibility so the
        // contextual module header stays consistent with the sidebar.
        if ($moduleSlug !== null) {
            if (! $this->moduleVisible($moduleSlug)) {
                return false;
            }
        } else {
            $class = $tab['class'] ?? null;
            if ($class !== null && ! ModuleVisibilityManager::isResourceVisible($class)) {
                return false;
            }
        }

        $class = $tab['class'] ?? null;

        if ($class === null) {
            return true;
        }

        if (! ModuleVisibilityManager::isResourceVisible($class)) {
            return false;
        }

        // The class's own access check is the single source of truth for a
        // tab. Once that resolves through the capability catalogue it already
        // covers both the module toggle and this person's permissions, so there
        // is no second rule to keep in step here.
        try {
            if (is_subclass_of($class, Resource::class)) {
                return (bool) $class::canAccess();
            }

            if (is_subclass_of($class, Page::class)) {
                return (bool) $class::canAccess();
            }
        } catch (\Throwable $e) {
            return true;
        }

        return true;
    }

    /**
     * Memoized module visibility lookup so the per-nav-item and per-tab
     * calls reuse a single settings snapshot instead of re-querying.
     */
    protected function moduleVisible(string $moduleSlug): bool
    {
        return $this->moduleVisibilityCache[$moduleSlug]
            ??= ModuleVisibilityManager::isModuleVisible($moduleSlug);
    }

    protected function resolveTab(array $tab): ?array
    {
        try {
            if (isset($tab['resource'])) {
                $class = $tab['resource'];
                if (! is_subclass_of($class, Resource::class)) {
                    return null;
                }
                $tab['class'] = $class;
                $tab['url'] = $class::getUrl('index');
                $tab['url'] = $this->normalizeUrl($tab['url']);

                return $tab;
            }

            if (isset($tab['page'])) {
                $class = $tab['page'];
                if (! is_subclass_of($class, Page::class)) {
                    return null;
                }
                $tab['class'] = $class;
                $tab['url'] = $class::getUrl();
                $tab['url'] = $this->normalizeUrl($tab['url']);

                return $tab;
            }
        } catch (\Throwable $e) {
            // Pages whose URL requires a route parameter (e.g. the CMS visual
            // builder) cannot be represented as a contextual tab.
            return null;
        }

        return null;
    }

    protected function normalizeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return trim($path, '/');
    }
}
