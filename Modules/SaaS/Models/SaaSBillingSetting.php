<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class SaaSBillingSetting extends Model
{
    protected $table = 'saas_billing_settings';

    protected $fillable = [
        'bank_name', 'bank_account_name', 'bank_account_number',
        'bank_branch_code', 'bank_swift_code', 'paynow_integration_id',
        'paynow_integration_key', 'paypal_client_id', 'paypal_client_secret',
        'paypal_sandbox_mode', 'support_email',
    ];

    protected $casts = [
        'paypal_sandbox_mode' => 'boolean',
    ];

    public static function getActiveSettings(): self
    {
        $settings = static::first();
        if (! $settings) {
            $settings = static::create([
                'bank_name' => 'Steward Bank',
                'bank_account_name' => 'Kairo CORE Systems Ltd',
                'bank_account_number' => '1002345678',
                'paynow_integration_id' => env('PAYNOW_INTEGRATION_ID', '25965'),
                'paynow_integration_key' => env('PAYNOW_INTEGRATION_KEY', '669ac21f-1216-40b0-9623-91c489caca35'),
                'paypal_sandbox_mode' => true,
            ]);
        }

        if (empty($settings->paynow_integration_id) || $settings->paynow_integration_id === '12345') {
            $settings->paynow_integration_id = env('PAYNOW_INTEGRATION_ID', '25965');
        }
        if (empty($settings->paynow_integration_key) || $settings->paynow_integration_key === 'sample-key-uuid-9923') {
            $settings->paynow_integration_key = env('PAYNOW_INTEGRATION_KEY', '669ac21f-1216-40b0-9623-91c489caca35');
        }

        return $settings;
    }
}
