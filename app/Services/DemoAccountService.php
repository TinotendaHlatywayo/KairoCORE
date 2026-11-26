<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Security\DemoPersona;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\SystemRolePresets;
use Modules\Students\Models\Student;

class DemoAccountService
{
    protected const TICKET_PREFIX = 'demo.ticket:';

    public function findByCredentials(string $email, string $password): ?User
    {
        $user = User::withoutGlobalScopes()
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        if (
            ! $user
            || $user->school_id === null
            || $user->account_status !== User::STATUS_ACTIVE
            || ! Hash::check($password, (string) $user->password)
        ) {
            return null;
        }

        return $user;
    }

    public function ensureAccount(bool $reseed = false): User
    {
        $school = $this->ensureSchool();

        SystemSetting::set('branding', 'theme', 'digital_cobalt', $school->id);
        SystemRolePresets::provisionForSchool($school);
        $role = SystemRolePresets::roleFor($school->id, 'administrator');

        $user = User::withoutGlobalScopes()
            ->where('email', mb_strtolower((string) config('demo.email')))
            ->first();

        $attributes = [
            'school_id' => $school->id,
            'name' => (string) config('demo.name'),
            'email' => mb_strtolower((string) config('demo.email')),
            'username' => (string) config('demo.email'),
            'password' => (string) config('demo.password'),
            'theme' => 'digital_cobalt',
            'account_status' => User::STATUS_ACTIVE,
            'custom_role_id' => $role->id,
            'requested_role' => 'administrator',
            'is_platform_managed' => false,
        ];

        if (! $user) {
            $user = User::withoutGlobalScopes()->create($attributes);
        } else {
            $user->forceFill($attributes)->save();
        }

        if ($reseed || ! $this->hasDemoData($school->id)) {
            app(DummyDataSeeder::class)->seed($school->id);
            $school->forceFill(['has_dummy_data' => true])->saveQuietly();
        }

        $this->ensureStudentProfile($school, $user);

        return $user;
    }

    public function issueTicket(User $user): string
    {
        $ticket = bin2hex(random_bytes(32));

        Cache::put(
            $this->ticketKey($ticket),
            ['user_id' => $user->id, 'school_id' => (int) $user->school_id],
            now()->addSeconds((int) config('demo.ticket_ttl_seconds', 60))
        );

        return $ticket;
    }

    public function redeemTicket(string $ticket): ?User
    {
        $payload = $ticket !== '' ? Cache::pull($this->ticketKey($ticket)) : null;

        if (! is_array($payload)) {
            return null;
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (! $tenant || (int) ($payload['school_id'] ?? 0) !== (int) $tenant->id) {
            return null;
        }

        $user = User::withoutGlobalScopes()->find($payload['user_id'] ?? null);

        if (! $user || ! DemoPersona::isDemoUser($user) || (int) $user->school_id !== (int) $tenant->id) {
            return null;
        }

        return $user;
    }

    public function signIn(User $user): void
    {
        Auth::login($user, false);
        DemoPersona::set('administrator');
        request()->session()->regenerate();
    }

    public function entryUrl(School $school, string $ticket): string
    {
        $base = config('platform.base_url') ?: config('app.url');
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST) ?: 'localhost';

        return "{$scheme}://{$school->subdomain}.{$host}/demo/consume?ticket=".rawurlencode($ticket);
    }

    protected function ticketKey(string $ticket): string
    {
        return self::TICKET_PREFIX.hash('sha256', $ticket);
    }

    protected function ensureSchool(): School
    {
        $subdomain = (string) config('demo.school.subdomain');

        $school = School::withTrashed()->where('subdomain', $subdomain)->first();

        if (! $school) {
            $school = School::create([
                'name' => (string) config('demo.school.name'),
                'subdomain' => $subdomain,
                'login_code' => (string) config('demo.school.login_code'),
                'institution_type' => 'secondary',
                'status' => 'active',
                'has_dummy_data' => false,
            ]);
        } else {
            if ($school->trashed()) {
                $school->restore();
            }

            $school->forceFill(['status' => 'active'])->saveQuietly();
        }

        return $school;
    }

    protected function hasDemoData(int $schoolId): bool
    {
        return Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->exists();
    }

    protected function ensureStudentProfile(School $school, User $user): void
    {
        $existing = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return;
        }

        if (! $this->hasDemoData($school->id)) {
            return;
        }

        $student = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->first();

        if (! $student) {
            return;
        }

        $student->forceFill(['user_id' => $user->id])->saveQuietly();
    }
}
