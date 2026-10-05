<?php

namespace App\Http\Controllers;

use App\Filament\App\Resources\CardTemplateResource;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        $students = $query->with(['currentEnrollment.section.course', 'user', 'application'])->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'No student records matched the printing filters.');
        }

        // Resolve template — returns NULL when no admin-created template exists
        $arbitraryTemplate = $selectedTemplate ?? self::resolveTemplateForStudent($students->first(), $schoolId);

        if (! $arbitraryTemplate) {
            if ($request->query('use_default') === '1') {
                $selectedTemplate = self::persistedDefaultTemplate($schoolId);
                $arbitraryTemplate = $selectedTemplate;
            } else {
                return view('modules.students.id-card-no-template', [
                    'students' => $students,
                    'school' => app('current_tenant'),
                    'layout' => $layout,
                ]);
            }
        }

        // For rendering, use the built-in default template object if no admin template exists
        // This ensures the correct Classic Academic layout is used instead of falling back to legacy premium
        $renderTemplate = $arbitraryTemplate ?? self::defaultTemplate($school?->name);

        // Log Print Audit Trails
        foreach ($students as $student) {
            $serial = null;
            // Serial numbers are unique across the whole platform (the DB index
            // is global, not per-school), so count/existence checks must ignore
            // the tenant scope to avoid colliding with another school's cards.
            $nextCount = CardPrintHistory::withoutTenantScope()->count() + 1;

            do {
                $serial = 'SR-'.str_pad($nextCount, 6, '0', STR_PAD_LEFT);
                $nextCount++;
            } while (CardPrintHistory::withoutTenantScope()->where('serial_number', $serial)->exists());

            $resolvedTemplate = $selectedTemplate ?? self::resolveTemplateForStudent($student, $schoolId);
            $templateId = $resolvedTemplate->id ?? $selectedTemplate->id
                ?? self::persistedDefaultTemplate($schoolId)->id;

            CardPrintHistory::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'card_template_id' => $templateId,
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
                'selectedTemplate' => $renderTemplate,
                'school' => app('current_tenant'),
                'crop_marks' => $cropMarks,
                'layout' => $layout,
            ])->setPaper('a4', $paperOrientation);

            return $pdf->stream('Bulk_ID_Cards_A4.pdf');
        }

        if ($renderTemplate && $renderTemplate->orientation === 'landscape') {
            $paperSize = [0, 0, 480, 300];
        } else {
            $paperSize = [0, 0, 300, 480];
        }

        $pdf = Pdf::loadView('modules.students.id-card-bulk-pdf', [
            'students' => $students,
            'selectedTemplate' => $renderTemplate,
            'school' => app('current_tenant'),
            'crop_marks' => $cropMarks,
            'layout' => $layout,
        ])->setPaper($paperSize, $paperOrientation);

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
            ->where('is_system_default', false)
            ->get()
            ->first(function ($t) use ($studentGroup) {
                return ($t->layout_config['target_group'] ?? 'all') === $studentGroup;
            });

        if (! $template) {
            $template = CardTemplate::where('school_id', $schoolId)
                ->where('is_active', true)
                ->where('is_system_default', false)
                ->get()
                ->first(function ($t) {
                    return ($t->layout_config['target_group'] ?? 'all') === 'all';
                });
        }

        if (! $template) {
            $template = CardTemplate::where('school_id', $schoolId)
                ->where('is_active', true)
                ->where('is_system_default', false)
                ->latest('updated_at')
                ->first();
        }

        if (! $template) {
            // A school that answered "Use Default Template" once has a persisted
            // system-default row (see persistedDefaultTemplate()). Fall back to it
            // so exports stop asking for a template it already created — without
            // this, every later export re-showed "No Active ID Card Template".
            $template = CardTemplate::where('school_id', $schoolId)
                ->where('is_active', true)
                ->where('is_system_default', true)
                ->latest('updated_at')
                ->first();
        }

        if (! $template) {
            return null;
        }

        return $template;
    }

    /**
     * Built-in Classic Academic (Double Border) — landscape default template
     * used when a school has no active template and the user chooses
     * "Use Default Template". Returned as a plain in-memory object so it never
     * requires a DB row.
     *
     * Design language: serif academic card — strong outer border with an inner
     * frame line, crest header with school name/motto/card-type label, photo +
     * identity grid, QR anchored bottom-right and a full-width contact footer.
     * The layout_config is derived from the same theme defaults the designer
     * uses, so the built-in default always matches the Classic Academic theme.
     *
     * @param  string|null  $schoolName  display name overridden on the card, if any
     */
    public static function defaultTemplate(?string $schoolName = null): object
    {
        $classicDefaults = [];
        foreach (CardTemplateResource::getThemeDefaults('classic') as $key => $value) {
            if (str_starts_with($key, 'layout_config.')) {
                $classicDefaults[substr($key, strlen('layout_config.'))] = $value;
            }
        }

        return (object) [
            'orientation' => 'landscape',
            'barcode_format' => 'Code128',
            'background_path' => null,
            'layout_config' => array_merge($classicDefaults, [
                'design_theme' => 'classic',
                'show_card_type_label' => true,
                'custom_school_name' => $schoolName ?? '',
                'custom_school_motto' => '',
                'contact_address' => '',
                'contact_phone' => '',
                'contact_email' => '',
                'contact_website' => '',
                'logo_path' => '',
            ]),
        ];
    }

    /**
     * Persist (or reuse) a card_templates row for the built-in default layout
     * so print jobs can reference a real template id in the audit trail and so
     * the default becomes the active template going forward.
     */
    protected static function persistedDefaultTemplate(int $schoolId): CardTemplate
    {
        $default = self::defaultTemplate();

        $template = CardTemplate::firstOrCreate([
            'school_id' => $schoolId,
            'name' => 'Classic Academic (Default)',
        ], [
            'orientation' => $default->orientation,
            'barcode_format' => $default->barcode_format,
            'background_path' => $default->background_path,
            'layout_config' => $default->layout_config,
            'is_active' => true,
            'is_system_default' => true,
        ]);

        // A previously-created built-in default row is kept in sync with the
        // ship-with Classic Academic theme and re-activated, so later print
        // jobs (and the audit trail) always resolve to the current default.
        if (($template->layout_config['design_theme'] ?? null) !== $default->layout_config['design_theme']) {
            $template->update(['layout_config' => $default->layout_config]);
        }
        if (! $template->is_active) {
            $template->update(['is_active' => true]);
        }
        // Ensure the built-in default is always marked as system default
        if (! $template->is_system_default) {
            $template->update(['is_system_default' => true]);
        }
        // Force set is_system_default to true regardless (handles edge cases)
        $template->is_system_default = true;
        $template->save();

        return $template;
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
            ->with(['currentEnrollment.section.course', 'user', 'application'])
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

            $template = self::persistedDefaultTemplate($schoolId);
            $arbitrary = $template;
        }

        // The template actually used for the export. Handing the view $template
        // left it null whenever the template was auto-resolved (the bulk action
        // never sends a template_id), so the PNG silently fell back to the
        // built-in design instead of the school's own card.
        $renderTemplate = $arbitrary ?? self::defaultTemplate(app('current_tenant')->name);

        $paper = (($renderTemplate->orientation ?? 'portrait') === 'landscape') ? [0, 0, 480, 300] : [0, 0, 300, 480];

        // Render the SAME blade used for printing (one CR80 card per page),
        // then rasterise it to PNG. This guarantees the PNG is pixel-for-pixel
        // identical to the printed ID card.
        $pdf = Pdf::loadView('modules.students.id-card-bulk-pdf', [
            'students' => $students,
            'selectedTemplate' => $renderTemplate,
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

        $rasteriser = self::resolveRasteriser();

        // A host with no PDF rasteriser installed cannot produce PNG at all.
        // Hand back the printable cards (with a filename that says what happened)
        // instead of bouncing to the student list, where the reason was invisible.
        if (! $rasteriser) {
            Log::warning('ID card PNG export: no PDF rasteriser on this server (tried: '.implode(', ', self::$rasteriserCandidates).'); returning the printable PDF instead.', [
                'school_id' => $schoolId,
                'students' => $students->count(),
            ]);

            return response()->download($pdfPath, 'ID_Cards_'.$stamp.'_PNG-unavailable.pdf')->deleteFileAfterSend(true);
        }

        $root = $dir.'/card_'.$stamp;
        $rasterised = self::rasterisePdf($pdfPath, $root, $rasteriser);

        if ($rasterised['files'] === []) {
            Log::error('ID card PNG export could not rasterise the rendered cards.', [
                'school_id' => $schoolId,
                'rasteriser' => $rasteriser['command'],
                'exit_code' => $rasterised['exit_code'],
                'output' => $rasterised['output'],
            ]);

            @unlink($pdfPath);

            return redirect()->back()->with(
                'error',
                'PNG export failed on this server ('.$rasteriser['command'].' exited '.$rasterised['exit_code'].'). Please use the PDF print option instead.'
            );
        }

        // Single student → download the PNG directly with a clean filename. Multiple → ZIP archive.
        if ($students->count() === 1) {
            @unlink($pdfPath);
            $student = $students->first();
            $pngName = 'ID_Card_'.($student->student_id_number ?? $student->id).'.png';

            return response()->download($rasterised['files'][0], $pngName)->deleteFileAfterSend(true);
        }

        $zipName = 'ID_Cards_'.$stamp.'.zip';
        $zipPath = storage_path('app/public/'.$zipName);
        $zip = new \ZipArchive;
        $added = 0;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($students->values() as $index => $student) {
                $png = $rasterised['files'][$index] ?? null;

                if ($png && file_exists($png)) {
                    $zip->addFile($png, 'ID_Card_'.($student->student_id_number ?? $student->id).'.png');
                    $added++;
                }
            }
            $zip->close();
        }

        @unlink($pdfPath);
        foreach ($rasterised['files'] as $png) {
            @unlink($png);
        }

        if ($added === 0 || ! file_exists($zipPath)) {
            @unlink($zipPath);

            return redirect()->back()->with('error', 'Failed to create the ID card archive.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * PDF rasterisers this export can use, in order of preference; the first one
     * actually installed on the server is used.
     *
     * pdftoppm (poppler-utils) is the fast path. Ghostscript is the fallback so
     * a host that ships gs but not poppler can still export PNGs. Public so a
     * test can exercise each path in isolation.
     *
     * @var array<int, string>
     */
    public static array $rasteriserCandidates = ['pdftoppm', 'gs'];

    /**
     * First installed rasteriser, or null when the server has none (or exec is
     * disabled, which is how the export used to fail without saying so).
     *
     * @return array{command: string, path: string}|null
     */
    protected static function resolveRasteriser(): ?array
    {
        if (! function_exists('exec')) {
            return null;
        }

        foreach (self::$rasteriserCandidates as $command) {
            $path = trim((string) @exec('command -v '.escapeshellarg($command).' 2>/dev/null'));

            if ($path !== '') {
                return ['command' => $command, 'path' => $path];
            }
        }

        return null;
    }

    /**
     * Rasterise a one-card-per-page PDF into one PNG per page, in page order.
     *
     * @param  array{command: string, path: string}  $rasteriser
     * @return array{files: array<int, string>, exit_code: int, output: array<int, string>}
     */
    protected static function rasterisePdf(string $pdfPath, string $root, array $rasteriser): array
    {
        $exitCode = 1;
        $output = [];

        if ($rasteriser['command'] === 'gs') {
            exec(
                $rasteriser['path'].' -q -dNOPAUSE -dBATCH -sDEVICE=png16m -r192 -sOutputFile='
                    .escapeshellarg($root.'-%03d.png').' '.escapeshellarg($pdfPath).' 2>&1',
                $output,
                $exitCode
            );
        } else {
            exec(
                $rasteriser['path'].' -png -r 192 '.escapeshellarg($pdfPath).' '.escapeshellarg($root).' 2>&1',
                $output,
                $exitCode
            );
        }

        $files = glob($root.'-*.png') ?: [];
        natsort($files);

        return ['files' => array_values($files), 'exit_code' => $exitCode, 'output' => $output];
    }
}
