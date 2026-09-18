<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Models\School;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\FinancialAnalyticsEngine;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveFinancialDashboard extends Page
{
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Finance';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Dashboard & Analytics';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $title = 'Executive Financial Operations Center';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.pages.executive-financial-dashboard';

    public string $dateRange = '12_months';

    public string $bankAccountId = ''; // '' => all accounts combined

    public string $activeDetailTab = 'debtors';

    public int $forecastMonths = 6;

    public array $summary = [];

    public array $revenueBreakdown = [];

    public array $revenueStreams = [];

    public array $feeAgeing = [];

    public array $revenueExpenseTrend = [];

    public array $cashFlowTimeline = [];

    public array $collectionRateTrend = [];

    public array $expenseBreakdown = [];

    public array $paymentMethodBreakdown = [];

    public array $topDebtors = [];

    public array $agedReceivablesDetail = [];

    public array $expenseRegister = [];

    public array $yoyComparison = [];

    public array $revenueForecast = [];

    public array $budgetVariance = [];

    public function mount(): void
    {
        $this->bankAccountId = (string) (request()->query('bank_account') ?? session('finance_bank_account', ''));
        $this->loadData();
    }

    public function updatedDateRange(): void
    {
        $this->loadData();
    }

    public function updatedBankAccountId(): void
    {
        session(['finance_bank_account' => $this->bankAccountId ?: null]);
        $this->loadData();
    }

    public function updatedActiveDetailTab(): void
    {
        $this->loadDetailData();
    }

    public function updatedForecastMonths(): void
    {
        $this->loadData();
    }

    #[On('refresh-dashboard')]
    public function refreshDashboard(): void
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        $user = Auth::user();
        $schoolId = session('current_tenant')->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            $schoolId = School::first()?->id ?? 1;
        }

        $months = match ($this->dateRange) {
            '7_days' => 1,
            '30_days' => 1,
            '90_days' => 3,
            '6_months' => 6,
            '12_months' => 12,
            '24_months' => 24,
            default => 12,
        };

        $bankAccountId = $this->bankAccountId !== '' ? (int) $this->bankAccountId : null;

        $engine = app(FinancialAnalyticsEngine::class);
        $this->summary = $engine->getSummary($schoolId, $bankAccountId);
        $this->revenueBreakdown = $engine->getRevenueBreakdown($schoolId, $bankAccountId);
        $this->revenueStreams = $engine->getRevenueStreamsForPeriod($schoolId, null, null, $bankAccountId);
        $this->feeAgeing = $engine->getFeeAgeing($schoolId);
        $this->revenueExpenseTrend = $engine->getRevenueExpenseTrend($schoolId, $months, $bankAccountId);
        $this->cashFlowTimeline = $engine->getCashFlowTimeline($schoolId, $months, $bankAccountId);
        $this->collectionRateTrend = $engine->getCollectionRateTrend($schoolId, $months, $bankAccountId);
        $this->expenseBreakdown = $engine->getExpenseBreakdown($schoolId, $bankAccountId);
        $this->paymentMethodBreakdown = $engine->getPaymentMethodBreakdown($schoolId, $bankAccountId);
        $this->topDebtors = $engine->getTopDebtors($schoolId, 10);
        $this->yoyComparison = $engine->getYoYComparison($schoolId, $bankAccountId);
        $this->revenueForecast = $engine->getRevenueForecast($schoolId, $this->forecastMonths, $bankAccountId);
        $this->budgetVariance = $engine->getBudgetVariance($schoolId);

        $this->loadDetailData();

        $this->dispatch('dashboard-data-updated');
    }

    private function loadDetailData(): void
    {
        $user = Auth::user();
        $schoolId = session('current_tenant')->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            $schoolId = School::first()?->id ?? 1;
        }

        $engine = app(FinancialAnalyticsEngine::class);
        $bankAccountId = $this->bankAccountId !== '' ? (int) $this->bankAccountId : null;

        if ($this->activeDetailTab === 'receivables') {
            $this->agedReceivablesDetail = $engine->getAgedReceivablesDetail($schoolId);
        } elseif ($this->activeDetailTab === 'expenses') {
            $this->expenseRegister = $engine->getExpenseRegister($schoolId, $bankAccountId);
        }
    }

    public function bankAccounts(): Collection
    {
        $schoolId = session('current_tenant')->id ?? (Auth::user()?->school_id ?? 1);

        return SchoolBankAccount::where('school_id', $schoolId)
            ->orderByDesc('is_default')
            ->get();
    }

    public function getDateRangeOptions(): array
    {
        return [
            '7_days' => __('Last 7 Days'),
            '30_days' => __('Last 30 Days'),
            '90_days' => __('Last 90 Days'),
            '6_months' => __('Last 6 Months'),
            '12_months' => __('Last 12 Months'),
            '24_months' => __('Last 24 Months'),
        ];
    }

    public function getForecastMonthOptions(): array
    {
        return [
            3 => __('3 Months'),
            6 => __('6 Months'),
            9 => __('9 Months'),
            12 => __('12 Months'),
            18 => __('18 Months'),
            24 => __('24 Months'),
        ];
    }

    public function getDetailTabs(): array
    {
        return [
            'debtors' => __('Top Debtors'),
            'receivables' => __('Aged Receivables'),
            'expenses' => __('Expense Register'),
            'payments' => __('Payment Methods'),
        ];
    }

    public function exportDashboardCSV(): StreamedResponse
    {
        $schoolId = session('current_tenant')->id ?? (Auth::user()?->school_id ?? 1);

        $engine = app(FinancialAnalyticsEngine::class);
        $bankAccountId = $this->bankAccountId !== '' ? (int) $this->bankAccountId : null;
        $summary = $engine->getSummary($schoolId, $bankAccountId);
        $receivables = $engine->getAgedReceivablesDetail($schoolId);

        $filename = 'executive-financial-summary-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($summary, $receivables) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Metric', 'Value']);
            fputcsv($out, ['Total Revenue', number_format($summary['total_revenue'] ?? 0, 2)]);
            fputcsv($out, ['Total Expenses', number_format($summary['total_expenses'] ?? 0, 2)]);
            fputcsv($out, ['Staff Salaries', number_format($summary['total_salaries'] ?? 0, 2)]);
            fputcsv($out, ['Net Surplus', number_format($summary['net_surplus'] ?? 0, 2)]);
            fputcsv($out, ['Outstanding Fees (AR)', number_format($summary['outstanding_student_fees'] ?? 0, 2)]);

            if (count($receivables) > 0) {
                fputcsv($out, []);
                fputcsv($out, ['Aged Receivables']);
                fputcsv($out, ['Invoice #', 'Student', 'Total', 'Paid', 'Balance', 'Status']);
                foreach ($receivables as $inv) {
                    fputcsv($out, [
                        $inv['invoice_number'] ?? '',
                        $inv['student_name'] ?? '',
                        number_format($inv['total_amount'] ?? 0, 2),
                        number_format($inv['paid_amount'] ?? 0, 2),
                        number_format($inv['balance_amount'] ?? 0, 2),
                        $inv['status'] ?? '',
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
