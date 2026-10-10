<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\SaaSInvoice;

class InvoiceDownloadController extends Controller
{
    public function download(Request $request, string $uuid)
    {
        $invoice = SaaSInvoice::where('uuid', $uuid)->firstOrFail();
        $this->authorizeInvoice($invoice);

        $pdf = Pdf::loadView('modules.saas.pdf.invoice', ['invoice' => $invoice]);

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    /**
     * Stream the invoice inline so a "View" button can open the real document
     * in the browser instead of only offering a download.
     */
    public function view(Request $request, string $uuid)
    {
        $invoice = SaaSInvoice::where('uuid', $uuid)->firstOrFail();
        $this->authorizeInvoice($invoice);

        $pdf = Pdf::loadView('modules.saas.pdf.invoice', ['invoice' => $invoice]);

        return $pdf->stream($invoice->invoice_number.'.pdf');
    }

    private function authorizeInvoice(SaaSInvoice $invoice): void
    {
        $user = Auth::user();

        // Security boundary validation
        if ($user->school_id !== null && $user->school_id !== $invoice->school_id) {
            abort(403, 'Unauthorized access to the requested invoice.');
        }

        // Integrity verification checks
        $computedHash = SaaSInvoice::calculateIntegrityHash($invoice);
        if ($invoice->integrity_hash !== $computedHash) {
            abort(400, 'Security Checksum Violation. Document may be modified.');
        }
    }
}
