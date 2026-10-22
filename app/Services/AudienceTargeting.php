<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Security\RoleCatalogue;
use Illuminate\Database\Eloquent\Builder;
use Modules\Admin\Models\CustomRole;

/**
 * The role vocabulary used to target communication content at people.
 *
 * A school sees its own roles on the Roles page (System Administration ->
 * Roles), and the platform has to target that same vocabulary when it sends a
 * message or an announcement to tenants. Catalogue roles keep their catalogue
 * key, so one selector reaches the same role in every school; a role a school
 * invented has no catalogue identity, so it is addressed by its own record.
 *
 * Users are matched the same way the tenant-side announcement does it: a
 * selector matches `users.requested_role` OR the role key of the custom role
 * the account actually holds.
 */
class AudienceTargeting
{
    /**
     * The catalogue key of the role that runs a whole school.
     */
    public const SYSTEM_ADMINISTRATOR = 'administrator';

    /**
     * Every role a tenant can see on its Roles page.
     *
     * The catalogue roles come first, because they are the same role in every
     * school and one selector reaches all of them. A role a school built
     * itself belongs to that school alone, so those are grouped under the
     * school that owns them — otherwise a platform admin would be shown a
     * dozen identically named "Administrator" entries with nothing to tell
     * them apart.
     *
     * @return array<string, string|array<string, string>> selector => label
     */
    public static function roleOptions(): array
    {
        $options = RoleCatalogue::audienceRoleOptions();

        $custom = CustomRole::withoutTenantScope()
            ->whereNull('role_key')
            ->whereIn('school_id', School::query()->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'school_id']);

        if ($custom->isEmpty()) {
            return $options;
        }

        $schoolNames = School::query()
            ->whereIn('id', $custom->pluck('school_id')->filter()->unique())
            ->pluck('name', 'id');

        $grouped = [];

        foreach ($custom as $role) {
            $group = (string) ($schoolNames[$role->school_id] ?? __('Other tenants'));
            $grouped[$group][self::customSelector((int) $role->id)] = $role->name;
        }

        ksort($grouped);

        return $options + $grouped;
    }

    /**
     * The selector that addresses one administrator-created role.
     */
    public static function customSelector(int $roleId): string
    {
        return 'custom:'.$roleId;
    }

    /**
     * Whether the selector addresses one specific role record.
     */
    public static function isCustomSelector(string $selector): bool
    {
        return str_starts_with($selector, 'custom:');
    }

    /**
     * The role record id a custom selector addresses.
     */
    public static function customSelectorId(string $selector): ?int
    {
        if (! self::isCustomSelector($selector)) {
            return null;
        }

        $id = (int) substr($selector, strlen('custom:'));

        return $id > 0 ? $id : null;
    }

    /**
     * Constrain a user query to the accounts holding any of the given roles.
     *
     * @param  array<int, string>  $selectors
     */
    public static function scopeToRoles(Builder $query, array $selectors): Builder
    {
        $selectors = array_values(array_filter(array_map('strval', $selectors)));

        if ($selectors === []) {
            return $query;
        }

        $catalogueKeys = [];
        $customIds = [];

        foreach ($selectors as $selector) {
            $customId = self::customSelectorId($selector);

            if ($customId !== null) {
                $customIds[] = $customId;

                continue;
            }

            $catalogueKeys[] = $selector;
        }

        return $query->where(function (Builder $q) use ($catalogueKeys, $customIds): void {
            $clauses = 0;

            if ($catalogueKeys !== []) {
                $q->whereIn('users.requested_role', $catalogueKeys)
                    ->orWhereHas('customRole', fn ($role) => $role->whereIn('role_key', $catalogueKeys));
                $clauses++;
            }

            if ($customIds !== []) {
                if ($clauses > 0) {
                    $q->orWhereIn('users.custom_role_id', $customIds);
                } else {
                    $q->whereIn('users.custom_role_id', $customIds);
                }
            }
        });
    }

    /**
     * Whether the account holds the tenant's System Administrator role.
     */
    public static function isSystemAdministrator(User $user): bool
    {
        if ($user->school_id === null) {
            return false;
        }

        if ((string) $user->requested_role === self::SYSTEM_ADMINISTRATOR) {
            return true;
        }

        $roleKey = $user->customRole?->role_key;

        return $roleKey !== null && RoleCatalogue::isFullAccess((string) $roleKey);
    }
}
