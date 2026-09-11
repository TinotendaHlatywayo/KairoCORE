<?php

namespace App\Filament\App\Resources\PayrollPeriodResource\Pages;

use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\Payslip;

class PayrollPeriodBreakdownPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = \App\Filament\App\Resources\PayrollPeriodResource::class;

    protected static string $view = 'filament.app.pages.hr.payroll-period-breakdown';

    public ?PayrollPeriod $record = null;

    public function mount(PayrollPeriod $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return __('Payroll Ledger Breakdown: ') . ($this->record?->name ?? '');
    }

    protected function getViewData(): array
    {
        $payslips = Payslip::query()
            ->where('school_id', current_tenant()?->id ?? 1)
            ->whereHas('run', fn ($q) => $q->where('payroll_period_id', $this->record?->id))
            ->with('items')
            ->get();

        $totalLoanDeductions = 0.00;
        foreach ($payslips as $p) {
            $totalLoanDeductions += (float) $p->items->where('code', 'LOAN_REC')->sum('amount');
        }

        return [
            'totalBaseSalary' => $payslips->sum('base_salary'),
            'totalGross' => $payslips->sum('gross_pay'),
            'totalNet' => $payslips->sum('net_pay'),
            'totalDeductions' => $payslips->sum('total_deductions'),
            'totalLoanDeductions' => $totalLoanDeductions,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payslip::query()
                    ->where('school_id', current_tenant()?->id ?? 1)
                    ->whereHas('run', fn ($q) => $q->where('payroll_period_id', $this->record?->id))
                    ->with(['employee', 'items'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee')
                    ->label(__('Employee Name & Staff ID'))
                    ->getStateUsing(fn (Payslip $record) => optional($record->employee)->first_name ? "{$record->employee->first_name} {$record->employee->last_name} ({$record->employee->employee_number})" : '-')
                    ->searchable(['first_name', 'last_name', 'employee_number'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.national_id')
                    ->label(__('National ID'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.employee_number')
                    ->label(__('Staff ID'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->label(__('Base Salary'))
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('allowances')
                    ->label(__('Allowances (Earnings)'))
                    ->getStateUsing(function (Payslip $record) {
                        return $record->items->where('type', 'earning')
                            ->map(fn ($i) => "{$i->name}: \${$i->amount}")
                            ->implode(' | ');
                    }),
                Tables\Columns\TextColumn::make('deductions')
                    ->label(__('Deductions (Taxes & Loans)'))
                    ->getStateUsing(function (Payslip $record) {
                        return $record->items->where('type', 'deduction')
                            ->map(fn ($i) => "{$i->name}: \${$i->amount}")
                            ->implode(' | ');
                    }),
                Tables\Columns\TextColumn::make('net_pay')
                    ->label(__('Net Total Received'))
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}
