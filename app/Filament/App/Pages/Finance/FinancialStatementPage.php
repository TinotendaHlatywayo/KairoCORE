<?php

namespace App\Filament\App\Pages\Finance;

use Filament\Pages\Page;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\Expense;
use Carbon\Carbon;

class FinancialStatementPage extends Page
{
    protected static string $view = 'filament.app.pages.finance.financial-statement';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Financial Statements';

    public static function getNavigationLabel(): string
    {
        return __('Financial Statements');
    }

    public string $range = 'month'; // day, week, month, year

    public function getTitle(): string
    {
        return __('School Financial Statement & Cash Flow');
    }

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id ?? 1;
        $defaultBank = SchoolBankAccount::where('school_id', $schoolId)->where('is_default', true)->first() 
            ?? SchoolBankAccount::where('school_id', $schoolId)->first();

        if (! $defaultBank) {
            $defaultBank = SchoolBankAccount::create([
                'school_id' => $schoolId,
                'bank_name' => 'Stanbic Bank Zimbabwe',
                'account_name' => 'School Operating Account',
                'account_number' => '9140001234567',
                'branch_code' => '02',
                'balance' => 5000.00,
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        $startDate = match ($this->range) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $totalRevenue = Payment::where('school_id', $schoolId)
            ->where('is_refund', false)
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $totalRefunds = Payment::where('school_id', $schoolId)
            ->where('is_refund', true)
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $totalExpenses = Expense::where('school_id', $schoolId)
            ->where('expense_date', '>=', $startDate->toDateString())
            ->sum('amount');

        $netCashFlow = ($totalRevenue - $totalRefunds) - $totalExpenses;

        $school = current_tenant();
        $season = $this->currentSeasonLabel();

        return [
            'defaultBank' => $defaultBank,
            'totalRevenue' => $totalRevenue,
            'totalRefunds' => $totalRefunds,
            'totalExpenses' => $totalExpenses,
            'netCashFlow' => $netCashFlow,
            'startDate' => $startDate->toDateString(),
            'endDate' => now()->toDateString(),
            'company' => $school?->name ?? config('app.name'),
            'companyTagline' => $school?->tagline ?? null,
            'companyAddress' => $school?->physical_address ?? $school?->address ?? '',
            'companyPhone' => $school?->phone ?? null,
            'companyEmail' => $school?->email ?? null,
            'season' => $season,
        ];
    }

    /**
     * Resolve the current term/season label (e.g. "Term 1 - 2026") so the
     * official statement header matches the reporting period.
     */
    protected function currentSeasonLabel(): ?string
    {
        $schoolId = current_tenant()?->id ?? 1;

        $term = \Modules\Academics\Models\Term::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('academicYear')
            ->orderByDesc('id')
            ->first();

        if (! $term) {
            return null;
        }

        return trim(implode(' | ', array_filter([
            $term->name,
            $term->academicYear?->name,
        ])));
    }
}
