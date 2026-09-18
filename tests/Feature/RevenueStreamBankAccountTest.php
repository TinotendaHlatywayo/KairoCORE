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
use Modules\Finance\Models\RevenueCategory;
use Modules\Finance\Models\RevenueStream;
use Modules\Finance\Models\SchoolBankAccount;

class RevenueStreamBankAccountTest extends TestCase
{
    protected int $schoolId;

    private array $preExistingStreamIds = [];

    private array $preExistingAccountIds = [];

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
    }

    protected function tearDown(): void
    {
        RevenueStream::withoutGlobalScopes()->whereNotIn('id', $this->preExistingStreamIds)->forceDelete();
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
}
