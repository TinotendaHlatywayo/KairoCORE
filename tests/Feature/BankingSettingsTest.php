<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\SystemSetting;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\BillingDocumentSettingsService;
use Tests\TestCase;

class BankingSettingsTest extends TestCase
{
    use InteractsWithDatabase;

    private array $preExistingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->preExistingIds = SchoolBankAccount::withoutTenantScope()
            ->where('school_id', 15)
            ->pluck('id')
            ->all();
    }

    protected function tearDown(): void
    {
        // Remove any rows created during the test (keep pre-existing data intact).
        SchoolBankAccount::withoutTenantScope()
            ->where('school_id', 15)
            ->whereNotIn('id', $this->preExistingIds)
            ->forceDelete();

        SystemSetting::where('group', 'banking')->whereIn('key', [
            'invoice_default_bank_account_id',
            'bank_name',
            'account_number',
        ])->delete();

        App::forgetInstance('current_tenant');
        parent::tearDown();
    }

    public function test_invoice_resolution_uses_all_active_accounts_without_default(): void
    {
        $school = School::firstOrFail();
        App::instance('current_tenant', $school);

        $a = $this->createAccount('Test Bank A', '111111', true);
        $b = $this->createAccount('Test Bank B', '222222', true);
        $this->createAccount('Inactive Bank', '333333', false);

        $banks = BillingDocumentSettingsService::get()['banks'];

        $names = collect($banks)->pluck('bank_name')->all();
        $this->assertContains('Test Bank A', $names);
        $this->assertContains('Test Bank B', $names);
        $this->assertNotContains('Inactive Bank', $names);

        $this->assertSame('111111', collect($banks)->firstWhere('bank_name', 'Test Bank A')['account_number']);
        $this->assertSame('222222', collect($banks)->firstWhere('bank_name', 'Test Bank B')['account_number']);
    }

    public function test_invoice_resolution_prints_only_selected_default_account(): void
    {
        $school = School::firstOrFail();
        App::instance('current_tenant', $school);

        $a = $this->createAccount('Test Bank A', '111111', true);
        $this->createAccount('Test Bank B', '222222', true);

        SystemSetting::set('banking', 'invoice_default_bank_account_id', (string) $a->id);

        $banks = BillingDocumentSettingsService::get()['banks'];

        $this->assertCount(1, $banks);
        $this->assertSame('Test Bank A', $banks[0]['bank_name']);
        $this->assertSame('111111', $banks[0]['account_number']);
    }

    public function test_invoice_resolution_ignores_selected_inactive_account(): void
    {
        $school = School::firstOrFail();
        App::instance('current_tenant', $school);

        $a = $this->createAccount('Test Bank A', '111111', true);
        $inactive = $this->createAccount('Deactivated', '444444', false);

        SystemSetting::set('banking', 'invoice_default_bank_account_id', (string) $inactive->id);

        $banks = BillingDocumentSettingsService::get()['banks'];

        // Inactive default is ignored; falls back to all active accounts.
        $this->assertContains('Test Bank A', collect($banks)->pluck('bank_name')->all());
        $this->assertNotContains('Deactivated', collect($banks)->pluck('bank_name')->all());
    }

    public function test_invoice_resolution_legacy_fallback_only_when_no_accounts(): void
    {
        $school = School::firstOrFail();
        App::instance('current_tenant', $school);

        // Snapshot existing rows so we can temporarily hide them and restore
        // afterwards (the DB is shared with live data).
        $existing = SchoolBankAccount::withoutTenantScope()
            ->where('school_id', $school->id)
            ->get()
            ->map(fn ($row) => $row->getAttributes())
            ->all();
        SchoolBankAccount::withoutTenantScope()->where('school_id', $school->id)->delete();

        try {
            // Remove any lingering legacy repeater JSON so the single-bank
            // fallback is the one being exercised.
            $legacyBanksSetting = SystemSetting::where('group', 'banking')->where('key', 'banks')->first();
            SystemSetting::where('group', 'banking')->where('key', 'banks')->delete();

            SystemSetting::set('banking', 'bank_name', 'Legacy Bank');
            SystemSetting::set('banking', 'account_number', '999999');

            $banks = BillingDocumentSettingsService::get()['banks'];

            $this->assertSame('Legacy Bank', $banks[0]['bank_name']);
        } finally {
            SystemSetting::where('group', 'banking')->whereIn('key', ['bank_name', 'account_number'])->delete();

            if ($legacyBanksSetting) {
                SystemSetting::set('banking', 'banks', json_decode($legacyBanksSetting->value, true));
            }
            SchoolBankAccount::withoutTenantScope()->where('school_id', $school->id)->delete();
            foreach ($existing as $row) {
                $row['updated_at'] = now()->toDateTimeString();
                $row['created_at'] = $row['created_at'] ?? now()->toDateTimeString();
                DB::table('school_bank_accounts')->insert($row);
            }
        }
    }

    protected function createAccount(string $name, string $number, bool $active): SchoolBankAccount
    {
        return SchoolBankAccount::create([
            'school_id' => 15,
            'bank_name' => $name,
            'account_name' => 'School '.$name,
            'account_number' => $number,
            'is_active' => $active,
        ]);
    }
}
