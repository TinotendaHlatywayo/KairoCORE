<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Students\Models\Student;

/**
 * Public, hash-protected verification page for printed finance documents
 * (invoices, receipts and statements of account). The QR code printed on each
 * document encodes a link to this page, so a parent/guardian can scan it and
 * confirm the document is genuine and see the student + financial details.
 */
class FinanceDocumentVerificationController extends Controller
{
    public function verify(Request $request, $hash)
    {
        $invoice = $this->findByHash($hash);

        if (! $invoice) {
            abort(404, 'Invalid Finance Document Verification Code.');
        }

        $type = in_array($request->query('type'), ['invoice', 'receipt', 'statement'])
            ? $request->query('type')
            : 'invoice';

        $school = app('current_tenant');
        $student = $invoice->student;

        if ($type === 'receipt') {
            $payment = $invoice->payments()
                ->orderByDesc('id')
                ->first();

            if (! $payment) {
                abort(404, 'No payment record found for this receipt.');
            }

            return view('modules.finance.verify-finance', [
                'school' => $school,
                'student' => $student,
                'invoice' => $invoice,
                'payment' => $payment,
                'type' => $type,
            ]);
        }

        if ($type === 'statement') {
            $ledger = $this->buildLedger($student, $school->id);

            return view('modules.finance.verify-finance', [
                'school' => $school,
                'student' => $student,
                'invoice' => $invoice,
                'payment' => null,
                'type' => $type,
                'ledger' => $ledger['ledger'],
                'current_balance' => $ledger['current_balance'],
            ]);
        }

        return view('modules.finance.verify-finance', [
            'school' => $school,
            'student' => $student,
            'invoice' => $invoice,
            'payment' => null,
            'type' => $type,
        ]);
    }

    /**
     * Locate an invoice by its integrity hash. Rows saved before hashes were
     * persisted are matched by recomputing the deterministic hash on the fly.
     */
    protected function findByHash(string $hash): ?Invoice
    {
        $invoice = Invoice::with(['student', 'term.academicYear', 'items'])
            ->where('integrity_hash', $hash)
            ->first();

        if ($invoice) {
            return $invoice;
        }

        return Invoice::with(['student', 'term.academicYear', 'items'])
            ->get()
            ->first(fn (Invoice $invoice) => $invoice->integrity_hash === $hash);
    }

    /**
     * Rebuild the chronological debit/credit ledger for a student, mirroring
     * the statement PDF generation.
     */
    protected function buildLedger(Student $student, int $schoolId): array
    {
        $invoices = Invoice::where('student_id', $student->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $payments = Payment::where('school_id', $schoolId)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->where('is_reversed', false)
            ->orderBy('created_at', 'asc')
            ->get();

        $ledger = [];
        $balance = 0;

        foreach ($invoices as $inv) {
            $balance += $inv->subtotal_amount;
            $ledger[] = [
                'date' => $inv->created_at,
                'type' => "Gross Fees Billed ({$inv->invoice_number})",
                'debit' => $inv->subtotal_amount,
                'credit' => 0.00,
                'running_balance' => $balance,
            ];

            if ($inv->discount_amount > 0) {
                $balance -= $inv->discount_amount;
                $ledger[] = [
                    'date' => $inv->created_at,
                    'type' => 'Waiver Applied: '.($inv->waiver_details ?? 'Scholarship / Discount'),
                    'debit' => 0.00,
                    'credit' => $inv->discount_amount,
                    'running_balance' => $balance,
                ];
            }
        }

        foreach ($payments as $pay) {
            $balance -= $pay->amount;
            $ledger[] = [
                'date' => $pay->payment_date,
                'type' => "Payment Received (Receipt: {$pay->receipt_number})",
                'debit' => 0.00,
                'credit' => $pay->amount,
                'running_balance' => $balance,
            ];
        }

        usort($ledger, fn ($a, $b) => $a['date'] <=> $b['date']);

        return ['ledger' => $ledger, 'current_balance' => $balance];
    }
}
