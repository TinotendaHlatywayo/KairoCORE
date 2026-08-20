<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\FeeStructure;
use Modules\Finance\Models\FinanceDocumentTemplate;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\BillingDocumentSettingsService;
use Modules\Students\Models\Student; // FIX 1: Explicitly imports PHP's native ZipArchive utility
use ZipArchive; // Added for storage file cleanups

class FinanceDocumentController extends Controller
{
    /**
     * Prints an individual custom Invoice
     */
    public function printInvoice($id)
    {
        $payload = $this->getInvoicePayload($id);

        $pdf = Pdf::loadView('modules.finance.invoice-pdf', $payload)
            ->setPaper('a4', 'portrait');

        $safeInvoiceNum = str_replace(['/', '\\'], '_', $payload['invoice']->invoice_number);

        return $pdf->stream("Invoice_{$safeInvoiceNum}.pdf");
    }

    /**
     * Prints multiple filtered Invoices in a single file with page-breaks
     */
    public function bulkGenerate(Request $request)
    {
        $idsString = $request->query('ids');
        $mode = $request->query('mode', 'combined');
        $type = $request->query('type', 'invoices');

        if (! $idsString) {
            return redirect()->back()->with('error', 'No records selected.');
        }

        $ids = explode(',', $idsString);
        $school = app('current_tenant');

        if ($type === 'receipts') {
            return $this->bulkGenerateReceipts($ids, $mode, $school);
        }

        if ($type === 'statements') {
            return $this->bulkGenerateStatements($ids, $mode, $school);
        }

        // Mode A: Single Combined PDF file with Page-Breaks
        if ($mode === 'combined') {
            $reportsData = [];
            foreach ($ids as $id) {
                $reportsData[] = $this->getInvoicePayload($id);
            }

            // FIX: Changed payload key from 'reports' to 'invoices' to match blade loop
            $pdf = Pdf::loadView('modules.finance.invoice-bulk-pdf', [
                'school' => $school,
                'invoices' => $reportsData,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('Bulk_Invoices.pdf');
        }

        // Mode B: ZIP Archive containing individual PDFs
        if ($mode === 'zip') {
            $zip = new ZipArchive;

            // FIX 2: Generate permission-safe ZIP path inside Laravel's local storage folder
            $zipFileName = storage_path('app/public/Invoices_Archive_'.time().'.zip');

            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($ids as $id) {
                    $payload = $this->getInvoicePayload($id);

                    $pdf = Pdf::loadView('modules.finance.invoice-pdf', $payload)
                        ->setPaper('a4', 'portrait');

                    // Sanitizes slash and backslash characters inside the zip paths
                    $safeAdmission = str_replace(['/', '\\'], '_', $payload['student']->admission_number);
                    $filename = 'Invoice_'.$safeAdmission.'.pdf';

                    $zip->addFromString($filename, $pdf->output());
                }
                $zip->close();

                // Stream the download and automatically clean the file from storage afterwards
                return response()->download($zipFileName, 'Invoices_Archive.zip')->deleteFileAfterSend(true);
            } else {
                return response()->json(['error' => 'Failed to build ZIP archive.'], 500);
            }
        }

        return redirect()->back();
    }

    protected function bulkGenerateReceipts(array $ids, string $mode, $school)
    {
        $receipts = [];
        foreach ($ids as $id) {
            $invoice = Invoice::with(['student', 'term.academicYear'])->find($id);
            if (! $invoice) {
                continue;
            }
            $payment = Payment::where('invoice_id', $invoice->id)->orderBy('id', 'desc')->first();
            if (! $payment) {
                continue;
            }
            $receipts[] = [
                'invoice' => $invoice,
                'payment' => $payment,
                'school' => $school,
                'config' => BillingDocumentSettingsService::get(),
                'template' => FinanceDocumentTemplate::resolveFor($school->id, 'receipt'),
            ];
        }

        if (empty($receipts)) {
            return redirect()->back()->with('error', 'No receipts to print (no payments found for selected invoices).');
        }

        if ($mode === 'combined') {
            $pdf = Pdf::loadView('modules.finance.receipt-bulk-pdf', [
                'school' => $school,
                'receipts' => $receipts,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('Bulk_Receipts.pdf');
        }

        if ($mode === 'zip') {
            $zip = new ZipArchive;
            $zipFileName = storage_path('app/public/Receipts_Archive_'.time().'.zip');

            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($receipts as $receipt) {
                    $pdf = Pdf::loadView('modules.finance.receipt-pdf', $receipt)->setPaper('a4', 'portrait');
                    $safeNum = str_replace(['/', '\\'], '_', $receipt['payment']->receipt_number);
                    $zip->addFromString('Receipt_'.$safeNum.'.pdf', $pdf->output());
                }
                $zip->close();

                return response()->download($zipFileName, 'Receipts_Archive.zip')->deleteFileAfterSend(true);
            }

            return response()->json(['error' => 'Failed to build ZIP archive.'], 500);
        }

        return redirect()->back();
    }

    protected function bulkGenerateStatements(array $ids, string $mode, $school)
    {
        $statements = [];
        foreach ($ids as $id) {
            $invoice = Invoice::find($id);
            if (! $invoice) {
                continue;
            }
            $student = Student::find($invoice->student_id);
            if (! $student) {
                continue;
            }

            $allInvoices = Invoice::where('student_id', $student->id)->orderBy('created_at', 'asc')->get();
            $payments = Payment::where('school_id', $school->id)
                ->whereIn('invoice_id', $allInvoices->pluck('id'))
                ->where('is_reversed', false)
                ->orderBy('created_at', 'asc')
                ->get();

            $ledger = [];
            $balance = 0;

            foreach ($allInvoices as $inv) {
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

            $statements[] = [
                'student' => $student,
                'school' => $school,
                'ledger' => $ledger,
                'current_balance' => $balance,
                'config' => BillingDocumentSettingsService::get(),
                'template' => FinanceDocumentTemplate::resolveFor($school->id, 'statement'),
                'verify_hash' => $allInvoices->last()?->integrity_hash,
            ];
        }

        if (empty($statements)) {
            return redirect()->back()->with('error', 'No statements to print.');
        }

        if ($mode === 'combined') {
            $pdf = Pdf::loadView('modules.finance.statement-bulk-pdf', [
                'school' => $school,
                'statements' => $statements,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('Bulk_Statements.pdf');
        }

        if ($mode === 'zip') {
            $zip = new ZipArchive;
            $zipFileName = storage_path('app/public/Statements_Archive_'.time().'.zip');

            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($statements as $statement) {
                    $pdf = Pdf::loadView('modules.finance.statement-pdf', $statement)->setPaper('a4', 'portrait');
                    $safeName = str_replace(['/', '\\'], '_', $statement['student']->admission_number);
                    $zip->addFromString('Statement_'.$safeName.'.pdf', $pdf->output());
                }
                $zip->close();

                return response()->download($zipFileName, 'Statements_Archive.zip')->deleteFileAfterSend(true);
            }

            return response()->json(['error' => 'Failed to build ZIP archive.'], 500);
        }

        return redirect()->back();
    }

    /**
     * Prints an Official Payment Receipt
     */
    public function printReceipt($id)
    {
        $invoice = Invoice::with(['student', 'term.academicYear'])->findOrFail($id);
        $payment = Payment::where('invoice_id', $invoice->id)->orderBy('id', 'desc')->firstOrFail();
        $school = app('current_tenant');

        $pdf = Pdf::loadView('modules.finance.receipt-pdf', [
            'invoice' => $invoice,
            'payment' => $payment,
            'school' => $school,
            'config' => BillingDocumentSettingsService::get(),
            'template' => FinanceDocumentTemplate::resolveFor($school->id, 'receipt'),
        ])->setPaper('a4', 'portrait');

        $safeReceiptNum = str_replace(['/', '\\'], '_', $payment->receipt_number);

        return $pdf->stream("Receipt_{$safeReceiptNum}.pdf");
    }

    /**
     * Prints a Chronological Statement of Account
     */
    public function printStatement($id)
    {
        $invoice = Invoice::findOrFail($id);
        $student = Student::findOrFail($invoice->student_id);
        $school = app('current_tenant');

        $invoices = Invoice::where('student_id', $student->id)->orderBy('created_at', 'asc')->get();
        $payments = Payment::where('school_id', $school->id)
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

        $pdf = Pdf::loadView('modules.finance.statement-pdf', [
            'student' => $student,
            'school' => $school,
            'ledger' => $ledger,
            'current_balance' => $balance,
            'config' => BillingDocumentSettingsService::get(),
            'template' => FinanceDocumentTemplate::resolveFor($school->id, 'statement'),
            'verify_hash' => $invoices->last()?->integrity_hash,
        ])->setPaper('a4', 'portrait');

        $safeAdmission = str_replace(['/', '\\'], '_', $student->admission_number);

        return $pdf->stream("Statement_{$safeAdmission}.pdf");
    }

    /**
     * Prints the Fee Structure sheet
     */
    public function printFeeStructure($termId)
    {
        $term = Term::with('academicYear')->findOrFail($termId);
        $school = app('current_tenant');

        $structures = FeeStructure::with(['feeCategory', 'course'])
            ->where('school_id', $school->id)
            ->where('term_id', $termId)
            ->get()
            ->groupBy('course.name');

        $pdf = Pdf::loadView('modules.finance.structure-pdf', [
            'term' => $term,
            'school' => $school,
            'structures' => $structures,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Fee_Structure_Term_{$termId}.pdf");
    }

    /**
     * Helper to compile single invoice data payload
     */
    protected function getInvoicePayload($id)
    {
        $invoice = Invoice::with(['student', 'term.academicYear', 'items'])->findOrFail($id);
        $school = app('current_tenant');
        $student = $invoice->student;

        $customConfig = BillingDocumentSettingsService::get();

        // Preserve any legacy school.settings['invoice_format'] overrides for backward compatibility
        $legacy = $school->settings['invoice_format'] ?? null;
        if (is_array($legacy)) {
            $customConfig = array_merge($customConfig, $legacy);
        }

        return [
            'invoice' => $invoice,
            'school' => $school,
            'student' => $student,
            'config' => $customConfig,
            'template' => FinanceDocumentTemplate::resolveFor($school->id, 'invoice'),
        ];
    }
}
