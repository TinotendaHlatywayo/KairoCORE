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

    /**
     * Stream several vault archives to the browser as one zip (?ids=1,2,3).
     */
    public function downloadMany(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->school_id === null, 403);

        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));
        abort_if($ids === [], 400, 'No backups selected.');

        $backups = PlatformBackup::whereIn('id', $ids)->get()
            ->filter(fn (PlatformBackup $backup) => Storage::disk($backup->disk ?: 'local')->exists("backups/{$backup->filename}"));

        abort_if($backups->isEmpty(), 404, 'Backup archives not found.');

        // Single selection streams straight through — no pointless zip wrap.
        if ($backups->count() === 1) {
            $backup = $backups->first();

            return Storage::disk($backup->disk ?: 'local')->download("backups/{$backup->filename}", $backup->filename);
        }

        $tmpDir = storage_path('app/private/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir.'/kairo-backups-'.bin2hex(random_bytes(6)).'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($backups as $backup) {
            $zip->addFile(
                Storage::disk($backup->disk ?: 'local')->path("backups/{$backup->filename}"),
                $backup->filename
            );
        }

        $zip->close();

        return response()->download(
            $zipPath,
            'kairo-backups-'.now()->format('Ymd-His').'.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }
}
