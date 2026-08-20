<?php

namespace App\Navigation;

use App\Services\ModuleVisibilityManager;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager as BaseNavigationManager;

/**
 * Sidebar navigation manager that hides every item belonging to a module whose
 * master toggle is switched off in System Settings -> Manage Modules.
 *
 * It runs the stock Filament navigation pipeline (permission + canAccess
 * filtering happens there), then drops any item whose URL maps back to a
 * module tab (via ModuleNavigationService) whose module is not visible.
 * Groups left with no visible items are removed entirely so the app panel
 * reflects exactly the modules the school has enabled.
 */
class ModuleAwareNavigationManager extends BaseNavigationManager
{
    public function get(): array
    {
        $service = app(ModuleNavigationService::class);

        $parentGroups = parent::get();

        return collect($parentGroups)
            ->map(function (NavigationGroup $group) use ($service): NavigationGroup {
                $group->items(
                    collect($group->getItems())
                        ->filter(fn (NavigationItem $item): bool => $this->isItemVisible($item, $service))
                        ->values()
                        ->all(),
                );

                return $group;
            })
            ->filter(fn (NavigationGroup $group): bool => filled($group->getItems()))
            ->values()
            ->all();
    }

    protected function isItemVisible(NavigationItem $item, ModuleNavigationService $service): bool
    {
        $url = $item->getUrl();
        if (blank($url)) {
            return true;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return true;
        }

        $moduleSlug = $service->moduleSlugForPath($path);

        if ($moduleSlug === null) {
            return true;
        }

        $visible = ModuleVisibilityManager::isModuleVisible($moduleSlug);

        return $visible;
    }
}
