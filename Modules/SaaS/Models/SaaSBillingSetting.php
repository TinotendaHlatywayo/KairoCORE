<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class SaaSBillingSetting extends Model
{
    protected $table = 'saas_billing_settings';

    public const PAYNOW_FALLBACK_ID = '25965';

    public const PAYNOW_FALLBACK_KEY = '669ac21f-1216-40b0-9623-91c489caca35';

    public const PAYNOW_FALLBACK_EMAIL = 'twaynehlatywayo09@gmail.com';

    protected $fillable = [
        'bank_name', 'bank_account_name', 'bank_account_number',
        'bank_branch_code', 'bank_swift_code', 'paynow_integration_id',
        'paynow_integration_key', 'paynow_merchant_email', 'support_email',
    ];

    protected $casts = [];

    /**
     * Return the first non-empty, non-placeholder value from the candidates.
     */
    public static function firstFilled(array $candidates): ?string
    {
        $placeholders = ['12345', 'sample-key-uuid-9923', 'null', 'undefined', 'changeme'];

        foreach ($candidates as $value) {
            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value === '' || in_array($value, $placeholders, true)) {
                continue;
            }

            return $value;
        }

        return null;
    }

    public static function getActiveSettings(): self
    {
        $settings = static::first();

        if (! $settings) {
            $settings = new static;
            $settings->fill([
                'bank_name' => '',
                'bank_account_name' => '',
                'bank_account_number' => '',
                'bank_branch_code' => '',
                'bank_swift_code' => '',
                'paynow_integration_id' => env('PAYNOW_INTEGRATION_ID') ?: self::PAYNOW_FALLBACK_ID,
                'paynow_integration_key' => env('PAYNOW_INTEGRATION_KEY') ?: self::PAYNOW_FALLBACK_KEY,
            ]);
            $settings->save();
        }

        return $settings;
    }

    public function resolvedPaynowIntegrationId(): string
    {
        return static::firstFilled([$this->paynow_integration_id, env('PAYNOW_INTEGRATION_ID')])
            ?? self::PAYNOW_FALLBACK_ID;
    }

    public function resolvedPaynowIntegrationKey(): string
    {
        return static::firstFilled([$this->paynow_integration_key, env('PAYNOW_INTEGRATION_KEY')])
            ?? self::PAYNOW_FALLBACK_KEY;
    }

    public function resolvedPaynowMerchantEmail(): string
    {
        return static::firstFilled([$this->paynow_merchant_email, env('PAYNOW_MERCHANT_EMAIL')])
            ?? self::PAYNOW_FALLBACK_EMAIL;
    }
}
