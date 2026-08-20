<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\ExchangeRate;

class ExchangeRateService
{
    /**
     * Get the active exchange rate for the active tenant school.
     */
    public function getActiveRate()
    {
        $schoolId = app('current_tenant')->id;

        $rate = ExchangeRate::where('school_id', $schoolId)
            ->where('from_currency', 'USD')
            ->where('to_currency', 'ZiG')
            ->where('is_active', true)
            ->first();

        // Default to a 1.0 rate to prevent arithmetic division errors if unconfigured
        return $rate ? $rate->rate : 1.0000;
    }

    /**
     * Convert a USD amount to ZiG
     */
    public function convertToZiG($usdAmount)
    {
        return round($usdAmount * $this->getActiveRate(), 2);
    }

    /**
     * Convert a ZiG payment back to USD for ledger reconciliation
     */
    public function convertToUSD($zigAmount)
    {
        $rate = $this->getActiveRate();
        if ($rate <= 0) {
            return $zigAmount;
        }

        return round($zigAmount / $rate, 2);
    }
}
