<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Exceptions\ChunkUploadException;
use Modules\Recovery\Services\ChunkedArchiveUploadService;
use Modules\Recovery\Services\PlatformBackupService;

/**
 * Receives one 10 MB piece of a recovery archive and appends it to the
 * part file for that upload.
 *
 * The browser posts pieces sequentially instead of the whole .zip at once:
 * a single large body dies behind Cloudflare's ~100 MB request cap and on
 * slow links, while 10 MB pieces sail through. The final piece is verified
 * (size + ZIP) and moved into the vault directory, then recorded as a vault
 * row so it appears in the recovery vault without any further round-trip.
 */
class PlatformBackupChunkController extends Controller
{
    public function append(
        Request $request,
        ChunkedArchiveUploadService $uploads,
        PlatformBackupService $vault,
    ): JsonResponse {
        // Same guard as the archive download route: central/super admin only.
        abort_unless(Auth::check() && Auth::user()->school_id === null, 403);

        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'index' => ['required', 'integer', 'min:0', 'max:999'],
            'total' => ['required', 'integer', 'min:1', 'max:1000'],
            'total_size' => ['required', 'integer', 'min:1'],
            'filename' => ['required', 'string', 'max:180'],
            'chunk_sha256' => ['nullable', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
            'chunk' => ['required', 'file'],
        ]);

        try {
            $result = $uploads->append(
                uploadId: $data['upload_id'],
                index: (int) $data['index'],
                total: (int) $data['total'],
                totalSize: (int) $data['total_size'],
                fileName: $data['filename'],
                sourcePath: (string) $request->file('chunk')->getRealPath(),
                chunkSha256: $data['chunk_sha256'] ?? null,
            );
        } catch (ChunkUploadException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'retryable' => $e->retryable,
            ], $e->status);
        }

        if (! ($result['done'] ?? false)) {
            return response()->json($result);
        }

        // Last piece: the file is in the vault directory — record it so it
        // shows up in the vault list. importArchive() is idempotent, so a
        // retried confirmation cannot create a second row for the same file.
        try {
            $backup = $vault->importArchive(
                Storage::disk('local')->path('backups/'.$result['filename']),
                $result['filename'],
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'The archive was assembled but could not be recorded in the vault: '.$e->getMessage(),
                'retryable' => true,
            ], 422);
        }

        return response()->json($result + ['id' => $backup->id]);
    }
}
