<?php

namespace App\Security;

use App\Models\User;
use App\Support\DemoSandbox;

final class DemoPersona
{
    public const SESSION_KEY = 'demo.persona';

    public static function isDemoUser(?User $user): bool
    {
        if (! $user || $user->school_id === null || $user->account_status !== User::STATUS_ACTIVE) {
            return false;
        }

        $email = mb_strtolower((string) $user->email);

        return $email === mb_strtolower((string) config('demo.email'))
            || DemoSandbox::isSandboxEmail($email);
    }

    public static function personas(): array
    {
        return RoleCatalogue::roles();
    }

    public static function active(?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! self::isDemoUser($user instanceof User ? $user : null)) {
            return null;
        }

        $key = session(self::SESSION_KEY);

        return is_string($key) && RoleCatalogue::exists($key) ? $key : null;
    }

    public static function set(string $key): bool
    {
        if (! RoleCatalogue::exists($key)) {
            return false;
        }

        session([self::SESSION_KEY => $key]);

        return true;
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function isPortal(?User $user = null): bool
    {
        return self::active($user) === 'student';
    }

    public static function permissions(?User $user = null): ?array
    {
        $key = self::active($user);

        return $key === null ? null : RoleCatalogue::permissionsFor($key);
    }

    public static function roleKey(?User $user = null): ?string
    {
        return self::active($user);
    }

    public static function roleLabel(?User $user = null): ?string
    {
        $key = self::active($user);

        return $key === null ? null : RoleCatalogue::label($key);
    }

    public static function roleKeys(?User $user = null): array
    {
        $key = self::active($user);

        return $key === null ? [] : [$key];
    }
}
