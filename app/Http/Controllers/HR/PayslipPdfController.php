<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\HR\Models\Payslip;

class PayslipPdfController extends Controller
{
    /**
     * Compile and stream a secure A4 payslip document.
     */
    public function stream(Request $request, int $id)
    {
        try {
            // Fetch payslip enforcing active tenant scope resolution
            $payslip = Payslip::with(['employee.currentGrade', 'run.period', 'items'])
                ->findOrFail($id);

            // Convert institutional logo to safe inline base64 string to bypass DomPDF transparency constraints
            $logoBase64 = null;
            $logoPath = $payslip->employee->avatar_path;

            if ($logoPath && Storage::exists($logoPath)) {
                $fileContent = Storage::get($logoPath);
                $mimeType = Storage::mimeType($logoPath);
                $logoBase64 = 'data:'.$mimeType.';base64,'.base64_encode($fileContent);
            } else {
                // Inline default base64 transparent 1px PNG as placeholder
                $logoBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
            }

            // Compile DomPDF using Barryvdh wrapper
            $pdf = Pdf::loadView('modules.hr.payslip-pdf', [
                'payslip' => $payslip,
                'logo_base64' => $logoBase64,
            ]);

            // Set paper standard and clean boundaries
            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream("payslip-{$payslip->employee->employee_number}-{$payslip->run->period->name}.pdf");

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to compile payslip PDF: '.$e->getMessage(),
            ], 500);
        }
    }
}
