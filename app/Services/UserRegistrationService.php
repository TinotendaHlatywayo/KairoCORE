<?php

namespace App\Services;

use App\Exceptions\RegistrationConflictException;
use App\Mail\UserRegistrationPending;
use App\Models\School;
use App\Models\User;
use App\Notifications\UserRegistrationApprovalNotification;
use App\Security\RoleCatalogue;
use Illuminate\Support\Facades\Notification;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\Department;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Admin\Services\SystemRolePresets;
use Modules\Admin\Services\TenantEmailConfigurationService;

/**
 * Individual user-account registration and administrative approval workflow.
 *
 * New registrations are created strictly as PENDING. They remain locked out of
 * the workspace (see User::canAccessPanel) until an authorized administrator
 * with the "users.approve" permission reviews and activates the account.
 */
class UserRegistrationService
{
    /**
     * Category key => role label, for the roles a school can be asked for.
     *
     * Derived from the catalogue so the registration form, the approval dialog
     * and the role screen can never drift apart, and so adding a role to the
     * catalogue is enough to make it offerable.
     *
     * @return array<string, string>
     */
    public static function categoryRoleNames(): array
    {
        $names = [];

        foreach (RoleCatalogue::roles() as $key => $role) {
            $names[$key] = $role['label'];
        }

        return $names;
    }

    /**
     * Sensible default permissions granted for each requested registration
     * category. These defaults are never final — the approver may add
     * permissions per account, which are stored on top of the role.
     *
     * @return array<int, string>
     */
    public static function defaultPermissionsFor(string $category): array
    {
        return PermissionRegistry::defaultPermissionsForRole($category);
    }

    public static function roleNameForCategory(string $category): string
    {
        return self::categoryRoleNames()[$category] ?? 'Generic';
    }

    /**
     * Create (or reuse) the school-scoped default role for a category.
     *
     * Delegated to the catalogue so an approved account can never be handed a
     * role whose defaults have drifted: an existing role is refreshed to the
     * catalogue's current bundle unless an administrator has tailored it, and a
     * role the school never had is created rather than silently downgraded to
     * the fallback.
     */
    public static function ensureRoleForCategory(int $schoolId, string $category): CustomRole
    {
        return SystemRolePresets::roleFor($schoolId, $category);
    }

    /**
     * Find any account (including previously soft-deleted ones) that occupies
     * the given school + email pair. Soft-deleted rows still hold the unique
     * index, so they are exactly the accounts that block a re-registration.
     */
    public function findConflicting(int $schoolId, ?string $email): ?User
    {
        if (! $email) {
            return null;
        }

        return User::withTrashed()
            ->where('school_id', $schoolId)
            ->where('email', mb_strtolower(trim($email)))
            ->first();
    }

