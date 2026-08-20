<?php

namespace App\Filament\App\Concerns;

use App\Navigation\ModuleNavigationService;
use Filament\Resources\Resource;

/**
 * Keeps a module's landing sidebar item highlighted while the user is on any
 * page that belongs to the same module (e.g. "Online Admissions" stays active
 * when the user opens "Admission Settings"), instead of only when the item's
 * own page is open.
 *
 * Applied to every navigation-registered landing resource / page in the app
 * panel so the current module is always visible in the sidebar.
 */
trait ModuleAwareActiveNavigation
{
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        $module = app(ModuleNavigationService::class)->moduleForClass(static::class);

        if ($module === null || static::class !== ($module['tabs'][0]['resource'] ?? $module['tabs'][0]['page'] ?? null)) {
            return $items;
        }

        foreach ($items as $item) {
            $item->isActiveWhen(static::moduleActiveClosure($module));
        }

        return $items;
    }

    protected static function moduleActiveClosure(array $module): \Closure
    {
        return function () use ($module): bool {
            if (static::navigationActiveRoutePattern() !== null && request()->routeIs(static::navigationActiveRoutePattern())) {
                return true;
            }

            $current = app(ModuleNavigationService::class)->currentModule();

            return ($current['slug'] ?? null) === ($module['slug'] ?? null);
        };
    }

    protected static function navigationActiveRoutePattern(): ?string
    {
        if (is_subclass_of(static::class, Resource::class)) {
            return static::getRouteBaseName().'.*';
        }

        return static::getNavigationItemActiveRoutePattern();
    }
}
