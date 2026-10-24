<?php

namespace Modules\Recovery\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Exceptions\ChunkUploadException;
use ZipArchive;

/**
 * Assembles a recovery archive from sequential browser-sized pieces.
 *
 * One big POST dies on the way through any proxy that caps request bodies
 * (Cloudflare refuses ~100 MB on the standard plans) and on slow links, so
 * the browser slices the .zip and sends 10 MB pieces instead. Each piece is
 * appended to a part file under storage/app/private/chunked-uploads — the
 * same disk as the vault, so a finished archive is put in place with a single
 * atomic rename — and every piece's SHA-256 is checked before it is written.
 *
 * When the last piece lands the total size is verified, the bytes are opened
 * as a ZIP to prove nothing was mangled in transit, and the file is moved
 * into the vault directory where PlatformBackupService::importArchive()
 * records it.
 */
class ChunkedArchiveUploadService
{
    /** Piece size the browser sends — one request stays far below any CDN cap. */
    public const CHUNK_BYTES = 10485760; // 10 MB

    /** Largest archive accepted through the browser: 500 MB (512000 KB). */
    public const MAX_BYTES = 524288000;

    /** Abandoned part files, locks and markers are swept after six hours. */
    public const STALE_SECONDS = 21600;

    /**
     * Append one piece of an archive.
     *
     * @param  string  $uploadId  browser-generated reference for the whole transfer
     * @param  int  $index  0-based piece number
     * @param  int  $total  how many pieces the browser split the file into
     * @param  int  $totalSize  size of the whole file, in bytes
     * @param  string  $fileName  original archive name (stored in the vault)
     * @param  string  $sourcePath  absolute path of the received piece
     * @param  string|null  $chunkSha256  SHA-256 of the piece, when the browser can compute one
     * @return array{done: bool, received: int, total?: int, filename?: string, size?: int}
     *
     * @throws ChunkUploadException
     */
    public function append(
        string $uploadId,
        int $index,
        int $total,
        int $totalSize,
        string $fileName,
        string $sourcePath,
        ?string $chunkSha256 = null,
    ): array {
        $this->purgeStale();

        if (preg_match('/^[A-Za-z0-9_-]{8,64}$/', $uploadId) !== 1) {
            $this->fail('That upload reference is not valid — reload the page and try again.');
        }

        // Checked before basename() so a name carrying directories is refused
        // outright rather than quietly flattened into something else.
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._()\-]{0,140}\.[zZ][iI][pP]$/', $fileName) !== 1) {
            $this->fail('Archive names may only use letters, numbers, spaces, dots, dashes and underscores, and must end in .zip.');
        }

        $fileName = basename($fileName);

        $chunkBytes = $this->chunkBytes();
        $maxBytes = $this->maxBytes();
        $maxChunks = (int) ceil($maxBytes / $chunkBytes);

        if ($total < 1 || $total > $maxChunks) {
            $this->fail("An archive this size must be sent in at most {$maxChunks} pieces — reload the page and upload again.");
        }
        if ($index < 0 || $index >= $total) {
            $this->fail('That piece number does not belong to this upload.');
        }
        if ($totalSize < 1 || $totalSize > $maxBytes) {
            $this->fail("Archives up to {$this->maxMegabytes()} MB can be uploaded through the browser — this one is larger.");
        }

        $isLast = $index === $total - 1;
        $expectedOffset = $index * $chunkBytes;

        clearstatcache(true, $sourcePath);
        $chunkLen = is_file($sourcePath) ? (int) filesize($sourcePath) : 0;

        if ($chunkLen < 1) {
            $this->fail('The piece arrived empty — send it again.', true);
        }
        if ($expectedOffset + $chunkLen > $totalSize) {
            $this->fail('Piece '.($index + 1).' overruns the file size the browser reported ('.$totalSize.' bytes).');
        }
        if (! $isLast && $chunkLen !== $chunkBytes) {
            $this->fail('Piece '.($index + 1)." should be {$chunkBytes} bytes but arrived as {$chunkLen}.");
        }
        if ($isLast && $chunkLen !== $totalSize - $expectedOffset) {
            $this->fail("The final piece is {$chunkLen} bytes but ".($totalSize - $expectedOffset).' were expected.');
        }

        if ($chunkSha256 !== null) {
            $actual = hash_file('sha256', $sourcePath);
            if (! hash_equals(strtolower($chunkSha256), strtolower((string) $actual))) {
                $this->fail('Piece '.($index + 1).' arrived corrupted (checksum mismatch) — send it again.', true);
            }
        }

        $directory = $this->ensureDirectory($this->directory());
        $marker = "{$directory}/{$uploadId}.done";

        // A completed upload whose confirmation never reached the browser:
        // answer from the marker instead of trying to re-append finished bytes.
        if (is_file($marker)) {
            $done = json_decode((string) file_get_contents($marker), true) ?: [];

            if (isset($done['filename'], $done['size']) && (int) $done['size'] === $totalSize) {
                return [
                    'done' => true,
                    'filename' => (string) $done['filename'],
                    'size' => $totalSize,
                    'received' => $totalSize,
                ];
            }

            $this->fail('That upload reference has already been used — reload the page and upload the file again.');
        }

        $part = "{$directory}/{$uploadId}.part";
        $lock = @fopen("{$directory}/{$uploadId}.lock", 'c');

        if ($lock === false) {
            $this->fail('The server could not open the upload session — send the piece again.', true);
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                $this->fail('Another request is writing this piece — send it again.', true);
            }

            $current = is_file($part) ? (int) filesize($part) : 0;
            $expectedEnd = $expectedOffset + $chunkLen;

            if ($current === $expectedEnd && $current !== $expectedOffset) {
                // Already stored: the reply to an earlier attempt of this same
                // piece was lost in transit, so the repeat is a harmless no-op.
                $stored = $current;
            } elseif ($current === $expectedOffset) {
                $stored = $this->appendBytes($part, $sourcePath, $expectedOffset, $expectedEnd, $index);
            } elseif ($current > $expectedOffset) {
                $this->fail('The server already holds more data than piece '.($index + 1).' expects — this upload is out of order. Reload the page and upload again.');
            } else {
                $this->fail("The server is missing an earlier piece (it holds {$current} bytes, piece ".($index + 1)." starts at {$expectedOffset}) — reload the page and upload again.");
            }

            if (! $isLast) {
                return ['done' => false, 'received' => $stored, 'total' => $totalSize];
            }

            if ($stored !== $totalSize) {
                @unlink($part);
                $this->fail("The assembled archive is {$stored} bytes but {$totalSize} were expected — reload the page and upload again.");
            }

            $this->assertReadableZip($part, $uploadId);

            $target = $this->reserveTarget($fileName);
            if (! @rename($part, $target) && ! @copy($part, $target)) {
                $this->fail('The server could not move the assembled archive into the recovery vault — check free disk space and send it again.', true);
            }
            if (is_file($part)) {
                @unlink($part);
            }

            $finalName = basename($target);
            File::put($marker, json_encode([
                'upload_id' => $uploadId,
                'filename' => $finalName,
                'size' => $totalSize,
            ]));

            return ['done' => true, 'filename' => $finalName, 'size' => $totalSize, 'received' => $totalSize];
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }

    /**
     * Stream a piece onto the part file and prove every byte landed.
     *
     * @return int bytes now stored (always $expectedEnd when this returns)
     *
     * @throws ChunkUploadException
     */
    private function appendBytes(string $part, string $sourcePath, int $expectedOffset, int $expectedEnd, int $index): int
    {
        $in = @fopen($sourcePath, 'rb');
        if ($in === false) {
            $this->fail('The uploaded piece could not be read — send it again.', true);
        }

        $out = @fopen($part, 'c+b');
        if ($out === false) {
            fclose($in);
            $this->fail('The server could not open the upload file — check free disk space and send the piece again.', true);
        }

        fseek($out, 0, SEEK_END);
        $copied = @stream_copy_to_stream($in, $out);
        fflush($out);
        $stored = (int) (fstat($out)['size'] ?? 0);

        if ($copied === false || $stored !== $expectedEnd) {
            // Put the part file back to the last known-good boundary so the
            // browser can safely resend this piece.
            ftruncate($out, $expectedOffset);
            fclose($out);
            fclose($in);

            $this->fail("The server stored only {$stored} of the {$expectedEnd} bytes (disk full?) — free some space and send piece ".($index + 1).' again.', true);
        }

        fclose($in);
        fclose($out);

        return $expectedEnd;
    }

    /**
     * Open the assembled bytes as a ZIP before they ever reach the vault, so a
     * truncated transfer can never be recorded as a usable backup.
     *
     * @throws ChunkUploadException
     */
    private function assertReadableZip(string $path, string $uploadId): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            @unlink($path);
            $this->fail('The assembled file is not a readable ZIP archive — reload the page and upload again.');
        }

        $zip->close();
    }

    /**
     * Path the finished archive should take inside the vault directory,
     * stepping aside instead of overwriting an archive already stored there.
     *
     * @return string absolute path
     *
     * @throws ChunkUploadException
     */
    private function reserveTarget(string $fileName): string
    {
        $directory = $this->ensureDirectory($this->backupDirectory());
        $target = "{$directory}/{$fileName}";

        if (! is_file($target)) {
            return $target;
        }

        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $stamp = now()->format('Ymd_His');
        $candidate = "{$directory}/{$base}_{$stamp}.zip";

        for ($n = 2; is_file($candidate); $n++) {
            $candidate = "{$directory}/{$base}_{$stamp}_{$n}.zip";
        }

        return $candidate;
    }

    /**
     * Drop part files, locks and markers from transfers abandoned long ago.
     */
    private function purgeStale(): void
    {
        $directory = $this->directory();

        if (! is_dir($directory)) {
            return;
        }

        $cutoff = time() - self::STALE_SECONDS;

        foreach (File::glob("{$directory}/*") ?: [] as $path) {
            $mtime = is_file($path) ? @filemtime($path) : false;

            if ($mtime !== false && $mtime < $cutoff) {
                @unlink($path);
            }
        }
    }

    /**
     * @throws ChunkUploadException
     */
    private function ensureDirectory(string $directory): string
    {
        if (! is_dir($directory)) {
            File::ensureDirectoryExists($directory, 0755, true);
        }

        if (! is_dir($directory) || ! is_writable($directory)) {
            $this->fail('The server cannot write to its storage directory — check permissions and free disk space.', true);
        }

        return $directory;
    }

    private function directory(): string
    {
        return Storage::disk('local')->path('chunked-uploads');
    }

    private function backupDirectory(): string
    {
        return Storage::disk('local')->path('backups');
    }

    private function chunkBytes(): int
    {
        return max(1024, (int) config('uploads.backup_chunk_bytes', self::CHUNK_BYTES));
    }

    private function maxBytes(): int
    {
        return max($this->chunkBytes(), (int) config('uploads.backup_max_bytes', self::MAX_BYTES));
    }

    private function maxMegabytes(): int
    {
        return (int) ceil($this->maxBytes() / 1048576);
    }

    /**
     * @throws ChunkUploadException
     */
    private function fail(string $message, bool $retryable = false): never
    {
        throw new ChunkUploadException($message, $retryable);
    }
}
