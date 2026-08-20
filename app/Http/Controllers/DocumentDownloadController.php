<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ApplicationDocument;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentDocument;

class DocumentDownloadController extends Controller
{
    /**
     * Download a supporting document attached to a student record.
     */
    public function studentDocument(Student $student, StudentDocument $document)
    {
        $schoolId = app('current_tenant')?->id ?? auth()->user()?->school_id;

        abort_if($document->school_id !== $schoolId, 403, 'Unauthorized access to document.');

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._\-]/', '_', $document->original_name ?? ($document->document_type.'.pdf'));

        return Storage::disk('public')->download($document->file_path, $safeName);
    }

    /**
     * Download a supporting document attached to an online application.
     */
    public function applicationDocument(Application $application, ApplicationDocument $document)
    {
        $schoolId = app('current_tenant')?->id ?? auth()->user()?->school_id;

        abort_if($document->school_id !== $schoolId, 403, 'Unauthorized access to document.');

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._\-]/', '_', $document->original_name ?? ($document->document_type.'.pdf'));

        return Storage::disk('public')->download($document->file_path, $safeName);
    }

    /**
     * View a supporting document attached to an online application in-browser.
     */
    public function viewApplicationDocument(Application $application, ApplicationDocument $document)
    {
        $schoolId = app('current_tenant')?->id ?? auth()->user()?->school_id;

        abort_if($document->school_id !== $schoolId, 403, 'Unauthorized access to document.');

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->response($document->file_path);
    }
}
