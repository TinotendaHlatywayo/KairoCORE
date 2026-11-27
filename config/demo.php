<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Public demo account
    |---------------------------------------------------------------------------
    |
    | Visitors use one shared account to try the whole system without
    | registering a school. The account lives on its own tenant so anything a
    | visitor changes stays out of real schools. Credentials are deliberately
    | configurable so they can be rotated without a code change.
    |
    */

    'email' => env('DEMO_ACCOUNT_EMAIL', 'demo@kairo.co.zw'),

    'password' => env('DEMO_ACCOUNT_PASSWORD', 'Demo123'),

    'name' => env('DEMO_ACCOUNT_NAME', 'Kairo CORE Demo'),

    'school' => [
        'name' => env('DEMO_SCHOOL_NAME', 'Kairo CORE Demo School'),
        'subdomain' => env('DEMO_SCHOOL_SUBDOMAIN', 'demo'),
        'login_code' => env('DEMO_SCHOOL_LOGIN_CODE', 'DEMO-SCH'),
    ],

    /*
    | The single-use ticket that carries the sign-in from the public website
    | to the demo school's subdomain. Session cookies are host-only, so the
    | authenticated session has to be created on the tenant host itself.
    */
    'ticket_ttl_seconds' => (int) env('DEMO_TICKET_TTL_SECONDS', 60),

    /*
    | Create/repair the demo school, account and sample data on first sign-in.
    | Disable this when the demo has been provisioned ahead of time by
    | `php artisan schoolcore:demo-account`.
    */
    'auto_provision' => env('DEMO_AUTO_PROVISION', true),

    'sandbox' => [
        'prefix' => env('DEMO_SANDBOX_PREFIX', 'demo-'),
        'token_length' => (int) env('DEMO_SANDBOX_TOKEN_LENGTH', 12),
        'idle_minutes' => (int) env('DEMO_SANDBOX_IDLE_MINUTES', 120),
        'hard_lifetime_hours' => (int) env('DEMO_SANDBOX_HARD_LIFETIME_HOURS', 8),
        'max_concurrent' => (int) env('DEMO_MAX_CONCURRENT', 50),
        'pool_enabled' => env('DEMO_SANDBOX_POOL_ENABLED', true),
        'pool_size' => (int) env('DEMO_SANDBOX_POOL_SIZE', 5),
        'refill_queue' => env('DEMO_SANDBOX_REFILL_QUEUE', 'demo-pool'),
        'refill_interval_seconds' => (int) env('DEMO_SANDBOX_REFILL_INTERVAL_SECONDS', 60),
        'refill_workers' => (int) env('DEMO_SANDBOX_REFILL_WORKERS', 2),
        'last_seed_ms' => (int) env('DEMO_SANDBOX_LAST_SEED_MS', 7000),
        'logins_per_minute' => (int) env('DEMO_SANDBOX_LOGINS_PER_MINUTE', 10),
        'upload_cap_mb' => (int) env('DEMO_SANDBOX_UPLOAD_CAP_MB', 10),
        'upload_max_files' => (int) env('DEMO_SANDBOX_UPLOAD_MAX_FILES', 20),
        'profile_seed' => env('DEMO_SANDBOX_PROFILE_SEED', false),
    ],

];