    /**
     * Register a new individual user account on behalf of the school.
     *
     * The caller is responsible for having validated the input. The account is
     * created with a PENDING status and can never sign in until approved.
     *
     * When another account already owns the school + email pair (including a
     * soft-deleted one that still occupies the unique index), a
     * RegistrationConflictException is thrown unless a $conflictMode is given:
     *
     *   - 'replace' permanently deletes the existing account and creates a
     *     fresh pending one, so re-adding an email never hits a duplicate.
     *   - 'merge' re-uses the existing account: it is restored if it was
     *     deleted, updated with the new details and re-queued for approval.
     */
    public function register(School $school, array $data, ?string $conflictMode = null): User
    {
        $email = mb_strtolower(trim($data['email']));
        $conflict = $this->findConflicting($school->id, $email);

        if ($conflict && $conflictMode === 'replace') {
            $conflict->forceDelete();
            $conflict = null;
        }

        if ($conflict && $conflictMode === 'merge') {
            return $this->mergeIntoExisting($school, $conflict, $data, $email);
        }

        if ($conflict) {
            throw new RegistrationConflictException($conflict);
        }

        $requestedRole = $data['requested_role'] ?? 'student';
        if (! array_key_exists($requestedRole, self::categoryRoleNames())) {
            $requestedRole = 'student';
        }

        // Attach the catalogue role straight away so the account is created
        // with its default permissions and the User Management "Assigned Role"
        // column is populated from day one. The role stays the single source of
        // truth for what the account can reach; approval only decides whether
        // the person may sign in.
        $role = self::ensureRoleForCategory($school->id, $requestedRole);

        $user = User::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'account_status' => User::STATUS_PENDING,
            'requested_role' => $requestedRole,
            'custom_role_id' => $role->id,
        ]);

        $this->notifyApprovers($school, $user);

        return $user;
    }

    /**
     * Re-register an existing account with the submitted details instead of
     * creating a duplicate. The account is restored if it was soft-deleted,
     * refreshed with the new data and put back into the pending-approval queue.
     */
    protected function mergeIntoExisting(School $school, User $user, array $data, string $email): User
    {
        if ($user->trashed()) {
            $user->restore();
        }

        $requestedRole = $data['requested_role'] ?? $user->requested_role ?? 'student';
        if (! array_key_exists($requestedRole, self::categoryRoleNames())) {
            $requestedRole = 'student';
        }

        // Re-attach (or refresh) the catalogue role so the merged account keeps
        // its default permissions and its Assigned Role is never left blank.
        $role = self::ensureRoleForCategory($school->id, $requestedRole);

        $user->forceFill([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? $user->phone,
            'requested_role' => $requestedRole,
            'custom_role_id' => $role->id,
            'account_status' => User::STATUS_PENDING,
            'rejected_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
            'activation_token' => null,
            'activation_token_expires_at' => null,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $this->notifyApprovers($school, $user);

        return $user;
    }

    /**
     * Notify every active user of the school who holds the users.approve
     * permission. The notification points the approver straight to the review
     * screen for the pending account. An email is also sent to each approver so
     * registrations are never missed, using the school's configured mailer.
     */
    public function notifyApprovers(School $school, User $pendingUser): int
    {
        $approvers = User::query()
            ->where('school_id', $school->id)
            ->where('account_status', User::STATUS_ACTIVE)
            ->whereNotNull('custom_role_id')
            ->with(['customRole:id,name,permissions'])
            ->get()
            ->filter(fn (User $user) => PermissionRegistry::userCan($user, 'users.approve'));

        if ($approvers->isEmpty()) {
            return 0;
        }

        // The in-app database notification is ALWAYS delivered so the approval
        // task is never missed inside the workspace.
        Notification::send($approvers, new UserRegistrationApprovalNotification($pendingUser));

        // Whether approvers also receive an email is a per-school preference
        // (System Settings -> Notifications). When disabled, admins are still
        // notified in-app but no email is dispatched for each registration.
        $emailOnRegistration = filter_var(
            SystemSetting::withoutTenantScope()
                ->where('school_id', $school->id)
                ->where('group', 'notifications')
                ->where('key', 'email_on_user_registration')
                ->value('value') ?? '1',
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $emailOnRegistration) {
            return $approvers->count();
        }

        foreach ($approvers as $approver) {
            try {
                app(TenantEmailConfigurationService::class)->queueSend(
                    new UserRegistrationPending($pendingUser, $approver->email, $school->name, $school),
                    EmailCategory::Communication,
                    $school,
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $approvers->count();
    }

    /**
     * Approve a pending account.
     *
     * @param  array<int, string>|null  $permissions  Explicit per-user permission
     *                                                snapshot. When null the snapshot
     *                                                is materialized from the role
     *                                                defaults + department defaults.
     * @param  array<int, int>|null  $departmentIds  Departments to assign (used for
     *                                               non-teaching staff); each contributes
     *                                               its default permission bundle.
     */
    public function approve(User $user, ?int $roleId = null, ?int $approverId = null, ?array $permissions = null, ?array $departmentIds = null): User
    {
        $schoolId = $user->school_id;

        if ($roleId) {
            $role = CustomRole::query()->where('school_id', $schoolId)->find($roleId);
            if ($role) {
                $user->custom_role_id = $role->id;
            }
        }

        if (! $user->custom_role_id && $user->requested_role) {
            $default = self::ensureRoleForCategory($schoolId, $user->requested_role);
            $user->custom_role_id = $default->id;
        }

        // Sync department memberships (non-teaching staff). Each department
        // contributes its default permission bundle to the account.
        if (is_array($departmentIds)) {
            $validIds = Department::query()
                ->where('school_id', $schoolId)
                ->whereIn('id', $departmentIds)
                ->pluck('id');

            $user->departments()->sync($validIds->mapWithKeys(
                fn (int $id) => [$id => ['school_id' => $schoolId]]
            )->all());
        }

        // Store only the approver's additions on top of the role. Per-user
        // permissions are additive, so writing the whole role bundle here would
        // freeze a copy of the role onto the account: the teacher would keep
        // Finance forever after losing it from the role, and nothing would be
        // left that the runtime could tell apart from an intentional grant.
        // The approval screen pre-ticks the role's defaults so the approver can
        // see them; anything already implied by the role or a department is
        // therefore filtered back out before it is saved.
        if ($permissions === null) {
            $user->permissions = [];
        } else {
            $inherited = PermissionRegistry::defaultPermissionsForUser($user);
            $user->permissions = array_values(array_filter(
                PermissionRegistry::normalizePermissionList($permissions),
                fn (string $permission): bool => ! PermissionRegistry::isGranted($inherited, $permission),
            ));
        }

        $user->forceFill([
            'account_status' => User::STATUS_ACTIVE,
            'rejected_reason' => null,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ])->save();

        return $user;
    }

    /**
     * Reject a pending registration, permanently refusing workspace access.
     * A rejected account can be re-reviewed and approved by an administrator.
     */
    public function reject(User $user, string $reason, ?int $approverId = null): User
    {
        $user->forceFill([
            'account_status' => User::STATUS_REJECTED,
            'rejected_reason' => $reason ?: null,
            'approved_by' => $approverId,
            'approved_at' => null,
        ])->save();

        return $user;
    }
}
