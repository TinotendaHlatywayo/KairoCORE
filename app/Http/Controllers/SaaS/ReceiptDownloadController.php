<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\SaaSReceipt;

class ReceiptDownloadController extends Controller
{
    public function download(Request $request, string $uuid)
    {
        $receipt = SaaSReceipt::where('uuid', $uuid)->firstOrFail();
        $user = Auth::user();

        if ($user->school_id !== null && $user->school_id !== $receipt->school_id) {
            abort(403, 'Unauthorized access to the requested receipt.');
        }

        $pdf = Pdf::loadView('modules.saas.pdf.receipt', ['receipt' => $receipt]);

        return $pdf->download($receipt->receipt_number.'.pdf');
    }
}
