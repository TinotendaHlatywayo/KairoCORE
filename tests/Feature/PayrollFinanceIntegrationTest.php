<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\Payslip;
use Modules\HR\Models\PayslipItem;
use Modules\HR\Models\SalaryGrade;
use Modules\HR\Models\StaffLoan;
use Modules\HR\Services\PayrollCalculationService;

class PayrollFinanceIntegrationTest extends TestCase
{
    protected int $schoolId;

    private array $tracked = [
        'expenses' => [],
        'payroll_periods' => [],
        'payslips' => [],
        'payslip_items' => [],
        'loans' => [],
        'employees' => [],
        'grades' => [],
        'accounts' => [],
    ];

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
    }

    protected function tearDown(): void
    {
        Expense::withoutGlobalScopes()->whereIn('id', $this->tracked['expenses'])->forceDelete();

        PayslipItem::withoutGlobalScopes()->whereIn('id', $this->tracked['payslip_items'])->forceDelete();
        Payslip::withoutGlobalScopes()->whereIn('id', $this->tracked['payslips'])->forceDelete();
        PayrollPeriod::withoutGlobalScopes()->whereIn('id', $this->tracked['payroll_periods'])->forceDelete();
        StaffLoan::withoutGlobalScopes()->whereIn('id', $this->tracked['loans'])->forceDelete();
        Employee::whereIn('id', $this->tracked['employees'])->forceDelete();

        SalaryGrade::whereIn('id', $this->tracked['grades'])->forceDelete();
        SchoolBankAccount::withoutTenantScope()->whereIn('id', $this->tracked['accounts'])->forceDelete();

        parent::tearDown();
    }

    private function makeBank(float $balance, bool $default = true): SchoolBankAccount
    {
        $bank = SchoolBankAccount::create([
            'school_id' => $this->schoolId,
            'bank_name' => 'Payroll '.(count($this->tracked['accounts']) + 1).' Bank',
            'account_name' => 'Payroll Test Account',
            'account_number' => 'PAY-'.uniqid(),
            'balance' => $balance,
            'is_active' => true,
            'is_default' => $default,
        ]);
        $this->tracked['accounts'][] = $bank->id;

        return $bank;
    }

    private function makeGrade(array $overrides = []): SalaryGrade
    {
        $grade = SalaryGrade::create(array_merge([
            'school_id' => $this->schoolId,
            'name' => 'Grade '.uniqid(),
            'base_salary' => 1000,
            'housing_allowance' => 0,
            'transport_allowance' => 0,
            'duty_allowance' => 0,
            'overtime_eligible' => false,
            'custom_allowances' => [],
            'custom_deductions' => [],
        ], $overrides));
        $this->tracked['grades'][] = $grade->id;

        return $grade;
    }

    private function makeEmployee(SalaryGrade $grade): Employee
    {
        $employee = Employee::create([
            'school_id' => $this->schoolId,
            'first_name' => 'Payroll',
            'last_name' => 'Test '.uniqid(),
            'national_id' => 'PAY-'.uniqid(),
            'email' => 'payroll-'.uniqid().'@schcore.test',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '0771111111',
            'physical_address' => 'Harare',
            'emergency_contact_name' => 'Emergency',
            'emergency_contact_phone' => '0772222222',
            'designation' => 'Teacher',
            'date_joined' => now()->toDateString(),
            'status' => 'active',
            'employment_type' => 'full_time',
            'current_grade_id' => $grade->id,
        ]);
        $this->tracked['employees'][] = $employee->id;

        return $employee;
    }

    private function makePeriod(): PayrollPeriod
    {
        $period = PayrollPeriod::create([
            'school_id' => $this->schoolId,
            'name' => 'Period '.uniqid(),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'draft',
        ]);
        $this->tracked['payroll_periods'][] = $period->id;

        return $period;
    }

    public function test_percentage_components_and_no_pension_paye_placeholders(): void
    {
        $grade = $this->makeGrade([
            'housing_allowance' => 100,
            'custom_allowances' => [
                ['name' => 'Tech Allow', 'type' => 'percentage', 'percentage_of' => 'base', 'amount' => 10],
            ],
            'custom_deductions' => [
                ['name' => 'Union', 'type' => 'percentage', 'percentage_of' => 'gross', 'amount' => 5, 'deduction_type' => 'tax'],
            ],
        ]);

        $ledger = app(PayrollCalculationService::class)->gradeLedger($grade);
        $this->assertSame(1200.0, (float) $ledger['gross']);
        $this->assertSame(60.0, (float) $ledger['deductions_total']);
        $this->assertSame(1140.0, (float) $ledger['net']);

        $employee = $this->makeEmployee($grade);
        $period = $this->makePeriod();

        $run = app(PayrollCalculationService::class)->executeRun($period);
        $this->assertSame('calculated', $period->fresh()->status);

        $payslip = Payslip::withoutGlobalScopes()->where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->firstOrFail();
        $items = PayslipItem::withoutGlobalScopes()->where('payslip_id', $payslip->id)->pluck('code')->all();

        $this->assertNotContains('PENSION', $items, 'Placeholder pension deduction must be removed.');
        $this->assertNotContains('PAYE', $items, 'Placeholder PAYE deduction must be removed.');
        $this->assertFalse(PayslipItem::withoutGlobalScopes()->where('payslip_id', $payslip->id)
            ->where(fn ($q) => $q->where('name', 'like', '%Pension%')->orWhere('name', 'like', '%Income Tax%'))
            ->exists());

        $this->assertSame(1200.0, (float) $payslip->gross_pay);
        $this->assertSame(60.0, (float) $payslip->total_deductions);
        $this->assertSame(1140.0, (float) $payslip->net_pay);
    }

    public function test_approve_debits_bank_account_and_records_salary_expense(): void
    {
        $bank = $this->makeBank(5000);
        $grade = $this->makeGrade();
        $this->makeEmployee($grade);
        $period = $this->makePeriod();
        app(PayrollCalculationService::class)->executeRun($period);

        $result = app(PayrollCalculationService::class)->approvePeriod($period, $bank->id);

        $this->assertFalse($result['skipped']);
        $this->assertSame(1000.0, (float) $result['outflow']);
        $this->assertSame(4000.0, (float) $bank->fresh()->balance, 'Salaries were not deducted from the bank account.');
        $this->assertSame('approved', $period->fresh()->status);
        $this->assertSame($bank->id, (int) $period->fresh()->bank_account_id);

        $expense = Expense::withoutGlobalScopes()->where('reference_number', 'EXP-PAYROLL-'.$period->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame(1000.0, (float) $expense->amount);
        $this->assertSame($bank->id, (int) $expense->bank_account_id);
        $this->assertSame('Payroll & Compensation', $expense->expenseCategory?->name);

        $this->tracked['expenses'][] = $expense->id;
    }

    public function test_approve_outflow_includes_tax_type_deductions(): void
    {
        $bank = $this->makeBank(5000);
        $grade = $this->makeGrade([
            'custom_deductions' => [
                ['name' => 'Reserve Tax', 'type' => 'fixed', 'amount' => 50, 'deduction_type' => 'tax'],
            ],
        ]);
        $this->makeEmployee($grade);
        $period = $this->makePeriod();
        app(PayrollCalculationService::class)->executeRun($period);

        $result = app(PayrollCalculationService::class)->approvePeriod($period, $bank->id);

        $this->assertSame(1000.0, (float) $result['outflow'], 'Outflow = net (950) + tax-type deductions (50).');
        $this->assertSame(4000.0, (float) $bank->fresh()->balance);

        $expense = Expense::withoutGlobalScopes()->where('reference_number', 'EXP-PAYROLL-'.$period->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame(1000.0, (float) $expense->amount);
        $this->tracked['expenses'][] = $expense->id;
    }

    public function test_loan_repayment_returns_to_loan_account_on_release(): void
    {
        $bank = $this->makeBank(2000);
        $grade = $this->makeGrade();
        $employee = $this->makeEmployee($grade);

        $loan = StaffLoan::create([
            'school_id' => $this->schoolId,
            'employee_id' => $employee->id,
            'loan_type' => 'salary_advance',
            'principal_amount' => 500,
            'interest_rate' => 0,
            'interest_type' => 'percentage',
            'repayment_method' => 'fixed',
            'total_repayable' => 500,
            'balance_remaining' => 500,
            'monthly_deduction' => 100,
            'monthly_deduction_type' => 'fixed',
            'bank_account_id' => $bank->id,
            'status' => 'active',
        ]);
        $this->tracked['loans'][] = $loan->id;

        $period = $this->makePeriod();
        $run = app(PayrollCalculationService::class)->executeRun($period);

        $payslip = Payslip::withoutGlobalScopes()->where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->firstOrFail();
        $loanItem = PayslipItem::withoutGlobalScopes()->where('payslip_id', $payslip->id)->where('code', 'LOAN_REC')->first();
        $this->assertNotNull($loanItem, 'Loan repayment line must be generated.');
        $this->assertSame('loan', $loanItem->deduction_type);
        $this->assertSame(100.0, (float) $loanItem->amount);
        $this->assertSame(900.0, (float) $payslip->net_pay);

        app(PayrollCalculationService::class)->approvePeriod($period, $bank->id);
        app(PayrollCalculationService::class)->releaseRun($period);

        $this->assertSame(400.0, (float) $loan->fresh()->balance_remaining, 'Loan balance must reduce by the repaid amount.');
        $this->assertSame(1200.0, (float) $bank->fresh()->balance, 'Approval debits 900 net; repayment returns 100 into the loan account.');
        $this->assertSame('active', $loan->fresh()->status);
    }

    public function test_reducing_balance_loan_accrues_interest_once_per_period(): void
    {
        $bank = $this->makeBank(2000);
        $grade = $this->makeGrade();
        $employee = $this->makeEmployee($grade);

        $loan = StaffLoan::create([
            'school_id' => $this->schoolId,
            'employee_id' => $employee->id,
            'loan_type' => 'salary_advance',
            'principal_amount' => 500,
            'interest_rate' => 10,
            'interest_type' => 'percentage',
            'repayment_method' => 'reducing_balance',
            'total_repayable' => 500,
            'balance_remaining' => 500,
            'monthly_deduction' => 100,
            'monthly_deduction_type' => 'fixed',
            'bank_account_id' => $bank->id,
            'status' => 'active',
        ]);
        $this->tracked['loans'][] = $loan->id;

        $period = $this->makePeriod();
        $run = app(PayrollCalculationService::class)->executeRun($period);

        $payslip = Payslip::withoutGlobalScopes()->where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(100.0, (float) PayslipItem::withoutGlobalScopes()->where('payslip_id', $payslip->id)->where('code', 'LOAN_REC')->firstOrFail()->amount);

        app(PayrollCalculationService::class)->approvePeriod($period, $bank->id);
        app(PayrollCalculationService::class)->releaseRun($period);

        $fresh = $loan->fresh();
        $this->assertSame(450.0, (float) $fresh->balance_remaining, '500 + 10% interest (50) - 100 repayment = 450.');
        $this->assertSame($period->id, (int) $fresh->last_interest_payroll_period_id);
        $this->assertSame(1200.0, (float) $bank->fresh()->balance, 'Approval debits 900 net; repayment returns 100 into the loan account.');
    }

    public function test_undo_restores_bank_balance_and_removes_salary_expense(): void
    {
        $bank = $this->makeBank(5000);
        $grade = $this->makeGrade();
        $this->makeEmployee($grade);
        $period = $this->makePeriod();
        app(PayrollCalculationService::class)->executeRun($period);

        app(PayrollCalculationService::class)->approvePeriod($period, $bank->id);

        $expense = Expense::withoutGlobalScopes()->where('reference_number', 'EXP-PAYROLL-'.$period->id)->first();
        $this->tracked['expenses'][] = $expense->id;

        $result = app(PayrollCalculationService::class)->revertRun($period);

        $this->assertTrue($result['reverted']);
        $this->assertSame(1000.0, (float) $result['restored_to_bank']);
        $this->assertSame(5000.0, (float) $bank->fresh()->balance, 'Bank balance must be restored after undo.');
        $this->assertSame($bank->id, (int) $period->fresh()->bank_account_id, 'Chosen deduction account persists so re-approval reuses it.');
        $this->assertSame('calculated', $period->fresh()->status);
        $this->assertFalse(Expense::withoutGlobalScopes()->where('reference_number', 'EXP-PAYROLL-'.$period->id)->exists(), 'Salary expense must be removed after undo.');
    }

    public function test_undo_on_calculated_period_is_noop(): void
    {
        $grade = $this->makeGrade();
        $this->makeEmployee($grade);
        $period = $this->makePeriod();
        app(PayrollCalculationService::class)->executeRun($period);

        $result = app(PayrollCalculationService::class)->revertRun($period);

        $this->assertFalse($result['reverted']);
    }

    public function test_undo_reverses_loan_repayment_and_interest_accrual(): void
    {
        $bank = $this->makeBank(2000);
        $grade = $this->makeGrade();
        $employee = $this->makeEmployee($grade);

        $loan = StaffLoan::create([
            'school_id' => $this->schoolId,
            'employee_id' => $employee->id,
            'loan_type' => 'salary_advance',
            'principal_amount' => 500,
            'interest_rate' => 10,
            'interest_type' => 'percentage',
            'repayment_method' => 'reducing_balance',
            'total_repayable' => 500,
            'balance_remaining' => 500,
            'monthly_deduction' => 100,
            'monthly_deduction_type' => 'fixed',
            'bank_account_id' => $bank->id,
            'status' => 'active',
        ]);
        $this->tracked['loans'][] = $loan->id;

        $period = $this->makePeriod();
        app(PayrollCalculationService::class)->executeRun($period);
        app(PayrollCalculationService::class)->approvePeriod($period, $bank->id);
        app(PayrollCalculationService::class)->releaseRun($period);

        $result = app(PayrollCalculationService::class)->revertRun($period);

        $fresh = $loan->fresh();
        $this->assertTrue($result['reverted']);
        $this->assertSame(500.0, (float) $fresh->balance_remaining, 'Loan balance must return to 500 after undo.');
        $this->assertSame('active', $fresh->status);
        $this->assertNull($fresh->last_interest_payroll_period_id, 'Interest accrual marker must be cleared.');
        $this->assertSame(2000.0, (float) $bank->fresh()->balance, 'Borrowed repayment must return to its bank account after undo.');
    }
}
