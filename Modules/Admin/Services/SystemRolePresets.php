<?php

namespace Modules\Admin\Services;

use App\Models\School;
use App\Security\RoleCatalogue;
use Modules\Admin\Models\CustomRole;

/**
 * Provisions the catalogue roles for a school and keeps their default
 * permissions equal to what `RoleCatalogue` says they should be.
 *
 * A school starts with the eleven roles the catalogue defines, and every one of
 * them must grant exactly the permissions its definition promises. That is easy
 * to get wrong twice over:
 *
 *  1. A role created before a catalogue change keeps the old bundle forever,
 *     because nothing ever revisited it. That is how a Teaching Staff role
 *     carried Publish to Student Portal long after the catalogue took it away.
 *  2. A school whose roles were never created at all — registered before the
 *     catalogue existed, or a role simply never assigned — has no defaults to
 *     hand out at all, so the approver is silently offered `supporting_staff`
 *     for somebody they meant to make a librarian.
 *
 * Both are fixed by asking the catalogue rather than the database. Roles the
 * platform owns are recognised by `role_key` (see the migration that adds it),
 * and a role an administrator created is never rewritten — not even if they
 * happened to give it one of our names.
 */
class SystemRolePresets
{
    /** The role's permissions were brought back to the catalogue defaults. */
    public const REFRESHED = 'refreshed';

    /** An administrator has tailored the role, so it was left alone. */
    public const CUSTOMISED = 'customised';

    /** The role already matched the catalogue; nothing was written. */
    public const UNCHANGED = 'unchanged';

    /**
     * Create every catalogue role a school is missing, and refresh the
     * permissions of the ones it already has that nobody has tailored.
     *
     * @return array{created: array<int, string>, refreshed: array<int, string>, customised: array<int, string>}
     */
    public static function provisionForSchool(School|int $school): array
    {
        $schoolId = $school instanceof School ? $school->id : (int) $school;

        $created = [];
        $refreshed = [];
        $customised = [];

        foreach (RoleCatalogue::keys() as $roleKey) {
            $role = self::locate($schoolId, $roleKey);

            if ($role === null) {
                self::create($schoolId, $roleKey);
                $created[] = $roleKey;

                continue;
            }

            $status = self::apply($role, $roleKey);

            if ($status === self::REFRESHED) {
                $refreshed[] = $roleKey;
            } elseif ($status === self::CUSTOMISED) {
                $customised[] = $roleKey;
            }
        }

        return [
            'created' => $created,
            'refreshed' => $refreshed,
            'customised' => $customised,
        ];
    }

    /**
     * Bring one already-loaded role back to its catalogue defaults, when the
     * catalogue owns it and nobody has tailored it.
     */
    public static function refresh(CustomRole $role): void
    {
        if ($role->role_key === null || ! RoleCatalogue::exists($role->role_key)) {
            return;
        }

        self::apply($role, $role->role_key);
    }

    /**
     * The catalogue role for a school, created on first use.
     *
     * Registration and approval call this instead of building a role by hand,
     * so an account can never be approved onto a stale or missing default: the
     * role it receives is the catalogue's, at the moment of approval.
     */
    public static function roleFor(int $schoolId, string $roleKey): CustomRole
    {
        $role = self::locate($schoolId, $roleKey);

        if ($role !== null) {
            self::apply($role, $roleKey);

            return $role;
        }

        return self::create($schoolId, $roleKey);
    }

    /**
     * Reset a tailored role to the catalogue defaults as well.
     *
     * Reserved for an administrator who says "give me the standard defaults
     * back"; provisioning deliberately does not do this, because a role someone
     * has narrowed on purpose must survive every later catalogue release.
     *
     * @return array<int, string>
     */
    public static function restoreDefaults(int $schoolId): array
    {
        $restored = [];

        foreach (RoleCatalogue::keys() as $roleKey) {
            $role = self::locate($schoolId, $roleKey);

            if ($role === null) {
                self::create($schoolId, $roleKey);
                $restored[] = $roleKey;

                continue;
            }

            $role->forceFill([
                'permissions' => self::defaultsFor($roleKey),
                'permissions_customised' => false,
            ])->saveQuietly();

            AuditLogger::log(
                'Restored catalogue defaults for role: '.$role->name,
                'System Administration',
                ['permissions' => $role->permissions],
                ['permissions' => $role->getAttribute('permissions')]
            );

            $restored[] = $roleKey;
        }

        return $restored;
    }

