<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Models\PlatformBackup;

/**
 * Streams a platform recovery archive to the super admin's browser.
 *
 * Downloads run through a normal route (not a Livewire action) because a
 * Livewire action cannot return a binary file response — it can only return
 * serialisable data, so the old in-action download 500'd.
 */
class PlatformBackupDownloadController extends Controller
{
    public function download(Request $request, int $backup)
    {
        abort_unless(Auth::check() && Auth::user()->school_id === null, 403);

        $record = PlatformBackup::findOrFail($backup);

        $path = "backups/{$record->filename}";
        $disk = $record->disk ?: 'local';

        abort_unless(Storage::disk($disk)->exists($path), 404, 'Backup archive not found.');

        return Storage::disk($disk)->download($path, $record->filename);
    }
}