<?php

namespace Tests\Feature;

use App\Filament\App\Pages\ExecutiveFinancialDashboard;
use App\Filament\App\Pages\Finance\FinancialStatementPage;
use App\Filament\App\Resources\ExpenseResource\Pages\ListExpenses;
use App\Filament\App\Resources\RevenueStreamResource\Pages\CreateRevenueStream;
use App\Filament\App\Widgets\BankAccountSwitcherWidget;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\RevenueCategory;
use Modules\Finance\Models\RevenueStream;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\FinancialAnalyticsEngine;

class RevenueStreamBankAccountTest extends TestCase
{
    protected int $schoolId;

    private array $preExistingStreamIds = [];

    private array $preExistingAccountIds = [];

    private array $preExistingPaymentIds = [];

    private array $preExistingExpenseIds = [];

    private array $preExistingInvoiceIds = [];

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

        $this->schoolId = (int) config('tenancy.single_tenant_id');
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);

        $this->preExistingStreamIds = RevenueStream::withoutGlobalScopes()->pluck('id')->all();
        $this->preExistingAccountIds = SchoolBankAccount::withoutTenantScope()->pluck('id')->all();
        $this->preExistingPaymentIds = Payment::withoutGlobalScopes()->pluck('id')->all();
        $this->preExistingExpenseIds = Expense::withoutGlobalScopes()->pluck('id')->all();
        $this->preExistingInvoiceIds = Invoice::withoutGlobalScopes()->pluck('id')->all();
    }

    protected function tearDown(): void
    {
        RevenueStream::withoutGlobalScopes()->whereNotIn('id', $this->preExistingStreamIds)->forceDelete();
        Payment::withoutGlobalScopes()->whereNotIn('id', $this->preExistingPaymentIds)->forceDelete();
        Expense::withoutGlobalScopes()->whereNotIn('id', $this->preExistingExpenseIds)->forceDelete();
        Invoice::withoutGlobalScopes()->whereNotIn('id', $this->preExistingInvoiceIds)->forceDelete();
        SchoolBankAccount::withoutTenantScope()->whereNotIn('id', $this->preExistingAccountIds)->forceDelete();
        parent::tearDown();
    }

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    public function test_create_revenue_stream_credits_bank_account(): void
    {
        $user = User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $bank = SchoolBankAccount::create([
            'school_id' => $this->schoolId,
            'bank_name' => 'Temp Repro Bank',
            'account_name' => 'Repro Account',
            'account_number' => '000-TEMP-'.uniqid(),
            'balance' => 100.00,
            'is_active' => true,
            'is_default' => false,
        ]);

        $category = RevenueCategory::withoutGlobalScopes()->first()
            ?? RevenueCategory::create(['school_id' => $this->schoolId, 'name' => 'Temp Repro Category']);

        Livewire::test(CreateRevenueStream::class)
            ->assertOk()
            ->fillForm([
                'revenue_category_id' => $category->id,
                'name' => 'Temp Repro Stream',
                'default_amount' => 250,
                'created_at' => now()->toDateString(),
                'account_id' => $bank->id,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $stream = RevenueStream::withoutGlobalScopes()->where('name', 'Temp Repro Stream')->latest('id')->first();
        $this->assertNotNull($stream, 'Revenue stream was not created (the original 500).');
        $this->assertSame($bank->id, (int) $stream->account_id);
        $this->assertSame(350.0, (float) $bank->fresh()->balance, 'Bank account balance was not credited.');
    }

    public function test_finance_pages_render_with_account_switcher(): void
    {
        $user = User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        Livewire::test(FinancialStatementPage::class)->assertOk();
        Livewire::test(ExecutiveFinancialDashboard::class)->assertOk();
        Livewire::test(ListExpenses::class)->assertOk();
        Livewire::test(BankAccountSwitcherWidget::class)->assertOk();
    }

    public function test_unassigned_transactions_count_toward_default_bank_account(): void
    {
        $bankDefault = SchoolBankAccount::create([
            'school_id' => $this->schoolId,
            'bank_name' => 'Temp Default Bank',
            'account_name' => 'Default Account',
            'account_number' => 'DFT-'.uniqid(),
            'balance' => 0.00,
            'is_active' => true,
            'is_default' => true,
        ]);
        $bankOther = SchoolBankAccount::create([
            'school_id' => $this->schoolId,
            'bank_name' => 'Temp Other Bank',
            'account_name' => 'Other Account',
            'account_number' => 'OTH-'.uniqid(),
            'balance' => 0.00,
            'is_active' => true,
            'is_default' => false,
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->schoolId,
            'invoice_number' => 'INV-'.uniqid(),
        ]);

        try {
            $engine = app(FinancialAnalyticsEngine::class);

            $beforeDefault = $engine->getSummary($this->schoolId, (int) $bankDefault->id);
            $beforeOther = $engine->getSummary($this->schoolId, (int) $bankOther->id);

            Payment::create([
                'school_id' => $this->schoolId,
                'invoice_id' => $invoice->id,
                'amount' => 50,
                'bank_account_id' => null,
                'is_reversed' => false,
                'payment_date' => now(),
            ]);
            Expense::create([
                'school_id' => $this->schoolId,
                'expense_name' => 'Unassigned expense',
                'amount' => 50,
                'expense_date' => now()->toDateString(),
                'status' => 'paid',
                'bank_account_id' => null,
            ]);

            $afterDefault = $engine->getSummary($this->schoolId, (int) $bankDefault->id);
            $afterOther = $engine->getSummary($this->schoolId, (int) $bankOther->id);

            $this->assertSame(
                50.0,
                (float) ($afterDefault['total_revenue'] - $beforeDefault['total_revenue']),
                'Default bank must gain revenue from the unassigned payment.'
            );
            $this->assertSame(
                50.0,
                (float) ($afterDefault['total_expenses'] - $beforeDefault['total_expenses']),
                'Default bank must gain the unassigned expense.'
            );
            $this->assertSame(
                0.0,
                (float) ($afterOther['total_revenue'] - $beforeOther['total_revenue']),
                'Unassigned transactions must NOT count toward a non-default bank.'
            );
            $this->assertSame(
                0.0,
                (float) ($afterOther['total_expenses'] - $beforeOther['total_expenses']),
                'Unassigned transactions must NOT count toward a non-default bank.'
            );
        } finally {
            Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->forceDelete();
            Expense::withoutGlobalScopes()->where('expense_name', 'Unassigned expense')->forceDelete();
            Invoice::withoutGlobalScopes()->where('id', $invoice->id)->forceDelete();
            SchoolBankAccount::withoutTenantScope()->whereIn('id', [$bankDefault->id, $bankOther->id])->forceDelete();
        }
    }

    public function test_opening_bank_balance_is_zero_for_accounts_created_within_the_period(): void
    {
        $user = User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $bank = SchoolBankAccount::create([
            'school_id' => $this->schoolId,
            'bank_name' => 'Temp Fresh Bank',
            'account_name' => 'Brand New Account',
            'account_number' => 'NEW-'.uniqid(),
            'balance' => 200.00,
            'is_active' => true,
            'is_default' => true,
        ]);

        try {
            $html = Livewire::test(FinancialStatementPage::class)
                ->set('bankAccountId', (string) $bank->id)
                ->assertOk()
                ->html();

            // A brand-new account (created within the 'past month' period) must
            // open at $0.00 - its recorded balance is NOT the opening balance.
            $this->assertMatchesRegularExpression('/Opening Bank Balance.+?\$0\.00/s', $html);
            // Closing = Opening + net flow, so it must not silently inherit the
            // account's current balance ($200.00) either.
            $this->assertDoesNotMatchRegularExpression('/Closing Balance.+?\$200\.00/s', $html);
        } finally {
            SchoolBankAccount::withoutTenantScope()->where('id', $bank->id)->forceDelete();
        }
    }
}
