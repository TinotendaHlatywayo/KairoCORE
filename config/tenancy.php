<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Mode
    |--------------------------------------------------------------------------
    |
    | Controls whether Kairo CORE runs as a multi-tenant SaaS or a single-
    | tenant deployment. Switch via env without any code changes.
    |
    | Supported: 'single', 'multi'
    |
    */

    'mode' => env('TENANCY_MODE', 'multi'),

    /*
    |--------------------------------------------------------------------------
    | Single-Tenant ID
    |--------------------------------------------------------------------------
    |
    | When mode is 'single', this school ID is bound as current_tenant on
    | every request. Subdomain resolution is bypassed entirely.
    |
    */

    'single_tenant_id' => (int) env('SINGLE_TENANT_ID', 0),

    /*
    |--------------------------------------------------------------------------
    | Tenant Base Domain
    |--------------------------------------------------------------------------
    |
    | The base domain used for subdomain resolution in 'multi' mode.
    | In 'single' mode this value is still used for URL generation.
    |
    */

    'tenant_base_domain' => env('TENANT_BASE_DOMAIN', 'lvh.me'),

];
