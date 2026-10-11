<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSInvoiceItem;
use Modules\SaaS\Models\SaaSReceipt;

class DocumentPreviewController extends Controller
{
    /**
     * Stream a sample of one of the platform documents so a super admin can
     * verify how the editable branding renders before publishing changes.
     */
    public function show(string $type)
    {
        $type = strtolower($type);

        abort_unless(in_array($type, ['invoice', 'receipt', 'statement'], true), 404);

        [$view, $data] = match ($type) {
            'invoice' => ['modules.saas.pdf.invoice', $this->invoiceSample()],
            'receipt' => ['modules.saas.pdf.receipt', $this->receiptSample()],
            'statement' => ['modules.saas.pdf.tenant-billing-statement', $this->statementSample()],
        };

        $pdf = Pdf::loadView($view, $data);

        return $pdf->stream('sample-'.$type.'.pdf');
    }

    private function school(): object
    {
        return (object) [
            'name' => __('Sample Academy'),
            'physical_address' => __('123 Sample Street, Harare'),
            'phone_number' => '+263 77 000 0000',
            'email_address' => 'accounts@example.com',
        ];
    }

    private function invoiceSample(): array
    {
        $invoice = new SaaSInvoice;
        $invoice->invoice_number = 'INV-SAMPLE-00001';
        $invoice->issue_date = now();
        $invoice->due_date = now()->addDays(7);
        $invoice->subtotal = 120.00;
        $invoice->discount = 20.00;
        $invoice->total = 100.00;
        $invoice->currency = 'USD';
        $invoice->payment_instructions = null;
        $invoice->integrity_hash = 'sample-integrity-hash';
        $invoice->setRelation('school', $this->school());
        $invoice->setRelation('subscription', (object) ['plan' => (object) ['name' => __('Standard Kairo CORE License')]]);

        $item = new SaaSInvoiceItem;
        $item->description = __('Subscription for Standard Kairo CORE License [Monthly billing cycle]');
        $item->quantity = 1;
        $item->unit_price = 120.00;
        $item->total = 120.00;

        $invoice->setRelation('items', collect([$item]));

        return ['invoice' => $invoice, 'outstanding' => 120.00];
    }

    private function receiptSample(): array
    {
        $receipt = new SaaSReceipt;
        $receipt->receipt_number = 'RCP-SAMPLE-00001';
        $receipt->amount_paid = 100.00;
        $receipt->currency = 'USD';
        $receipt->issued_at = now();
        $receipt->integrity_hash = 'sample-integrity-hash';
        $receipt->setRelation('school', $this->school());
        $receipt->setRelation('invoice', (object) ['invoice_number' => 'INV-SAMPLE-00001']);
        $receipt->setRelation('transaction', (object) ['transaction_reference' => 'PAYNOW-SAMPLE-0001']);

        return ['receipt' => $receipt];
    }

    private function statementSample(): array
    {
        $start = now()->startOfMonth();
        $end = now();

        $invoices = collect([
            (object) [
                'invoice_number' => 'INV-SAMPLE-00001',
                'issue_date' => $start,
                'due_date' => $start->copy()->addDays(7),
                'status' => 'paid',
                'total' => 120.00,
            ],
            (object) [
                'invoice_number' => 'INV-SAMPLE-00002',
                'issue_date' => $end->copy()->subDays(2),
                'due_date' => $end->copy()->addDays(5),
                'status' => 'unpaid',
                'total' => 120.00,
            ],
        ]);

        $receipts = collect([
            (object) [
                'receipt_number' => 'RCP-SAMPLE-00001',
                'invoice' => (object) ['invoice_number' => 'INV-SAMPLE-00001'],
                'issued_at' => $end->copy()->subDays(3),
                'amount_paid' => 120.00,
            ],
        ]);

        $payments = collect([
            (object) [
                'transaction_reference' => 'PAYNOW-SAMPLE-0001',
                'uuid' => 'sample-uuid-0001',
                'payment_gateway_key' => 'paynow',
                'processed_at' => $end->copy()->subDays(2),
                'amount' => 120.00,
            ],
        ]);

        return ['statement' => [
            'currency' => 'USD',
            'school' => $this->school(),
            'start' => $start,
            'end' => $end,
            'invoiced_total' => 240.00,
            'receipts_total' => 120.00,
            'payments_total' => 120.00,
            'outstanding_total' => 120.00,
            'invoices' => $invoices,
            'receipts' => $receipts,
            'payments' => $payments,
        ]];
    }
}
