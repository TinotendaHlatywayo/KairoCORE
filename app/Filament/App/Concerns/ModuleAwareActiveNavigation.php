<?php

namespace App\Filament\App\Concerns;

use App\Navigation\ModuleNavigationService;
use Filament\Resources\Resource;

/**
 * Keeps a sidebar item highlighted for the whole category (group) it belongs
 * to, instead of only while its own page is open.
 *
 * Applied to navigation-registered category hub pages so that, for example,
 * "Student Billing & Revenue" (Finance) stays highlighted while the user opens
 * Invoices, Payment Proofs, Fee Waivers etc., and "Payroll & Compensation"
 * (HR & Payroll) stays highlighted while the user opens Payroll Periods,
 * Salary Grades or Staff Loans.
 *
 * When the class maps to a module tab that has a category group, the item is
 * highlighted while the current page's active tab belongs to that same group.
 * For tab-less landing pages it falls back to highlighting the whole module.
 */
trait ModuleAwareActiveNavigation
{
    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        $service = app(ModuleNavigationService::class);
        $module = $service->moduleForClass(static::class);

        if ($module === null) {
            return $items;
        }

        $category = static::categoryGroup($module);

        foreach ($items as $item) {
            $item->isActiveWhen(static::moduleActiveClosure($module, $category));
        }

        return $items;
    }

    /**
     * The category group this class belongs to, read straight from the module
     * registry so it always matches the group used when resolving the active tab.
     */
    protected static function categoryGroup(array $module): ?string
    {
        foreach (array_merge($module['tabs'] ?? [], $module['more'] ?? []) as $tab) {
            if (($tab['resource'] ?? $tab['page'] ?? null) === static::class) {
                return $tab['group'] ?? null;
            }
        }

        return null;
    }

    protected static function moduleActiveClosure(array $module, ?string $category): \Closure
    {
        return function () use ($module, $category): bool {
            if (static::navigationActiveRoutePattern() !== null && request()->routeIs(static::navigationActiveRoutePattern())) {
                return true;
            }

            $service = app(ModuleNavigationService::class);

            if (($service->currentModule()['slug'] ?? null) !== ($module['slug'] ?? null)) {
                return false;
            }

            // A category hub is active while any page in the same group is open.
            if ($category !== null) {
                return $service->currentTabInGroup($module, $category);
            }

            // No category resolved (landing page): keep the whole module active.
            return true;
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
