<?php

use App\Security\RoleCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;

/**
 * Per-user permissions used to *replace* a user's role, and the approval flow
 * wrote a full copy of the role bundle into `users.permissions`. Now they are
 * *additions*, so those stored copies have to be reduced to the part that is not
 * already explained by the role, or every account keeps whatever it had at
 * approval time forever.
 *
 * Consider what happens without this. A teacher approved while the Teaching
 * Staff role still carried Publish to Student Portal would, after that page is
 * removed from the role, still be able to publish: the copy on the account would
 * keep granting it. Subtracting the role bundle removes exactly the redundant
 * part and leaves behind whatever the approver genuinely added, which is the
 * difference between an intentional grant and a stale snapshot.
 *
 * Only rows that carry at least one permission are touched. An empty or null
 * snapshot already means "no personal additions" under the additive rule, so it
 * needs no change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'permissions')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('permissions')
            ->where('permissions', '!=', '[]')
            ->where('permissions', '!=', '{}')
            ->chunkById(200, function ($users): void {
                foreach ($users as $row) {
                    $stored = $this->decode($row->permissions);

                    if ($stored === []) {
                        continue;
                    }

                    $inherited = $this->inheritedFor((int) $row->id, $row->school_id ?? null);

                    // Anything the account already gets from its role or
                    // departments is not an addition.
                    $additions = array_values(array_diff($stored, $inherited));

                    // A custom role can be edited later and lose permissions the
                    // role never had, so a wildcard role cannot be used to
                    // justify keeping a stored copy. Only explicit lists count.
                    if ($additions === $stored && $this->isWildcardRole((int) $row->id)) {
                        $additions = [];
                    }

                    if ($additions === $stored) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $row->id)
                        ->update([
                            'permissions' => json_encode(array_values(array_unique($additions))),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Not reversible on purpose.
        //
        // The information this migration discards is the fact that a permission
        // came from the role rather than from the approver, and that cannot be
        // reconstructed. Restoring the old behaviour would mean re-granting every
        // stored key, which would hand back capabilities the school has since
        // removed from the role.
    }

    /**
     * @return array<int, string>
     */
    protected function decode(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter($raw, 'is_string'));
        }

        if (! is_string($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        // Tolerate the historical `{"permissions": [...]}` wrapper shape.
        if (isset($decoded['permissions']) && is_array($decoded['permissions'])) {
            $decoded = $decoded['permissions'];
        }

        return array_values(array_filter($decoded, 'is_string'));
    }

    /**
     * What the account already reaches without its stored permissions.
     *
     * @return array<int, string>
     */
    protected function inheritedFor(int $userId, ?int $schoolId): array
    {
        $rolePermissions = [];

        $row = DB::table('users')
            ->where('id', $userId)
            ->first(['custom_role_id', 'requested_role']);

        if ($row === null) {
            return [];
        }

        if ($row->custom_role_id) {
            $role = CustomRole::query()
                ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
                ->find($row->custom_role_id);

            $rolePermissions = is_array($role?->permissions) ? $role->permissions : [];
        } elseif ($row->requested_role && RoleCatalogue::exists($row->requested_role)) {
            $rolePermissions = RoleCatalogue::permissionsFor($row->requested_role);
        }

        $departments = DB::table('department_user')
            ->join('departments', 'departments.id', '=', 'department_user.department_id')
            ->where('department_user.user_id', $userId)
            ->pluck('departments.permissions')
            ->all();

        $departmentPermissions = [];

        foreach ($departments as $value) {
            $departmentPermissions = array_merge($departmentPermissions, $this->decode($value));
        }

        return array_values(array_unique(array_merge($rolePermissions, $departmentPermissions)));
    }

    protected function isWildcardRole(int $userId): bool
    {
        $roleId = DB::table('users')->where('id', $userId)->value('custom_role_id');

        if (! $roleId) {
            return false;
        }

        $permissions = CustomRole::query()->find($roleId)?->permissions;

        return is_array($permissions) && in_array(PermissionRegistry::WILDCARD, $permissions, true);
    }
};
