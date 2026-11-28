<?php

namespace App\Services;

use App\Exceptions\DemoBusyException;
use App\Jobs\ProvisionDemoSandbox;
use App\Jobs\RefillDemoSandboxPool;
use App\Models\School;
use App\Models\User;
use App\Security\DemoPersona;
use App\Support\DemoSandbox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\Services\SystemRolePresets;
use Modules\Students\Models\Student;

final class DemoSandboxService
{
    public static ?int $lastSeedDurationMs = null;

    /** @return array{school: School, user: User, token: string} */
    public function claim(bool $allowSeed = true): array
    {
        $sessionId = Str::uuid()->toString();
        $shouldSeed = false;

        $school = DB::transaction(function () use ($sessionId, $allowSeed, &$shouldSeed): School {
            $active = School::withoutGlobalScopes()
                ->where('is_demo_sandbox', true)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('hard_expires_at')->orWhere('hard_expires_at', '>', now());
                })
                ->lockForUpdate()
                ->get()
                ->count();

            if ($active >= (int) config('demo.sandbox.max_concurrent', 50)) {
                throw new DemoBusyException('The demo environment is busy.');
            }

            $pooled = config('demo.sandbox.pool_enabled', false)
                ? School::withoutGlobalScopes()
                    ->where('is_demo_sandbox', true)
                    ->where('pool_status', 'ready')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first()
                : null;

            if ($pooled) {
                $pooled->forceFill([
                    'demo_session_id' => $sessionId,
                    'pool_status' => 'claimed',
                    'demo_last_activity_at' => now(),
                    'expires_at' => $this->expiryFrom(now()),
                    'hard_expires_at' => $this->hardExpiryFrom(now()),
                ])->saveQuietly();

                return $pooled;
            }

            if (! $allowSeed) {
                throw new DemoBusyException('No ready demo sandbox is available.');
            }

            $shouldSeed = true;

            return $this->createSchoolRow($sessionId, 'claimed');
        });

        $token = $this->tokenFor($school);
        $user = $this->provisionUser($school, $token);

        if ($shouldSeed) {
            $this->seed($school);
        }

        $this->dispatchRefill();

        return [
            'school' => $school,
            'user' => $user,
            'token' => $token,
        ];
    }

    public function hasReadyPool(): bool
    {
        if (! config('demo.sandbox.pool_enabled', false)) {
            return false;
        }

        return School::withoutGlobalScopes()
            ->where('is_demo_sandbox', true)
            ->where('pool_status', 'ready')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /** @return array{session_id: string, school_id: int} */
    public function prepare(): array
    {
        $sessionId = Str::uuid()->toString();

        $school = DB::transaction(function () use ($sessionId): School {
            $active = School::withoutGlobalScopes()
                ->where('is_demo_sandbox', true)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('hard_expires_at')->orWhere('hard_expires_at', '>', now());
                })
                ->lockForUpdate()
                ->count();

            if ($active >= (int) config('demo.sandbox.max_concurrent', 50)) {
                throw new DemoBusyException('The demo environment is busy.');
            }

            return $this->createSchoolRow($sessionId, 'preparing');
        });

        $token = $this->tokenFor($school);
        $this->provisionUser($school, $token);

        ProvisionDemoSandbox::dispatch($school->id, $sessionId)
            ->onQueue((string) config('demo.sandbox.refill_queue', 'demo-pool'));

        return [
            'session_id' => $sessionId,
            'school_id' => (int) $school->id,
        ];
    }

    /** @return array{status: string, school: School, user: ?User}|null */
    public function pending(string $sessionId): ?array
    {
        $school = School::withoutGlobalScopes()->where('demo_session_id', $sessionId)->first();

        if (! $school) {
            return null;
        }

        $user = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('requested_role', 'administrator')
            ->where('is_platform_managed', true)
            ->first();

        $status = match ($school->pool_status) {
            'claimed' => $school->has_dummy_data && $user ? 'ready' : 'preparing',
            'failed' => 'failed',
            default => 'preparing',
        };

        return [
            'status' => $status,
            'school' => $school,
            'user' => $user,
        ];
    }

    public function seed(School $school): void
    {
        $startedAt = microtime(true);
        $hadTenant = app()->bound('current_tenant');
        $previousTenant = $hadTenant ? app('current_tenant') : null;

        app()->instance('current_tenant', $school);

        try {
            app(DummyDataSeeder::class)->seedForDemoSandbox((int) $school->id);
        } finally {
            if ($hadTenant) {
                app()->instance('current_tenant', $previousTenant);
            } else {
                app()->forgetInstance('current_tenant');
            }
        }

        self::$lastSeedDurationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $school->forceFill(['has_dummy_data' => true])->saveQuietly();

        Log::info('demo.sandbox.seed', [
            'school_id' => (int) $school->id,
            'subdomain' => $school->subdomain,
            'duration_ms' => self::$lastSeedDurationMs,
        ]);
    }

    public function touch(School $school): void
    {
        if (! $school->is_demo_sandbox) {
            return;
        }

        $now = now();
        $expiry = $this->expiryFrom($now);

        if ($school->hard_expires_at && $expiry->greaterThan($school->hard_expires_at)) {
            $expiry = $school->hard_expires_at->copy();
        }

        $school->forceFill([
            'expires_at' => $expiry,
            'demo_last_activity_at' => $now,
        ])->saveQuietly();
    }

    public function delete(int $schoolId): bool
    {
        $school = School::withoutGlobalScopes()->withTrashed()->find($schoolId);

        if (! $school || ! $school->is_demo_sandbox) {
            return false;
        }

        $counts = [
            'users' => User::withoutGlobalScopes()->where('school_id', $schoolId)->count(),
            'students' => Student::withoutGlobalScopes()->where('school_id', $schoolId)->count(),
        ];

        $school->forceDelete();
        $this->deleteFiles($schoolId);

        Log::info('demo.sandbox.deleted', [
            'school_id' => $schoolId,
            'subdomain' => $school->subdomain,
            'counts' => $counts,
        ]);

        return true;
    }

    public function deleteExpired(): int
    {
        $schools = School::withoutGlobalScopes()
            ->where('is_demo_sandbox', true)
            ->where(function ($query): void {
                $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
            })
            ->orderBy('id')
            ->get();

        foreach ($schools->chunk(10) as $chunk) {
            foreach ($chunk as $school) {
                $this->delete((int) $school->id);
            }
        }

        return $schools->count();
    }

    public function ensurePool(): void
    {
        if (! config('demo.sandbox.pool_enabled', false)) {
            return;
        }

        DB::transaction(function (): void {
            $active = School::withoutGlobalScopes()
                ->where('is_demo_sandbox', true)
                ->where('pool_status', 'claimed')
                ->lockForUpdate()
                ->count();

            if ($active >= (int) config('demo.sandbox.max_concurrent', 50)) {
                return;
            }

            $ready = School::withoutGlobalScopes()
                ->where('is_demo_sandbox', true)
                ->whereIn('pool_status', ['ready', 'seeding'])
                ->count();

            if ($ready >= (int) config('demo.sandbox.pool_size', 5)) {
                return;
            }

            $school = $this->createSchoolRow(null, 'seeding');
            $school->forceFill(['pool_status' => 'seeding'])->saveQuietly();
        });

        $seeding = School::withoutGlobalScopes()
            ->where('is_demo_sandbox', true)
            ->where('pool_status', 'seeding')
            ->orderBy('id')
            ->first();

        if (! $seeding) {
            return;
        }

        try {
            $this->provisionUser($seeding, $this->tokenFor($seeding));
            $this->seed($seeding);
            $seeding->forceFill(['pool_status' => 'ready'])->saveQuietly();
        } catch (\Throwable $e) {
            report($e);
            $this->delete((int) $seeding->id);
        }
    }

    public function activeCount(): int
    {
        return School::withoutGlobalScopes()
            ->where('is_demo_sandbox', true)
            ->where('pool_status', 'claimed')
            ->where('expires_at', '>', now())
            ->count();
    }

    public function estimateDatabaseBytes(int $schoolId): int
    {
        $tables = DB::select(
            'SELECT TABLE_NAME AS table_name
               FROM information_schema.COLUMNS
              WHERE table_schema = DATABASE()
                AND column_name = ?',
            ['school_id']
        );

        $bytes = 0;

        foreach ($tables as $table) {
            $name = (string) $table->table_name;
            $rows = DB::table($name)->where('school_id', $schoolId)->get();
            $bytes += strlen((string) json_encode($rows));
        }

        return $bytes;
    }

    private function createSchoolRow(?string $sessionId, string $poolStatus): School
    {
        $length = (int) config('demo.sandbox.token_length', 12);
        $now = now();

        do {
            $token = DemoSandbox::token($length);
        } while (School::withoutGlobalScopes()->withTrashed()->where('subdomain', DemoSandbox::subdomain($token))->exists());

        return School::withoutGlobalScopes()->create([
            'name' => 'Kairo CORE Demo Sandbox '.strtoupper($token),
            'subdomain' => DemoSandbox::subdomain($token),
            'login_code' => 'SANDBOX-'.strtoupper($token),
            'institution_type' => 'secondary',
            'status' => 'active',
            'has_dummy_data' => false,
            'is_demo_sandbox' => true,
            'allow_demo_seed_wipe' => false,
            'demo_session_id' => $sessionId,
            'expires_at' => $this->expiryFrom($now),
            'demo_last_activity_at' => $now,
            'hard_expires_at' => $this->hardExpiryFrom($now),
            'pool_status' => $poolStatus,
        ]);
    }

    private function provisionUser(School $school, string $token): User
    {
        SystemRolePresets::provisionForSchool($school);
        $role = SystemRolePresets::roleFor($school->id, 'administrator');
        $email = DemoSandbox::email($token);

        $user = User::withoutGlobalScopes()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'school_id' => $school->id,
            'name' => (string) config('demo.name'),
            'email' => $email,
            'username' => $email,
            'password' => Str::password(32),
            'theme' => 'digital_cobalt',
            'account_status' => User::STATUS_ACTIVE,
            'custom_role_id' => $role->id,
            'requested_role' => 'administrator',
            'is_platform_managed' => true,
        ])->save();

        app(DemoAccountService::class)->ensureStudentProfile($school, $user);
        DemoPersona::set('administrator');

        return $user;
    }

    private function tokenFor(School $school): string
    {
        return Str::after($school->subdomain, DemoSandbox::prefix());
    }

    private function expiryFrom(Carbon $from): Carbon
    {
        return $from->copy()->addMinutes((int) config('demo.sandbox.idle_minutes', 120));
    }

    private function hardExpiryFrom(Carbon $from): Carbon
    {
        return $from->copy()->addHours((int) config('demo.sandbox.hard_lifetime_hours', 8));
    }

    private function deleteFiles(int $schoolId): void
    {
        Storage::disk('public')->deleteDirectory('demo-sandboxes/'.$schoolId);
    }

    private function dispatchRefill(): void
    {
        if (! config('demo.sandbox.pool_enabled', false)) {
            return;
        }

        RefillDemoSandboxPool::dispatch()->onQueue((string) config('demo.sandbox.refill_queue', 'demo-pool'));
    }
}
