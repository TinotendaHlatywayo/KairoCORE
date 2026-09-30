<?php

namespace App\Filament\App\Concerns;

use App\Security\CapabilityCatalog;
use App\Services\ModuleVisibilityManager;
use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Services\PermissionRegistry;

/**
 * Permission-based access gate for tenant (workspace) resources and pages.
 *
 * There is no hand-maintained list of classes here any more. Every resource and
 * page in the workspace is described once, in the capability catalogue, which
 * already knows its module, its category, and the operations it supports. A
 * class that is not in the catalogue is denied rather than waved through, so a
 * newly added screen can never be reachable by accident before somebody has
 * decided who may see it.
 */
trait ModulePermissionAccess
{
    /**
     * Can this person open this page at all?
     *
     * Reaching a page is not the same as being allowed to do anything on it, so
     * this only asks for the page's `view` capability. A module that the school
     * has switched off entirely stays switched off.
     */
    public static function canAccess(): bool
    {
        if (ModuleVisibilityManager::isSchoolAdmin()) {
            return true;
        }

        $page = CapabilityCatalog::pageForClass(static::class);

        // Not in the catalogue: deny. Fail closed by design.
        if ($page === null) {
            return false;
        }

        $module = $page['module'];

        // A module the school has not enabled stays hidden regardless of role.
        if ($module !== 'universal' && ! ModuleVisibilityManager::isModuleVisible($module)) {
            return false;
        }

        // Nor does a part of a module the school has individually switched off.
        if (! CapabilityCatalog::isPageTenantEnabled($module, $page['key'])) {
            return false;
        }

        return PermissionRegistry::checkAny(CapabilityCatalog::accessKeysFor($module, $page['key']));
    }

    /**
     * Can this person perform an operation on this page?
     *
     * Used to hide individual buttons — Create, Edit, Delete, Export and the
     * page's own extras such as Publish or Approve — so a table never offers an
     * action that will be refused.
     */
    public static function canPerform(string $action): bool
    {
        if (ModuleVisibilityManager::isSchoolAdmin()) {
            return true;
        }

        $page = CapabilityCatalog::pageForClass(static::class);

        if ($page === null) {
            return false;
        }

        return PermissionRegistry::checkPermission(
            CapabilityCatalog::pagePermissionKey($page['module'], $page['key'], $action)
        );
    }

    /**
     * Every Filament ability for this resource, answered from the catalogue.
     *
     * Filament decides whether to show Create, Edit, Delete, Duplicate, Restore
     * and View actions — in a table, in a header, or on a whole page — by
     * calling these. Overriding this one method therefore gates all of them in
     * the same place, rather than each resource having to remember to hide each
     * button.
     *
     * Where a model policy exists it is still consulted, so record-level rules
     * a school has written (only your own records, only your department) keep
     * working on top of the module-level decision.
     *
     * @param  Model|null  $record
     */
    public static function can(string $ability, $record = null): bool
    {
        if (! static::canPerform(self::abilityToOperation($ability))) {
            return false;
        }

        return parent::can($ability, $record);
    }

    /**
     * Filament's ability names, in terms of the operations a page declares.
     *
     * Filament distinguishes abilities that a page treats as one thing — a
     * duplicate is a create, a restore is an edit — so both land on the same
     * capability the permission editor shows.
     */
    protected static function abilityToOperation(string $ability): string
    {
        return match ($ability) {
            'viewAny', 'view' => 'view',
            'create', 'replicate' => 'create',
            'update', 'edit', 'restore', 'reorder' => 'edit',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny' => 'delete',
            default => $ability,
        };
    }

    /**
     * Permission keys for every operation this page supports.
     *
     * @return array<string, string> action => permission key
     */
    public static function pagePermissionKeys(): array
    {
        $page = CapabilityCatalog::pageForClass(static::class);

        if ($page === null) {
            return [];
        }

        return array_combine(
            $page['actions'],
            array_map(
                fn (string $action): string => CapabilityCatalog::pagePermissionKey($page['module'], $page['key'], $action),
                $page['actions']
            )
        ) ?: [];
    }

    /**
     * The catalogue record for this page, for any screen that wants to explain
     * itself — the page's purpose, its category, its operations.
     */
    public static function capabilityRecord(): ?array
    {
        return CapabilityCatalog::pageForClass(static::class);
    }
}
