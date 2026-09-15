<?php

namespace Modules\Finance\Services;

use Modules\Admin\Models\SystemSetting;

class FinanceSettingsService
{
    public static function billingFrequency(?int $schoolId = null): string
    {
        $schoolId ??= current_tenant()?->id;

        if (! $schoolId) {
            return 'termly';
        }

        $value = SystemSetting::where('school_id', $schoolId)
            ->where('group', 'finance')
            ->where('key', 'billing_frequency')
            ->value('value');

        return in_array($value, ['termly', 'monthly'], true) ? $value : 'termly';
    }
}
