<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row holding the super admin's billing-notification schedule.
 * Accessed through current() which creates the row on first use.
 */
class PlatformBillingSetting extends Model
{
    protected $table = 'platform_billing_settings';

    protected $fillable = [
        'scope_type',
        'target_school_ids',
        'remind_days_before',
        'day_before_reminder_offset',
        'overdue_reminder_offset',
        'suspension_warning_offset',
        'suspension_offset',
        'data_retention_months',
        'notify_super_admin_on_billing',
        'notify_super_admin_on_registration',
        'super_admin_billing_email',
        'default_free_days',
    ];

    protected $casts = [
        'target_school_ids' => 'array',
        'remind_days_before' => 'integer',
        'day_before_reminder_offset' => 'integer',
        'overdue_reminder_offset' => 'integer',
        'suspension_warning_offset' => 'integer',
        'suspension_offset' => 'integer',
        'data_retention_months' => 'integer',
        'notify_super_admin_on_billing' => 'boolean',
        'notify_super_admin_on_registration' => 'boolean',
        'default_free_days' => 'integer',
    ];

    public static function current(?int $schoolId = null): self
    {
        if ($schoolId) {
            $specific = static::where('scope_type', 'specific')
                ->whereJsonContains('target_school_ids', $schoolId)
                ->first();
            if ($specific) {
                return $specific;
            }
        }

        return static::where('scope_type', 'all')->orWhereNull('scope_type')->first() ?: static::query()->firstOrCreate([], [
            'scope_type' => 'all',
            'remind_days_before' => 3,
            'day_before_reminder_offset' => 1,
            'overdue_reminder_offset' => 1,
            'suspension_warning_offset' => 4,
            'suspension_offset' => 5,
            'data_retention_months' => 6,
            'notify_super_admin_on_billing' => true,
            'notify_super_admin_on_registration' => true,
            'super_admin_billing_email' => 'hlatywayotw@gmail.com',
            'default_free_days' => 30,
        ]);
    }
}
