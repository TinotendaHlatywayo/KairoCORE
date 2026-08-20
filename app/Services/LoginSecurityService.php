<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginSecurityService
{
    /**
     * Check 3-tiered rate limits before login attempt.
     */
    public static function ensureNotRateLimited(string $email, string $ip): void
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        $emailKey = 'login.attempts.email:'.$email;
        $emailLockoutKey = 'login.attempts.email:lockout:'.$email;
        $emailEscalatedKey = 'login.lockouts.email:'.$email;
        $ipKey = 'login.attempts.ip:'.$ip;
        $ipBlockKey = 'login.attempts.ip:block:'.$ip;

        // Tier 3: IP Address Block (Per IP - 24 hours block if 30 failed attempts in 1 hr)
        if (Cache::has($ipBlockKey) || RateLimiter::tooManyAttempts($ipKey.':blocked', 1)) {
            abort(403, __('Your IP address has been temporarily blocked due to excessive failed login attempts.'));
        }

        // Tier 2: Escalated Lockout (Per Email - 24 hours)
        if (Cache::has($emailEscalatedKey)) {
            $remaining = Cache::get($emailEscalatedKey) - now()->timestamp;
            $hours = max(1, ceil($remaining / 3600));
            throw ValidationException::withMessages([
                'data.email' => __("This account has been locked for 24 hours due to repeated security lockouts. Please try again in {$hours} hours."),
            ]);
        }

        // Tier 1: Account Lockout (Per Email - 15 minutes)
        if (Cache::has($emailLockoutKey) || RateLimiter::tooManyAttempts($emailKey.':lockout', 1)) {
            $remaining = RateLimiter::availableIn($emailKey.':lockout');
            if ($remaining <= 0 && Cache::has($emailLockoutKey)) {
                $remaining = Cache::get($emailLockoutKey) - now()->timestamp;
            }
            $minutes = max(1, ceil($remaining / 60));
            throw ValidationException::withMessages([
                'data.email' => __("Too many failed login attempts. This account is locked. Please try again in {$minutes} minutes."),
            ]);
        }
    }

    /**
     * Record a failed login attempt.
     */
    public static function hit(string $email, string $ip): void
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        $emailKey = 'login.attempts.email:'.$email;
        $emailLockoutKey = 'login.attempts.email:lockout:'.$email;
        $emailTier1CountKey = 'login.attempts.email:tier1_count:'.$email;
        $emailEscalatedKey = 'login.lockouts.email:'.$email;
        $ipKey = 'login.attempts.ip:'.$ip;
        $ipBlockKey = 'login.attempts.ip:block:'.$ip;

        // Tier 3 tracking: 30 attempts within 1 hour window -> block IP for 24 hours
        RateLimiter::hit($ipKey, 3600);
        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            Cache::put($ipBlockKey, now()->addDay()->timestamp, now()->addDay());
            RateLimiter::hit($ipKey.':blocked', 86400);
        }

        // Tier 1 tracking: 5 attempts within 5-minute window -> lock email for 15 minutes
        RateLimiter::hit($emailKey, 300);
        if (RateLimiter::tooManyAttempts($emailKey, 5)) {
            Cache::put($emailLockoutKey, now()->addMinutes(15)->timestamp, now()->addMinutes(15));
            RateLimiter::hit($emailKey.':lockout', 900);
            RateLimiter::clear($emailKey);

            // Escalated counter (Tier 2): 3 consecutive Tier 1 lockouts without successful login -> 24 hour lockout
            $tier1Count = (int) Cache::get($emailTier1CountKey, 0) + 1;
            Cache::put($emailTier1CountKey, $tier1Count, now()->addDays(7));

            if ($tier1Count >= 3) {
                Cache::put($emailEscalatedKey, now()->addDay()->timestamp, now()->addDay());
                Cache::forget($emailTier1CountKey);
            }
        }
    }

    /**
     * Get remaining attempts before Tier 1 lockout.
     */
    public static function getRemainingAttempts(string $email): int
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return 5;
        }
        $emailKey = 'login.attempts.email:'.$email;
        $attempts = RateLimiter::attempts($emailKey);

        return max(0, 5 - $attempts);
    }

    /**
     * Clear all tracking keys on successful login.
     */
    public static function clear(string $email, string $ip): void
    {
        $email = strtolower(trim($email));
        if ($email !== '') {
            RateLimiter::clear('login.attempts.email:'.$email);
            RateLimiter::clear('login.attempts.email:lockout:'.$email);
            Cache::forget('login.attempts.email:lockout:'.$email);
            Cache::forget('login.attempts.email:tier1_count:'.$email);
            Cache::forget('login.lockouts.email:'.$email);
        }
        RateLimiter::clear('login.attempts.ip:'.$ip);
        Cache::forget('login.attempts.ip:block:'.$ip);
    }
}
