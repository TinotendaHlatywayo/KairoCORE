<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;

/**
 * Creates (or reuses) a locked portal account for a student or staff member
 * who already exists in the school roster, then delivers the branded
 * activation email so the person can choose their own username and password.
 *
 * Activation is only ever emailed when an account is first created. Existing
 * accounts are linked/reused silently — edit screens never trigger a new email.
 */
class RosterAccountProvisioningService
{
    /**
     * Ensure a portal account exists for a student and send the activation mail
     * on first creation. Returns the account, or null when there is no email
     * address on the student's record to provision from.
     */
    public function provisionStudent(Student $student, string $requestedRole = 'student'): ?User
    {
        $email = mb_strtolower(trim((string) $student->getRawOriginal('email')));

        if ($email === '') {
            return null;
        }

        $user = $this->resolveUser($student->school_id, $student->user_id, $email, $student->full_name);

        if (! $user) {
            return null;
        }

        $this->prepareAccount($user, [
            'name' => $student->full_name,
            'username' => $student->full_name,
            'requested_role' => $requestedRole ?: 'student',
        ]);

        if ($student->user_id !== $user->id) {
            $student->user_id = $user->id;
            $student->save();
        }

        $this->sendActivationIfFirstTime($user);

        return $user;
    }

    /**
     * Ensure a portal account exists for an employee and send the activation
     * mail on first creation. Returns the account, or null when no email
     * address is on file.
     */
    public function provisionEmployee(Employee $employee, ?string $requestedRole = null): ?User
    {
        $email = mb_strtolower(trim((string) $employee->email));

        if ($email === '') {
            return null;
        }

        $user = $this->resolveUser($employee->school_id, $employee->user_id, $email, trim("{$employee->first_name} {$employee->last_name}"));

        if (! $user) {
            return null;
        }

        $role = $requestedRole ?: ($employee->role !== '' && stripos((string) $employee->role, 'teach') !== false
            ? 'teaching_staff'
            : 'non_teaching_staff');

        $this->prepareAccount($user, [
            'name' => trim("{$employee->first_name} {$employee->last_name}"),
            'username' => trim("{$employee->first_name} {$employee->last_name}"),
            'requested_role' => $role,
        ]);

        if ($employee->user_id !== $user->id) {
            $employee->user_id = $user->id;
            $employee->save();
        }

        $this->sendActivationIfFirstTime($user);

        return $user;
    }

    /**
     * Find the account linked to a roster record, otherwise the account that
     * already owns the email for this school. A soft-deleted account is
     * restored (it still occupies the unique school+email index). When nothing
     * exists, a new locked pending account is created.
     */
    protected function resolveUser(int $schoolId, ?int $linkedUserId, string $email, string $name): ?User
    {
        if ($linkedUserId) {
            $user = User::withTrashed()->find($linkedUserId);

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }

                return $user;
            }
        }

        $user = User::withTrashed()
            ->where('school_id', $schoolId)
            ->where('email', $email)
            ->first();

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }

            return $user;
        }

        return User::create([
            'school_id' => $schoolId,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'account_status' => User::STATUS_PENDING,
        ]);
    }

    protected function prepareAccount(User $user, array $attributes): void
    {
        $user->forceFill(array_merge($attributes, [
            'account_status' => User::STATUS_PENDING,
        ]))->save();
    }

    /**
     * Mail the activation link only when the account has never been activated
     * and no token has ever been issued (i.e. genuine first creation, never on
     * edit/update paths).
     */
    protected function sendActivationIfFirstTime(User $user): bool
    {
        if ($user->activated_at || filled($user->activation_token)) {
            return false;
        }

        return (bool) app(AccountActivationService::class)->issueAndSend($user);
    }
}
