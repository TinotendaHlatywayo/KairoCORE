<?php

namespace Tests\Feature;

use App\Filament\App\Widgets\FinanceDashboardSummaryWidget;
use App\Models\School;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\PaymentSettlementService;
use Modules\Students\Models\Student;
use ReflectionMethod;

class OverpaymentInRevenueFiguresTest extends TestCase
{
    protected int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');

        $this->schoolId = (int) config('tenancy.single_tenant_id');
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);
    }

    /**
     * A credited overpayment must count in Total Revenue / Net Cash / Bank
     * Balance immediately, because the full cash was already collected and sits
     * in the school account (the excess riding on credit_balance).
     */
    public function test_credited_overpayment_counts_as_collected_revenue(): void
    {
        DB::beginTransaction();

        try {
            $before = $this->widgetStats();

            $student = Student::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id_number' => 'REV-'.uniqid(),
                'admission_number' => 'REVADM-'.uniqid(),
                'first_name' => 'Revenue',
                'last_name' => 'Probe '.uniqid(),
                'gender' => 'male',
                'date_of_birth' => now()->subYears(14)->toDateString(),
                'admission_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);

            $invoice = Invoice::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'invoice_number' => 'REVINV-'.uniqid(),
                'currency' => 'USD',
                'subtotal_amount' => 100.00,
                'total_amount' => 100.00,
                'paid_amount' => 0,
                'balance_amount' => 100.00,
                'status' => 'unpaid',
                'due_date' => now()->addDays(30)->toDateString(),
            ]);

            $bank = SchoolBankAccount::create([
                'school_id' => $this->schoolId,
                'bank_name' => 'Revenue Probe Bank',
                'account_name' => 'Probe Account',
                'account_number' => 'REV-'.uniqid(),
                'balance' => 0.00,
                'is_active' => true,
                'is_default' => false,
            ]);

            // Pay $200 on a $100 invoice, carrying the $100 excess as a credit.
            PaymentSettlementService::settle(
                $invoice,
                200.00,
                [
                    'receipt_number' => 'REVRCP-'.uniqid(),
                    'reference_number' => 'REVREF-'.uniqid(),
                    'payment_method' => 'cash',
                    'payment_date' => now(),
                    'currency' => 'USD',
                ],
                PaymentSettlementService::MODE_CREDIT,
                $bank->id,
            );

            $this->assertSame(100.0, (float) $student->fresh()->credit_balance);
            $this->assertSame(200.0, (float) $bank->fresh()->balance);

            $after = $this->widgetStats();

            // The excess is collected cash, so the whole 200 (100 applied to the
            // invoice + 100 parked on credit_balance) must register as revenue.
            $this->assertSame(
                '$200.00',
                $this->delta($before, $after, 'Total Revenue Collected'),
                'Total Revenue must include the collected overpayment excess.'
            );

            $this->assertSame(
                '$200.00',
                $this->delta($before, $after, 'Net Cash Position'),
                'Net Cash must include the collected overpayment excess.'
            );

            // Bank Balance is max(0, net). This fixture school carries a large
            // pre-existing refund/expense ledger that keeps net negative, so the
            // balance stays clamped at 0 — but it must move exactly as net moves.
            $netOld = (float) $this->unformat($before['Net Cash Position']);
            $netNew = (float) $this->unformat($after['Net Cash Position']);
            $expectedBankDelta = max(0.0, $netNew) - max(0.0, $netOld);

            $this->assertSame(
                $this->format($expectedBankDelta),
                $this->delta($before, $after, 'Bank Balance'),
                'Bank Balance must track net (max(0, net)).'
            );

            $this->assertSame('$0.00', $this->delta($before, $after, 'Outstanding Fees'), 'Invoice is fully paid after settlement.');
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Internal carry-forward settlement rows (zero cash, they merely move an
     * unpaid balance onto a CF- invoice) must never count as collected revenue.
     */
    public function test_carry_forward_settlement_payments_do_not_count_as_revenue(): void
    {
        DB::beginTransaction();

        try {
            $before = $this->widgetStats();

            $student = Student::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id_number' => 'CFX-'.uniqid(),
                'admission_number' => 'CFXADM-'.uniqid(),
                'first_name' => 'Carry',
                'last_name' => 'Forward '.uniqid(),
                'gender' => 'male',
                'date_of_birth' => now()->subYears(13)->toDateString(),
                'admission_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);

            // An unpaid source invoice that was settled internally when the term
            // switched: it now carries the carried_forward_at marker.
            $source = Invoice::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'invoice_number' => 'CFXSRC-'.uniqid(),
                'currency' => 'USD',
                'subtotal_amount' => 80.00,
                'total_amount' => 80.00,
                'paid_amount' => 80.00,
                'balance_amount' => 0.00,
                'status' => 'paid',
                'carried_forward_at' => now(),
                'due_date' => now()->addDays(30)->toDateString(),
            ]);

            Payment::create([
                'school_id' => $this->schoolId,
                'invoice_id' => $source->id,
                'receipt_number' => 'CREDIT-'.mt_rand(10000, 99999),
                'reference_number' => 'CARRY-FWD-'.uniqid(),
                'amount' => 80.00,
                'currency' => 'USD',
                'payment_method' => 'credit',
                'payment_date' => now(),
                'is_refund' => false,
                'excess_handling' => PaymentSettlementService::MODE_CREDIT,
            ]);

            $after = $this->widgetStats();

            foreach (['Total Revenue Collected', 'Net Cash Position', 'Bank Balance'] as $label) {
                $this->assertSame(
                    '$0.00',
                    $this->delta($before, $after, $label),
                    "{$label} must ignore zero-cash carry-forward settlement rows."
                );
            }

            $this->assertSame(
                '$0.00',
                $this->delta($before, $after, 'Outstanding Fees'),
                'A settled (paid) source invoice adds nothing to outstanding.'
            );
        } finally {
            DB::rollBack();
        }
    }

    /**
     * @return array<string, string> stat label => formatted value
     */
    private function widgetStats(): array
    {
        $widget = new FinanceDashboardSummaryWidget;
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        $map = [];
        foreach ($stats as $stat) {
            $map[(string) $stat->getLabel()] = (string) $stat->getValue();
        }

        return $map;
    }

    private function delta(array $before, array $after, string $label): string
    {
        $old = (float) $this->unformat($before[$label] ?? '$0.00');
        $new = (float) $this->unformat($after[$label] ?? '$0.00');
        $delta = round($new - $old, 2);

        return $this->format($delta);
    }

    private function format(float $delta): string
    {
        return ($delta < 0 ? '-$' : '$').number_format(abs($delta), 2);
    }

    private function unformat(string $value): string
    {
        return str_replace(['$', ','], '', $value);
    }
}
