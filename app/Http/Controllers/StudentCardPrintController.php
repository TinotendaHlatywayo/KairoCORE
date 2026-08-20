<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Students\Models\CardPrintHistory;
use Modules\Students\Models\CardTemplate;
use Modules\Students\Models\Student;

class StudentCardPrintController extends Controller
{
    public function generate(Request $request)
    {
        $schoolId = app('current_tenant')->id;
        $scope = $request->query('scope', 'selected');
        $layout = $request->query('layout', 'pvc');
        $cropMarks = $request->query('crop_marks', false);
        $idsString = $request->query('ids');

        $selectedTemplate = null;
        if ($request->filled('template_id')) {
            $selectedTemplate = CardTemplate::where('school_id', $schoolId)
                ->where('id', $request->query('template_id'))
                ->first();
        }

        $query = Student::where('school_id', $schoolId)->where('status', 'active');

        if ($scope === 'selected' && $idsString) {
            $query->whereIn('id', explode(',', $idsString));
        } elseif ($scope === 'class' && $request->filled('section_id')) {
            $query->whereHas('enrollments', fn ($q) => $q->where('section_id', $request->query('section_id')));
        } elseif ($scope === 'grade' && $request->filled('course_id')) {
            $query->whereHas('enrollments', fn ($q) => $q->where('course_id', $request->query('course_id')));
        } elseif ($scope === 'new') {
            $query->whereDoesntHave('enrollments', function ($q) {
                $q->whereIn('student_id', CardPrintHistory::pluck('student_id')->toArray());
            });
        } elseif ($scope === 'expired') {
            $query->where('card_expiry_date', '<', now()->toDateString());
        } elseif ($scope === 'no_card') {
            $query->where('card_status', 'pending_issuance');
        }

        $students = $query->with('currentEnrollment.section.course')->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'No student records matched the printing filters.');
        }

        // Validate that an active template exists before writing logs
        $arbitraryTemplate = $selectedTemplate ?? self::resolveTemplateForStudent($students->first(), $schoolId);

        if (! $arbitraryTemplate) {
            return redirect()->back()->with('error', 'No Active ID Card Template found. Please design and activate an ID Card template first.');
        }

        // Log Print Audit Trails
        foreach ($students as $student) {
            $serial = null;
            $nextCount = CardPrintHistory::where('school_id', $schoolId)->count() + 1;

            do {
                $serial = 'SR-'.str_pad($nextCount, 6, '0', STR_PAD_LEFT);
                $nextCount++;
            } while (CardPrintHistory::where('school_id', $schoolId)->where('serial_number', $serial)->exists());

            $resolvedTemplate = $selectedTemplate ?? self::resolveTemplateForStudent($student, $schoolId);

            CardPrintHistory::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'card_template_id' => $resolvedTemplate?->id ?? 0,
                'serial_number' => $serial,
                'verification_code' => hash_hmac('sha256', $student->student_id_number, config('app.key')),
                'printed_by_id' => Auth::id(),
                'printed_at' => now(),
                'printer_type' => $layout,
            ]);

            $student->update(['card_status' => 'active']);
        }

        $paperOrientation = ($arbitraryTemplate && $arbitraryTemplate->orientation === 'landscape') ? 'landscape' : 'portrait';

        if ($layout === 'a4') {
            $pdf = Pdf::loadView('modules.students.id-card-bulk-pdf', [
                'students' => $students,
                'selectedTemplate' => $selectedTemplate,
                'school' => app('current_tenant'),
                'crop_marks' => $cropMarks,
                'layout' => $layout,
            ])->setPaper('a4', $paperOrientation);

            return $pdf->stream('Bulk_ID_Cards_A4.pdf');
        }

        if ($arbitraryTemplate && $arbitraryTemplate->orientation === 'landscape') {
            $paperSize = [0, 0, 480, 300];
        } else {
            $paperSize = [0, 0, 300, 480];
        }

        $pdf = Pdf::loadView('modules.students.id-card-bulk-pdf', [
            'students' => $students,
            'selectedTemplate' => $selectedTemplate,
            'school' => app('current_tenant'),
            'crop_marks' => $cropMarks,
            'layout' => $layout,
        ])->setPaper($paperSize, 'portrait');

        return $pdf->stream('Bulk_ID_Cards_PVC.pdf');
    }

    public static function resolveTemplateForStudent($student, $schoolId)
    {
        $enrollment = $student->currentEnrollment ?? $student->enrollments()->latest()->first();
        $levelName = $enrollment?->course?->name ?? '';

        $studentGroup = 'all';
        if ($levelName === 'ECD A' || $levelName === 'ECD B') {
            $studentGroup = 'ecd';
        } elseif (preg_match('/Grade\s*[1-7]/i', $levelName)) {
            $studentGroup = 'primary';
        } elseif (preg_match('/Form\s*[1-4]/i', $levelName)) {
            $studentGroup = 'secondary';
        } elseif ($levelName === 'Lower Six' || $levelName === 'Upper Six') {
            $studentGroup = 'alevel';
        }

        $template = CardTemplate::where('school_id', $schoolId)
            ->where('is_active', true)
            ->get()
            ->first(function ($t) use ($studentGroup) {
                return ($t->layout_config['target_group'] ?? 'all') === $studentGroup;
            });

        if (! $template) {
            $template = CardTemplate::where('school_id', $schoolId)
                ->where('is_active', true)
                ->get()
                ->first(function ($t) {
                    return ($t->layout_config['target_group'] ?? 'all') === 'all';
                });
        }

        if (! $template) {
            $template = CardTemplate::where('school_id', $schoolId)
                ->where('is_active', true)
                ->latest('updated_at')
                ->first();
        }

        return $template;
    }
}
