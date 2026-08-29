<?php

namespace App\Services;

use Modules\Admin\Models\SystemSetting;

class TenantConfigService
{
    /**
     * Base configuration shared by every tenant. Tenant-specific overrides
     * are layered on top at runtime. Update this array to push a new
     * default — every tenant inherits it unless they have an explicit
     * override.
     *
     * Nested structure: group.key => value
     */
    protected static array $baseConfig = [
        // Branding
        'branding' => [
            'theme' => 'dev_choice_1',
            'typography' => 'inter',
            'header_opacity' => '100',
            'watermark_opacity' => '30',
            'watermark_scale' => 'cover',
            'logo_scale' => '32px',
        ],
        // System preferences
        'system_preferences' => [
            'timezone' => 'Africa/Harare',
            'date_format' => 'd/m/Y',
            'time_format' => '24h',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'academic_year_start' => 'January',
            'default_language' => 'en',
        ],
        // Authentication & security
        'authentication' => [
            'require_email_verification' => true,
            'allow_google_sso' => false,
            'session_timeout_minutes' => '120',
            'max_login_attempts' => '5',
            'password_min_length' => '8',
            'force_password_change' => false,
        ],
        // Notifications
        'notifications' => [
            'email_enabled' => true,
            'sms_enabled' => false,
            'whatsapp_enabled' => false,
            'sms_provider' => 'twilio',
        ],
        // Feature flags (inherited from TenantFeatureService defaults)
        'features' => [],
    ];

    /**
     * Get a config value for the current tenant. Resolution order:
     *  1. Tenant-specific override (SystemSetting)
     *  2. Platform base config
     *
     * @param  mixed  $default  Fallback when key doesn't exist anywhere.
     */
    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $schoolId = current_tenant()?->id;

        // 1. Check tenant override
        if ($schoolId) {
            $tenantValue = SystemSetting::get($group, $key, null);
            if ($tenantValue !== null) {
                return $tenantValue;
            }
        }

        // 2. Fall back to base config
        if (isset(self::$baseConfig[$group][$key])) {
            return self::$baseConfig[$group][$key];
        }

        return $default;
    }

    /**
     * Get all resolved config for a group (base + tenant overrides merged).
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        $base = self::$baseConfig[$group] ?? [];
        $schoolId = current_tenant()?->id;

        if ($schoolId) {
            $overrides = SystemSetting::query()
                ->where('school_id', $schoolId)
                ->where('group', $group)
                ->pluck('value', 'key')
                ->all();

            return array_merge($base, $overrides);
        }

        return $base;
    }

    /**
     * Get the entire resolved configuration for the current tenant.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $resolved = self::$baseConfig;
        $schoolId = current_tenant()?->id;

        if ($schoolId) {
            foreach (array_keys(self::$baseConfig) as $group) {
                $overrides = SystemSetting::query()
                    ->where('school_id', $schoolId)
                    ->where('group', $group)
                    ->pluck('value', 'key')
                    ->all();

                $resolved[$group] = array_merge($resolved[$group] ?? [], $overrides);
            }
        }

        return $resolved;
    }

    /**
     * Set a tenant-specific config override. Pass null to remove
     * an override (revert to base default).
     */
    public static function set(string $group, string $key, mixed $value): void
    {
        if ($value === null) {
            SystemSetting::forget($group, $key);

            return;
        }

        SystemSetting::set($group, $key, $value);
    }

    /**
     * Bulk-set overrides for a group.
     *
     * @param  array<string, mixed>  $values
     */
    public static function setGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($group, $key, $value);
        }
    }

    /**
     * Get all defined base config groups and their keys.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getBaseConfig(): array
    {
        return self::$baseConfig;
    }

    /**
     * Reset a tenant's config for a group back to base defaults
     * by removing all SystemSetting overrides.
     */
    public static function resetGroup(string $group): void
    {
        $schoolId = current_tenant()?->id;
        if (! $schoolId) {
            return;
        }

        SystemSetting::query()
            ->where('school_id', $schoolId)
            ->where('group', $group)
            ->delete();
    }

    /**
     * Get a config value with dot notation: config('branding.theme').
     */
    public static function dot(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        if (count($parts) !== 2) {
            return $default;
        }

        return self::get($parts[0], $parts[1], $default);
    }
}