    /**
     * Find the catalogue role a school already owns, claiming only the rows
     * that are provably ours.
     */
    protected static function locate(int $schoolId, string $roleKey): ?CustomRole
    {
        $role = CustomRole::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('role_key', $roleKey)
            ->first();

        if ($role !== null) {
            return $role;
        }

        // Rows created before `role_key` existed have no key to match on. The
        // ones that are ours are flagged as system roles and carry a catalogue
        // label, so they are adopted rather than duplicated. A role an
        // administrator created is left alone even when it shares our name:
        // it may have been narrowed on purpose, and adopting it would hand our
        // defaults to somebody else's role.
        $legacy = CustomRole::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('is_system', true)
            ->orderBy('id')
            ->get()
            ->first(fn (CustomRole $candidate): bool => RoleCatalogue::keyForRoleName($candidate->name) === $roleKey);

        if ($legacy === null) {
            return null;
        }

        $legacy->forceFill(['role_key' => $roleKey])->saveQuietly();

        return $legacy;
    }

    /**
     * Point a role at the current catalogue defaults.
     *
     * @return string One of REFRESHED, CUSTOMISED or UNCHANGED.
     */
    protected static function apply(CustomRole $role, string $roleKey): string
    {
        if ($role->hasCustomisedPermissions() && ! self::isUntouchable($roleKey)) {
            return self::CUSTOMISED;
        }

        $defaults = self::defaultsFor($roleKey);
        $changes = [];

        if (self::normalise($role->permissions) !== self::normalise($defaults)) {
            $changes['permissions'] = $role->permissions;
            $role->permissions = $defaults;
        }

        if (! $role->is_system) {
            $changes['is_system'] = false;
            $role->is_system = true;
        }

        if (empty($changes)) {
            return self::UNCHANGED;
        }

        // Quiet, because this is the catalogue writing, not an administrator
        // tailoring a role: the saving hook would otherwise stamp the role as
        // customised and stop it ever being refreshed again. The change is
        // audited explicitly below instead.
        $role->saveQuietly();

        AuditLogger::log(
            'Refreshed default permissions for role: '.$role->name,
            'System Administration',
            $changes,
            ['permissions' => $role->getAttribute('permissions')]
        );

        return self::REFRESHED;
    }

    /**
     * The system administrator role is the one role a school cannot be asked to
     * narrow.
     *
     * Every other default may be tailored — that is the point of having roles at
     * all. This one may not: the person who runs the platform for the school
     * signs in through it, and a role that no longer grants unrestricted access
     * * cannot sign in at all — they would be locked out of the very screen that
     * * could fix it. So an edit here is corrected rather than respected, which is
     * why the flag alone does not protect this role.
     */
    protected static function isUntouchable(string $roleKey): bool
    {
        return RoleCatalogue::isFullAccess($roleKey);
    }

    protected static function create(int $schoolId, string $roleKey): CustomRole
    {
        return CustomRole::create([
            'school_id' => $schoolId,
            'name' => self::availableName($schoolId, $roleKey),
            'description' => RoleCatalogue::summary($roleKey),
            'permissions' => self::defaultsFor($roleKey),
            'is_system' => true,
            'role_key' => $roleKey,
            'permissions_customised' => false,
        ]);
    }

    /**
     * The catalogue label, unless an administrator has already used it for a
     * role of their own.
     *
     * `custom_roles` is unique per school on name, so an administrator who made
     * their own "Teaching Staff" would otherwise make the default role
     * impossible to create — and an un-creatable default is how a school ends up
     * silently handing out the `supporting_staff` fallback instead. Marking the
     * default keeps both roles, and the suffix says plainly which is which.
     */
    protected static function availableName(int $schoolId, string $roleKey): string
    {
        $label = RoleCatalogue::label($roleKey);

        $taken = CustomRole::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('name', $label)
            ->where(function ($query) use ($roleKey) {
                $query->whereNull('role_key')->orWhere('role_key', '!=', $roleKey);
            })
            ->exists();

        return $taken ? $label.' (Default)' : $label;
    }

    /**
     * @return array<int, string>
     */
    protected static function defaultsFor(string $roleKey): array
    {
        return RoleCatalogue::permissionsFor($roleKey);
    }

    /**
     * Order-insensitive comparison, so a re-serialisation is not mistaken for a
     * change.
     *
     * @param  array<int, string>|null  $permissions
     * @return array<int, string>
     */
    protected static function normalise(?array $permissions): array
    {
        $sorted = array_values(array_unique(array_map('strval', $permissions ?? [])));
        sort($sorted);

        return $sorted;
    }
}
