<?php

namespace App\Services;

use Modules\Admin\Models\SystemSetting;

class TenantFeatureService
{
    /**
     * Base feature flag defaults. Every tenant inherits these unless
     * they have an explicit override in SystemSetting ('features', key).
     *
     * Keys should be snake_case. Values are booleans.
     */
    protected static array $defaults = [
        'dark_mode' => false,
        'csv_export' => true,
        'pdf_export' => true,
        'email_notifications' => true,
        'sms_notifications' => false,
        'whatsapp_notifications' => false,
        'two_factor_auth' => false,
        'online_admissions' => true,
        'student_portal' => true,
        'parent_portal' => false,
        'staff_portal' => true,
        'library_module' => true,
        'hostel_module' => true,
        'clinic_module' => true,
        'inventory_module' => true,
        'lms_module' => true,
        'knowledge_base' => true,
        'attendance_tracking' => true,
        'exam_management' => true,
        'fee_management' => true,
        'payroll_module' => true,
        'report_generation' => true,
        'website_builder' => true,
        'communication_center' => true,
        'id_card_designer' => true,
        'timetable_builder' => true,
        'data_import' => true,
        'advanced_analytics' => false,
        'api_access' => false,
        'custom_roles' => false,
        'onboarding_wizard' => true,
        'print_reports' => true,
        'bulk_operations' => true,
        'academic_calendar' => true,
    ];

    /**
     * Determine whether a specific feature flag is enabled for the
     * current tenant (school). Falls back to the base default when
     * no tenant-level override exists.
     */
    public static function isEnabled(string $feature): bool
    {
        $schoolId = current_tenant()?->id;
        if (! $schoolId) {
            // Super-admin context: all features enabled by default.
            return true;
        }

        $raw = SystemSetting::get('features', $feature, null);

        // If the tenant has never configured this flag, fall back to the
        // platform base default.
        if ($raw === null) {
            return self::$defaults[$feature] ?? false;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if a feature flag exists (either as a default or a tenant override).
     */
    public static function exists(string $feature): bool
    {
        return array_key_exists($feature, self::$defaults)
            || SystemSetting::get('features', $feature) !== null;
    }

    /**
     * Return all available feature flags with their resolved values for
     * the active tenant.
     *
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $schoolId = current_tenant()?->id;
        $resolved = self::$defaults;

        if ($schoolId) {
            // Overlay tenant overrides onto the defaults
            $overrides = self::loadOverrides($schoolId);
            foreach ($overrides as $key => $value) {
                $resolved[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        } else {
            // Super-admin context: everything enabled
            $resolved = array_fill_keys(array_keys($resolved), true);
        }

        ksort($resolved);

        return $resolved;
    }

    /**
     * Return only the features that have been explicitly overridden for
     * the given tenant (not including defaults).
     *
     * @return array<string, bool>
     */
    public static function getOverrides(int $schoolId): array
    {
        return self::loadOverrides($schoolId);
    }

    /**
     * Set a single feature flag for a tenant. Pass null to remove
     * an override (revert to the base default).
     */
    public static function set(string $feature, ?bool $enabled): void
    {
        if ($enabled === null) {
            SystemSetting::forget('features', $feature);

            return;
        }

        SystemSetting::set('features', $feature, $enabled ? '1' : '0');
    }

    /**
     * Bulk-update feature flags for the active tenant. Accepts an
     * associative array of [feature => enabled].
     *
     * @param  array<string, bool>  $flags
     */
    public static function bulkSet(array $flags): void
    {
        foreach ($flags as $feature => $enabled) {
            self::set($feature, (bool) $enabled);
        }
    }

    /**
     * Get all defined default feature keys.
     *
     * @return array<string, bool>
     */
    public static function getDefaults(): array
    {
        return self::$defaults;
    }

    /**
     * Load raw overrides from SystemSetting for a given school.
     *
     * @return array<string, mixed>
     */
    protected static function loadOverrides(int $schoolId): array
    {
        return SystemSetting::query()
            ->where('school_id', $schoolId)
            ->where('group', 'features')
            ->pluck('value', 'key')
            ->mapWithKeys(fn ($val, $key) => [$key => $val])
            ->all();
    }
}
