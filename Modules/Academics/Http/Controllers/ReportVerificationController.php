<?php

namespace Modules\Academics\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academics\Models\AcademicReport;

class ReportVerificationController extends Controller
{
    public function verify($hash)
    {
        // Decode and locate the report by its unique anti-fraud hash
        $report = AcademicReport::with(['student', 'term.academicYear', 'section.course'])
            ->where('integrity_hash', $hash)
            ->first();

        if (! $report) {
            abort(404, 'Invalid Report Card Verification Code.');
        }

        return view('modules.academics.verify-report', [
            'report' => $report,
            'student' => $report->student,
            'school' => $report->student->school,
            'term' => $report->term,
            'year' => $report->term->academicYear,
        ]);
    }
}
