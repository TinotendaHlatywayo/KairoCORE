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
            // When the user explicitly chose the built-in default template,
            // fall back to the ship-with Premium Royal Gold (landscape) layout
            // instead of blocking the print.
            if ($request->query('use_default') === '1') {
                $defaultObj = self::defaultTemplate();
                $selectedTemplate = CardTemplate::firstOrCreate([
                    'school_id' => $schoolId,
                    'name' => 'Premium Royal Gold (Default)',
                ], [
                    'orientation' => $defaultObj->orientation,
                    'barcode_format' => $defaultObj->barcode_format,
                    'background_path' => $defaultObj->background_path,
                    'layout_config' => $defaultObj->layout_config,
                    'is_active' => true,
                ]);
                // Reusing a previously-created default must also make it the
                // active template, otherwise later print jobs would keep using
                // an unrelated stale template.
                if (! $selectedTemplate->is_active) {
                    $selectedTemplate->update(['is_active' => true]);
                }
                $arbitraryTemplate = $selectedTemplate;
            } else {
                // No active template found: present a friendly choice card so the
                // user can either open the ID Card Designer or print with the
                // built-in default template.
                return view('modules.students.id-card-no-template', [
                    'students' => $students,
                    'school' => app('current_tenant'),
                    'layout' => $layout,
                ]);
            }
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

        if (! $template) {
            $school = \Modules\Admin\Models\School::find($schoolId);
            return self::defaultTemplate($school?->name);
        }

        return $template;
    }

    /**
     * Built-in Professional School ID (landscape) default template used when a
     * school has no active template and the user chooses "Use Default Template".
     * Returned as a plain in-memory object so it never requires a DB row.
     *
     * Design language: Professional school ID card — diagonal header with logo
     * on left, school name on right, academic year strip, photo on left, info
     * grid on right, branded footer.
     *
     * @param  string|null  $schoolName  display name overridden on the card, if any
     */
    public static function defaultTemplate(?string $schoolName = null): object
    {
        return (object) [
            'orientation' => 'landscape',
            'barcode_format' => 'Code128',
            'background_path' => null,
            'layout_config' => [
                'design_theme' => 'professional',
                'show_school_header' => true,
                'show_card_type_label' => true,
                'show_school_motto' => true,
                'show_school_logo' => true,
                'show_contact_details' => true,
                'show_photo' => true,
                'show_name' => true,
                'show_class' => true,
                'show_student_id' => true,
                'show_admission_no' => false,
                'show_dob' => true,
                'show_address' => true,
                'show_student_phone' => true,
                'show_national_id' => true,
                'show_expiry' => true,
                'show_qr' => true,
                'show_barcode' => false,
                'show_photo_caption' => true,
                'photo_caption_text' => 'STUDENT',
                'primary_color' => '#1e3a8a',
                'primary_dark' => '#0f172a',
                'accent_color' => '#fbbf24',
                'text_primary' => '#0f172a',
                'text_secondary' => '#334155',
                'text_muted' => '#64748b',
                'footer_bg' => '#0f172a',
                'footer_text' => '#fbbf24',
                'custom_school_name' => $schoolName ?? '',
                'custom_school_motto' => '',
                'contact_address' => '',
                'contact_phone' => '',
                'contact_email' => '',
                'contact_website' => '',
                'logo_path' => '',
                'card_border_width' => 3,
                'card_border_color' => '#1e3a8a',
                'canvas_bg_color' => '#ffffff',
                'canvas_bg_watermark_opacity' => 100,
                'bg_mode' => 'solid',
                'school_name_font_family' => 'sans-serif',
                'school_name_font_size' => 18,
                'school_name_color' => '#fbbf24',
                'school_name_is_bold' => true,
                'motto_font_family' => 'sans-serif',
                'motto_font_size' => 10,
                'motto_color' => '#cbd5e1',
                'motto_is_italic' => true,
                'name_font_family' => 'sans-serif',
                'name_font_size' => 22,
                'name_color' => '#0f172a',
                'name_is_bold' => true,
                'label_font_family' => 'sans-serif',
                'label_font_size' => 11,
                'label_color' => '#64748b',
                'value_font_family' => 'sans-serif',
                'value_font_size' => 12,
                'value_color' => '#0f172a',
                'value_color_accent' => '#1e3a8a',
                'photo_border_color' => '#fbbf24',
                'photo_rounded_corners' => 8,
                'photo_border_width' => 2,
                'contact_font_size' => 8,
                'contact_color' => '#fbbf24',
                'contact_line_mode' => 'single',
                'qr_size' => 58,
                'strip_font_size' => 9,
            ],
        ];
    }

    public function downloadPngs(Request $request)
    {
        $schoolId = app('current_tenant')->id;
        $idsString = $request->query('ids');
        if (! $idsString) {
            return redirect()->back()->with('error', 'No students selected.');
        }

        $students = Student::where('school_id', $schoolId)
            ->whereIn('id', explode(',', $idsString))
            ->with('currentEnrollment.section.course')
            ->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'No students found.');
        }

        // Resolve template exactly the same way the PDF printer does, so the
        // exported PNG is the actual designed ID card.
        $template = null;
        if ($request->filled('template_id')) {
            $template = CardTemplate::where('school_id', $schoolId)
                ->where('id', $request->query('template_id'))
                ->first();
        }

        $arbitrary = $template ?? self::resolveTemplateForStudent($students->first(), $schoolId);
        if (! $arbitrary) {
            if ($request->query('use_default') !== '1') {
                return view('modules.students.id-card-no-template', [
                    'students' => $students,
                    'school' => app('current_tenant'),
                    'layout' => 'pvc',
                    'downloadPng' => true,
                ]);
            }

            $default = self::defaultTemplate();
            $template = CardTemplate::firstOrCreate([
                'school_id' => $schoolId,
                'name' => 'Premium Royal Gold (Default)',
            ], [
                'orientation' => $default->orientation,
                'barcode_format' => $default->barcode_format,
                'background_path' => $default->background_path,
                'layout_config' => $default->layout_config,
                'is_active' => true,
            ]);
            if (! $template->is_active) {
                $template->update(['is_active' => true]);
            }
            $arbitrary = $template;
        }

        $paper = ($arbitrary->orientation === 'landscape') ? [0, 0, 480, 300] : [0, 0, 300, 480];

        // Render the SAME blade used for printing (one CR80 card per page),
        // then rasterise it to PNG via poppler's pdftoppm. This guarantees the
        // PNG is pixel-for-pixel identical to the printed ID card.
        $pdf = Pdf::loadView('modules.students.id-card-bulk-pdf', [
            'students' => $students,
            'selectedTemplate' => $template,
            'school' => app('current_tenant'),
            'crop_marks' => false,
            'layout' => 'pvc',
        ])->setPaper($paper, 'portrait');

        $dir = storage_path('app/public/id-cards-temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stamp = now()->format('Ymd_His');
        $pdfPath = $dir.'/cards_'.$stamp.'.pdf';
        $pdf->save($pdfPath);

        $root = $dir.'/card_'.$stamp;
        $exitCode = 1;
        $output = [];
        exec('pdftoppm -png -r 192 '.escapeshellarg($pdfPath).' '.escapeshellarg($root).' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            @unlink($pdfPath);

            return redirect()->back()->with('error', 'PNG export failed. Please use the PDF print option instead.');
        }

        // Single student → download the PNG directly. Multiple → ZIP archive.
        if ($students->count() === 1) {
            $single = $root.'-1.png';
            @unlink($pdfPath);

            if (! file_exists($single)) {
                return redirect()->back()->with('error', 'PNG export failed. Please use the PDF print option instead.');
            }

            return response()->download($single)->deleteFileAfterSend(true);
        }

        $zipName = 'ID_Cards_'.$stamp.'.zip';
        $zipPath = storage_path('app/public/'.$zipName);
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $index = 1;
            foreach ($students as $student) {
                $png = $root.'-'.$index.'.png';
                $index++;

                if (file_exists($png)) {
                    $zip->addFile($png, 'ID_Card_'.($student->student_id_number ?? $student->id).'.png');
                }
            }
            $zip->close();
        }

        @unlink($pdfPath);
        foreach (glob($root.'-*.png') ?: [] as $png) {
            @unlink($png);
        }

        if (! file_exists($zipPath)) {
            return redirect()->back()->with('error', 'Failed to create the ID card archive.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
