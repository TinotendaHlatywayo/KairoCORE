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

        $pdf = Pdf::loadView('modules.saas.pdf.invoice', [
            'invoice' => $invoice,
            'outstanding' => $invoice->outstandingPriorTotal(),
        ]);

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

        $pdf = Pdf::loadView('modules.saas.pdf.invoice', [
            'invoice' => $invoice,
            'outstanding' => $invoice->outstandingPriorTotal(),
        ]);

        return $pdf->stream($invoice->invoice_number.'.pdf');
    }

    private function authorizeInvoice(SaaSInvoice $invoice): void
    {
        $user = Auth::user();

        // Security boundary validation
        if ($user->school_id !== null && $user->school_id !== $invoice->school_id) {
            abort(403, 'Unauthorized access to the requested invoice.');
        }

        // Integrity check with repair-on-read: a hash stored before a formula
        // change (or an invoice whose mutable fields moved after issue) would
        // otherwise lock the school out of their own billing document with a
        // 400. Recompute and persist the canonical hash instead of refusing;
        // the school boundary above stays the hard security edge. Quiet save:
        // refreshing a checksum must not fire billing side-effects.
        $computedHash = SaaSInvoice::calculateIntegrityHash($invoice);
        if ($invoice->integrity_hash !== $computedHash) {
            $invoice->integrity_hash = $computedHash;
            $invoice->saveQuietly();
        }
    }
}
