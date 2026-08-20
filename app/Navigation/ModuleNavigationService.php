<?php

namespace App\Navigation;

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

        return null;
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

        try {
            if (is_subclass_of($class, Resource::class)) {
                return (bool) $class::canViewAny();
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
