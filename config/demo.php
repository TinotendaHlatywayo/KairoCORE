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

];
