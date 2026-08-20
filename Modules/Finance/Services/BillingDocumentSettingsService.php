<?php

namespace Modules\Finance\Services;

use Modules\Admin\Models\SystemSetting;
use Modules\Finance\Models\SchoolBankAccount;

/**
 * Resolves the configurable content that appears on printed finance documents
 * (invoices, receipts and statements of account).
 *
 * Banking details are configured in System Settings (group "banking") while the
 * document layout / wording is configured under Billing & Invoicing
 * (group "invoice_docs"). Every value falls back to a sensible default so the
 * documents always render even before a school configures anything.
 */
class BillingDocumentSettingsService
{
    public static function get(): array
    {
        $settings = SystemSetting::where('group', 'invoice_docs')->get()->pluck('value', 'key')->toArray();
        $banking = SystemSetting::where('group', 'banking')->get()->pluck('value', 'key')->toArray();
        $branding = SystemSetting::where('group', 'branding')->get()->pluck('value', 'key')->toArray();
        $profile = SystemSetting::where('group', 'profile')->get()->pluck('value', 'key')->toArray();

        return self::fillFooterNotes([
            // Banking & payments (configured in System Settings)
            'banks' => self::resolveBanks($banking),
            'ecocash_merchant' => $banking['ecocash_merchant'] ?? '*151*2*2*123456#',

            // School logo (uploaded in System Settings → Design & Branding)
            'logo_path' => $branding['logo_path'] ?? null,

            // School registration number (System Settings → Institution Profile)
            'registration_number' => $profile['reg_number'] ?? null,

            // School identity / contact (System Settings → Institution Profile).
            // Printed documents fall back to the schools table columns, then to
            // placeholders, when any of these are left empty.
            'school_name' => $profile['school_name'] ?? null,
            'address' => $profile['address'] ?? null,
            'phone' => $profile['phone'] ?? null,
            'email' => $profile['email'] ?? null,

            // School website (System Settings → Institution Profile). When empty,
            // documents fall back to the school's automatically assigned URL.
            'website_url' => $profile['website'] ?? null,

            // Document layout toggles
            'show_logo' => (bool) ($settings['show_logo'] ?? true),
            'show_boarding_status' => (bool) ($settings['show_boarding_status'] ?? true),
            'show_parent_address' => (bool) ($settings['show_parent_address'] ?? true),

            // Wording
            'terms_conditions' => $settings['terms_conditions']
                ?? 'Fees are non-refundable. Please make payments through the listed bank channels only.',
            'reference_notice' => $settings['reference_notice']
                ?? 'You MUST quote the Student Admission Number ({ADMISSION_NUMBER}) as the transaction reference. Payments without proper reference numbers may experience delay in reconciliation.',

            // Signature labels
            'invoice_signature_left' => $settings['invoice_signature_left'] ?? 'Class Teacher Signature',
            'invoice_signature_right' => $settings['invoice_signature_right'] ?? 'Accounts Clerk / Bursar Stamp',
            'receipt_signature_left' => $settings['receipt_signature_left'] ?? 'Receiving Cashier / Bursar',
            'receipt_signature_right' => $settings['receipt_signature_right'] ?? 'Official School Stamp',

            // Footers
            'receipt_footer' => $settings['receipt_footer']
                ?? 'Generated securely by {SCHOOL_NAME}. This is an official system-generated payment confirmation.',
            'statement_footer' => $settings['statement_footer']
                ?? 'Generated securely by {SCHOOL_NAME}. Any query regarding these transactions should be directed to the school bursar.',
        ]);
    }

    /**
     * Fill the {SCHOOL_NAME} placeholder in the resolved footer notes with the
     * school name that appears on the printed documents (Institution Profile
     * setting, falling back to the tenant's name).
     */
    protected static function fillFooterNotes(array $config): array
    {
        $schoolName = $config['school_name'] ?? null;

        if (empty($schoolName) && app()->has('current_tenant')) {
            $schoolName = app('current_tenant')->name;
        }

        $schoolName = $schoolName ?: 'School';

        foreach (['receipt_footer', 'statement_footer'] as $key) {
            if (isset($config[$key])) {
                $config[$key] = self::fillTemplate($config[$key], ['SCHOOL_NAME' => $schoolName]);
            }
        }

        return $config;
    }

    /**
     * Normalises the saved banking settings into a list of bank accounts.
     *
     * The `school_bank_accounts` table (managed under Finance → School Bank
     * Accounts) is the single source of truth for printed invoices, receipts
     * and statements. When the administrator has chosen a specific default
     * account in System Settings → Banking & Payments, that account alone is
     * printed on student invoices. Legacy single-bank keys (`bank_name` /
     * `account_number` / `branch_code`) and the old multi-bank repeater JSON in
     * system settings are only used as a fallback so already-configured schools
     * keep working.
     */
    protected static function resolveBanks(array $banking): array
    {
        $defaults = [
            ['bank_name' => 'Standard Chartered Bank', 'account_number' => '0100234567', 'branch_code' => '05001'],
        ];

        // 1. Explicit default chosen in System Settings → Banking & Payments.
        //    That account alone is what should print on student invoices.
        $defaultId = $banking['invoice_default_bank_account_id'] ?? null;
        if ($defaultId) {
            $account = SchoolBankAccount::query()
                ->where('is_active', true)
                ->find((int) $defaultId);

            if ($account) {
                return [[
                    'bank_name' => $account->bank_name,
                    'account_name' => $account->account_name,
                    'account_number' => $account->account_number,
                    'branch_code' => $account->branch_code,
                    'swift_code' => $account->swift_code,
                ]];
            }
        }

        // 2. Source of truth: the Finance module bank account table.
        $accounts = SchoolBankAccount::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('bank_name')
            ->get();

        if ($accounts->isNotEmpty()) {
            return $accounts->map(function (SchoolBankAccount $account) {
                return [
                    'bank_name' => $account->bank_name,
                    'account_name' => $account->account_name,
                    'account_number' => $account->account_number,
                    'branch_code' => $account->branch_code,
                    'swift_code' => $account->swift_code,
                ];
            })->values()->all();
        }

        // 3. Legacy fallback: the old System Settings repeater JSON.
        if (! empty($banking['banks'])) {
            $banks = is_array($banking['banks']) ? $banking['banks'] : json_decode($banking['banks'], true);
            if (is_array($banks) && count($banks) > 0) {
                return array_map(function ($bank) {
                    return [
                        'bank_name' => $bank['bank_name'] ?? '',
                        'account_number' => $bank['account_number'] ?? '',
                        'branch_code' => $bank['branch_code'] ?? '',
                    ];
                }, array_values($banks));
            }
        }

        // 4. Legacy single-bank settings.
        if (! empty($banking['bank_name'])) {
            return [[
                'bank_name' => $banking['bank_name'],
                'account_number' => $banking['account_number'] ?? '',
                'branch_code' => $banking['branch_code'] ?? '',
            ]];
        }

        return $defaults;
    }

    /**
     * Substitutes placeholders (e.g. {ADMISSION_NUMBER}) in a config template.
     */
    public static function fillTemplate(string $template, array $vars = []): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{'.strtoupper($key).'}', (string) $value, $template);
        }

        return $template;
    }
}
