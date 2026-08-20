<?php

namespace App\Services;

use App\Exceptions\RegistrationConflictException;
use App\Mail\UserRegistrationPending;
use App\Models\School;
use App\Models\User;
use App\Notifications\UserRegistrationApprovalNotification;
use Illuminate\Support\Facades\Notification;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\Department;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\PermissionRegistry;
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
    public const CATEGORY_ROLE_NAMES = [
        'administrator' => 'Administrator',
        'student' => 'Student',
        'teaching_staff' => 'Teaching Staff',
        'non_teaching_staff' => 'Non-Teaching Staff',
    ];

    /**
     * Sensible default permissions granted for each requested registration
     * category. These defaults are never final — the approver may tick or
     * untick permissions per account, and a per-user snapshot is materialized
     * on approval.
     *
     * @return array<int, string>
     */
    public static function defaultPermissionsFor(string $category): array
    {
        return PermissionRegistry::defaultPermissionsForRole($category);
    }

    public static function roleNameForCategory(string $category): string
    {
        return self::CATEGORY_ROLE_NAMES[$category] ?? 'Generic';
    }

    /**
     * Create (or reuse) the school-scoped default role for a category.
     */
    public static function ensureRoleForCategory(int $schoolId, string $category): CustomRole
    {
        $name = self::roleNameForCategory($category);

        $role = CustomRole::query()
            ->where('school_id', $schoolId)
            ->where('name', $name)
            ->first();

        if (! $role) {
            $role = CustomRole::create([
                'school_id' => $schoolId,
                'name' => $name,
                'description' => __('SchoolCore default ').$name.' role.',
                'permissions' => self::defaultPermissionsFor($category),
                'is_system' => true,
            ]);
        }

        return $role;
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
        if (! array_key_exists($requestedRole, self::CATEGORY_ROLE_NAMES)) {
            $requestedRole = 'student';
        }

        $user = User::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'account_status' => User::STATUS_PENDING,
            'requested_role' => $requestedRole,
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

        $user->forceFill([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? $user->phone,
            'requested_role' => $data['requested_role'] ?? $user->requested_role ?? 'student',
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
                    new UserRegistrationPending($pendingUser, $approver->email, $school->name),
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

        // Materialize the per-user permission snapshot: an explicit override
        // from the approver wins; otherwise default to role + department sets.
        $user->permissions = PermissionRegistry::normalizePermissionList(
            $permissions ?? PermissionRegistry::defaultPermissionsForUser($user)
        );

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
