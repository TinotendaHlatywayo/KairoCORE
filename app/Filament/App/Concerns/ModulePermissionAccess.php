<?php

namespace App\Filament\App\Concerns;

use App\Security\CapabilityCatalog;
use App\Security\RoleCatalogue;
use App\Services\ModuleVisibilityManager;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
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

    /**
     * Grouped permission editor tabs for roles and user overrides.
     *
     * @return array<int, Tab>
     */
    public static function permissionEditorTabs(string $fieldPrefix = 'permissions'): array
    {
        $tabs = [];

        foreach (CapabilityCatalog::forEditor() as $mod) {
            $modKey = $mod['key'];
            $tabSchema = [];

            // Module-wide actions
            if (! empty($mod['actions'])) {
                $options = [];
                $descriptions = [];
                foreach ($mod['actions'] as $act) {
                    $options[$act['key']] = $act['label'];
                    $descriptions[$act['key']] = $act['help'];
                }
                $tabSchema[] = Section::make(__('Module-Wide Controls'))
                    ->description($mod['description'])
                    ->schema([
                        CheckboxList::make($fieldPrefix.'_mod_'.$modKey)
                            ->label(__('Module-Wide Actions'))
                            ->options($options)
                            ->descriptions($descriptions)
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable(),
                    ]);
            }

            // Categories & Pages
            foreach ($mod['categories'] as $cat) {
                $catSchema = [];
                foreach ($cat['pages'] as $page) {
                    $pageOptions = [];
                    $pageDescriptions = [];
                    foreach ($page['actions'] as $act) {
                        $pageOptions[$act['key']] = $act['label'];
                        $pageDescriptions[$act['key']] = $act['help'];
                    }

                    $catSchema[] = Section::make($page['label'])
                        ->description($page['purpose'])
                        ->compact()
                        ->schema([
                            CheckboxList::make($fieldPrefix.'_page_'.$modKey.'_'.$page['key'])
                                ->label(__('Page Operations'))
                                ->options($pageOptions)
                                ->descriptions($pageDescriptions)
                                ->columns(3)
                                ->gridDirection('row'),
                        ]);
                }

                $tabSchema[] = Section::make($cat['label'])
                    ->schema($catSchema)
                    ->collapsible();
            }

            $tabs[] = Tab::make($mod['label'])
                ->badge(count($mod['categories']))
                ->schema($tabSchema);
        }

        return $tabs;
    }

    public static function hydratePermissions(array $data, string $sourceKey = 'permissions', string $fieldPrefix = 'permissions'): array
    {
        $perms = (array) ($data[$sourceKey] ?? []);
        $data[$fieldPrefix.'_special'] = array_values(array_intersect($perms, ['*', 'student_portal.access']));

        foreach (CapabilityCatalog::forEditor() as $mod) {
            $modKey = $mod['key'];
            if (! empty($mod['actions'])) {
                $keys = array_column($mod['actions'], 'key');
                $data[$fieldPrefix.'_mod_'.$modKey] = array_values(array_intersect($perms, $keys));
            }
            foreach ($mod['categories'] as $cat) {
                foreach ($cat['pages'] as $page) {
                    $keys = array_column($page['actions'], 'key');
                    $data[$fieldPrefix.'_page_'.$modKey.'_'.$page['key']] = array_values(array_intersect($perms, $keys));
                }
            }
        }

        return $data;
    }

    /**
     * The permission editor tabs for the permissions one user has been given on top
     * of their role.
     *
     * The same structure as the role editor, so an administrator learns it once, but
     * every capability their role already supplies is locked and shown as inherited
     * rather than offered as a choice. Only the gaps are theirs to fill, which makes
     * the screen answer the only question that matters here: "what has this person
     * been given beyond their job?"
     *
     * @return array<int, Tab>
     */
    public static function permissionOverrideTabs(
        callable $inheritedKeys,
        string $fieldPrefix = 'extra_permissions',
    ): array {
        $inherited = $inheritedKeys();

        $tabs = [];
        $anythingToOffer = false;

        foreach (CapabilityCatalog::forEditor() as $mod) {
            $modKey = $mod['key'];
            $tabSchema = [];
            $inheritedHere = false;

            if (! empty($mod['actions'])) {
                [$options, $descriptions] = static::optionMap(
                    array_filter($mod['actions'], fn (array $a): bool => ! self::alreadyGranted($inherited, $a['key']))
                );

                if ($options !== []) {
                    $anythingToOffer = true;
                    $inheritedHere = true;
                    $tabSchema[] = Section::make(__('Module-Wide Controls'))
                        ->description($mod['description'])
                        ->schema([
                            CheckboxList::make($fieldPrefix.'_mod_'.$modKey)
                                ->label(__('Extra Module-Wide Actions'))
                                ->options($options)
                                ->descriptions($descriptions)
                                ->columns(3)
                                ->gridDirection('row')
                                ->bulkToggleable(),
                        ]);
                }
            }

            foreach ($mod['categories'] as $cat) {
                $catSchema = [];
                $catInherited = false;

                foreach ($cat['pages'] as $page) {
                    [$options, $descriptions] = static::optionMap(
                        array_filter($page['actions'], fn (array $a): bool => ! self::alreadyGranted($inherited, $a['key']))
                    );

                    if ($options === []) {
                        continue;
                    }

                    $catInherited = true;
                    $catSchema[] = Section::make($page['label'])
                        ->description($page['purpose'])
                        ->compact()
                        ->schema([
                            CheckboxList::make($fieldPrefix.'_page_'.$modKey.'_'.$page['key'])
                                ->label(__('Extra Operations'))
                                ->options($options)
                                ->descriptions($descriptions)
                                ->columns(3)
                                ->gridDirection('row'),
                        ]);
                }

                if ($catInherited) {
                    $inheritedHere = true;
                    $tabSchema[] = Section::make($cat['label'])
                        ->schema($catSchema)
                        ->collapsible();
                }
            }

            if ($inheritedHere) {
                $anythingToOffer = true;
                $tabs[] = Tab::make($mod['label'])
                    ->badge(count($mod['categories']))
                    ->schema($tabSchema);
            }
        }

        // A user whose role already covers everything gets an explanation instead of
        // an empty editor, rather than a blank screen that looks broken.
        if (! $anythingToOffer) {
            $tabs[] = Tab::make(__('Nothing Extra To Grant'))
                ->schema([
                    Placeholder::make('nothing_extra')
                        ->label(__('This account already has everything its role provides.'))
                        ->content(__('Give this person a different role, or add permissions to the role itself, if they need more.')),
                ]);
        }

        return $tabs;
    }

    /**
     * Does the role already cover this capability, directly or by implication?
     *
     * A teacher who holds the whole Finance module must not be offered Finance
     * page-by-page as if it were new, and the same implication rules that decide
     * real access are used here so the screen cannot disagree with the system.
     *
     * @param  array<int, string>  $inherited
     */
    protected static function alreadyGranted(array $inherited, string $key): bool
    {
        return PermissionRegistry::isGranted($inherited, $key);
    }

    /**
     * A summary of one catalogue role, describing what it reaches by default.
     *
     * Written from the role's own definition rather than restated by hand, so an
     * administrator reading the role list sees exactly what the system will
     * grant — including the pages one role deliberately holds back from another.
     *
     * @return array{label: string, summary: string, modules: array<int, string>, grants: array<int, string>, held_back: array<int, string>, inherited: array<int, string>}
     */
    public static function describeCatalogueRole(string $roleKey): array
    {
        $role = RoleCatalogue::roles()[$roleKey] ?? null;

        if ($role === null) {
            return [
                'label' => $roleKey,
                'summary' => '',
                'modules' => [],
                'grants' => [],
                'held_back' => [],
                'inherited' => [],
            ];
        }

        // A role that is a wildcard, or that lives only in the student portal, is not
        // built from module grants at all, so there is nothing meaningful to
        // compare and "withheld" would misdescribe it.
        $heldBack = RoleCatalogue::isFullAccess($roleKey) || RoleCatalogue::isPortalOnly($roleKey)
            ? []
            : array_values(array_diff(
                RoleCatalogue::permissionsForWithoutExclusions($roleKey),
                RoleCatalogue::permissionsFor($roleKey),
            ));

        return [
            'label' => $role['label'],
            'summary' => $role['summary'],
            'modules' => RoleCatalogue::modulesFor($roleKey),
            'grants' => RoleCatalogue::permissionsFor($roleKey),
            'held_back' => $heldBack,
            // The universal self-service every member of staff receives, listed
            // separately so it is clear it is not something the role chose.
            'inherited' => RoleCatalogue::selfService(),
        ];
    }

    /**
     * The catalogue roles, each with a plain-language account of what it covers.
     *
     * @return array<int, array{key: string, label: string, summary: string, modules: array<int, string>, grants: array<int, string>, held_back: array<int, string>, inherited: array<int, string>}>
     */
    public static function catalogueRoleSummaries(): array
    {
        $summaries = [];

        foreach (RoleCatalogue::keys() as $key) {
            $summaries[] = ['key' => $key] + self::describeCatalogueRole($key);
        }

        return $summaries;
    }

    /**
     * The tab that shows the catalogue roles and what each one grants.
     *
     * Permission editing is organised by module because that is what a checkbox
     * list can do, but an administrator choosing between roles thinks in roles.
     * This tab answers "what does Teaching Staff actually get?" before they start
     * ticking anything, and names the pages a role is held back from so the
     * difference between two similar roles is visible instead of surprising.
     */
    public static function roleOverviewTab(): Tab
    {
        return Tab::make(__('Roles & Defaults'))
            ->icon('heroicon-o-identification')
            ->schema([
                Placeholder::make('role_overview_intro')
                    ->label('')
                    ->content(__('These are the roles a school starts with. Each has a default permission set, and a teacher can be given extra permissions without changing the role. Open the other tabs to tailor a role itself.')),
                Placeholder::make('role_overview')
                    ->label('')
                    ->content(fn () => new HtmlString(static::renderRoleOverview())),
            ]);
    }

    protected static function renderRoleOverview(): string
    {
        $rows = '';

        foreach (static::catalogueRoleSummaries() as $role) {
            $employee = ! RoleCatalogue::isPortalOnly($role['key']);

            $modules = $role['modules'] === []
                ? '<span class="text-gray-400">—</span>'
                : implode(', ', array_map(
                    fn (string $slug): string => CapabilityCatalog::module($slug)['label'] ?? $slug,
                    $role['modules'],
                ));

            $heldBack = '';

            foreach ($role['held_back'] as $permission) {
                $page = PermissionRegistry::pageKeyFor($permission);

                if ($page === null) {
                    continue;
                }

                $action = substr($permission, strrpos($permission, '.') + 1);

                $heldBack .= (CapabilityCatalog::pageName($page[0], $page[1])
                    ?? $page[1])
                    .' ('.CapabilityCatalog::actionLabel($action).') ';
            }

            $grants = count($role['grants']) > 1
                ? count($role['grants']).' permissions'
                : 'the student portal';

            // Built here rather than with Blade in the heredoc below: this is a
            // plain string handed to an HtmlString, so a directive would be
            // printed literally instead of being evaluated.
            $audience = $employee
                ? ''
                : ' &middot; not offered to employees';

            $heldBackBlock = static::heldBackBlock($heldBack);

            $rows .= <<<HTML
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">{$role['label']}</div>
                        <div class="text-xs text-gray-500">{$grants}{$audience}</div>
                    </div>
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">{$role['summary']}</div>
                    <div class="mt-2 text-xs text-gray-500">
                        <span class="font-medium text-gray-700 dark:text-gray-200">Covers:</span> {$modules}
                    </div>
                    {$heldBackBlock}
                </div>
                HTML;
        }

        return '<div class="grid gap-3">'.$rows.'</div>';
    }

    /**
     * The amber note naming the pages one role is deliberately held back from.
     * Empty string when there is nothing held back, so the caller does not have
     * to test it.
     */
    protected static function heldBackBlock(string $heldBack): string
    {
        if ($heldBack === '') {
            return '';
        }

        return '<div class="mt-2 rounded-md bg-amber-50 p-2 text-xs text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">'
            .'<span class="font-medium">Deliberately not included:</span> '.$heldBack
            .'</div>';
    }

    /**
     * @param  array<int, array>  $actions
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    protected static function optionMap(array $actions): array
    {
        $options = [];
        $descriptions = [];

        foreach ($actions as $action) {
            $options[$action['key']] = $action['label'];
            $descriptions[$action['key']] = $action['help'];
        }

        return [$options, $descriptions];
    }

    public static function dehydratePermissions(array $data, string $targetKey = 'permissions', string $fieldPrefix = 'permissions'): array
    {
        $permissions = [];
        if (! empty($data[$fieldPrefix.'_special'])) {
            foreach ((array) $data[$fieldPrefix.'_special'] as $p) {
                $permissions[] = $p;
            }
        }
        foreach ($data as $key => $val) {
            if (str_starts_with($key, $fieldPrefix.'_mod_') || str_starts_with($key, $fieldPrefix.'_page_')) {
                if (is_array($val)) {
                    foreach ($val as $p) {
                        $permissions[] = $p;
                    }
                }
                unset($data[$key]);
            }
        }
        unset($data[$fieldPrefix.'_special']);
        $data[$targetKey] = array_values(array_unique($permissions));

        return $data;
    }
}
