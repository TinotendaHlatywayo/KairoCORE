<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Platform -> tenant hand-off
    |---------------------------------------------------------------------------
    |
    | When a system administrator enters a school from /platform/schools the
    | browser moves from the central host to the school's own subdomain. The
    | session cookie is host-only, so the hand-off is done with a single-use
    | cache ticket rather than by carrying the session across.
    |
    */

    'ticket_ttl_seconds' => (int) env('PLATFORM_TICKET_TTL_SECONDS', 60),

    /*
    |---------------------------------------------------------------------------
    | Platform base URL
    |---------------------------------------------------------------------------
    |
    | Resolved once at boot from APP_URL. It is stored here rather than read
    | from config('app.url') at request time because ResolveTenant rewrites
    | app.url to the tenant's own root while serving a subdomain — by then it
    | is too late to work out where the platform lives.
    |
    */

    'base_url' => rtrim(env('PLATFORM_BASE_URL', env('APP_URL', 'http://lvh.me')), '/'),

    /*
    |---------------------------------------------------------------------------
    | Impersonated session lifetime
    |---------------------------------------------------------------------------
    |
    | How long an entered school stays usable before the session is torn down
    | and the administrator must enter again. Keeps the blast radius small if
    | a browser is left unattended.
    |
    */

    'session_ttl_minutes' => (int) env('PLATFORM_IMPERSONATION_TTL_MINUTES', 30),

    /*
    |---------------------------------------------------------------------------
    | Shadow account
    |---------------------------------------------------------------------------
    |
    | Identity and mailbox domain used for the per-school "System
    | Administrator" account. The .invalid TLD is reserved by RFC 2606 and can
    | never resolve, so these accounts can never receive mail or be phished.
    |
    */

    'system_administrator' => [
        'name' => 'System Administrator',
        'email_domain' => env('PLATFORM_SYSTEM_ADMIN_EMAIL_DOMAIN', 'kairocore.invalid'),
        'role_key' => 'administrator',
    ],

];
