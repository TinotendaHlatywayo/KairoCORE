<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Recovery\Exceptions\ChunkUploadException;
use Modules\Recovery\Models\PlatformBackup;
use Modules\Recovery\Services\ChunkedArchiveUploadService;
use Tests\TestCase;
use ZipArchive;

/**
 * "Restore from a file" streams the archive in 10 MB pieces instead of one
 * large POST that any CDN or slow link would cut off. These tests pin the
 * assembly contract: pieces land in order, every piece's checksum is checked,
 * the finished size is verified, nothing that is not a ZIP reaches the vault,
 * and a lost confirmation is answered from the marker instead of appending a
 * second copy of the final piece.
 *
 * Pieces are shrunk to 1 KB in the tests so the geometry — not the size — is
 * what gets exercised.
 */
class ChunkedBackupUploadTest extends TestCase
{
    private const CHUNK = 1024;

    private ChunkedArchiveUploadService $service;

    private string $root;

    private string $scratch;

    private string $uploadId = 'chunk-test-0001';

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('app/chunk_root_'.uniqid());
        $this->scratch = storage_path('app/chunk_scratch_'.uniqid());

        File::ensureDirectoryExists($this->root, 0777, true);
        File::ensureDirectoryExists($this->scratch, 0777, true);

        Config::set('filesystems.disks.local.root', $this->root);
        Config::set('uploads.backup_chunk_bytes', self::CHUNK);
        Config::set('uploads.backup_max_bytes', 8 * self::CHUNK);

        $this->service = new ChunkedArchiveUploadService;
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        File::deleteDirectory($this->scratch);

        parent::tearDown();
    }

    public function test_pieces_are_assembled_into_the_vault_byte_for_byte(): void
    {
        $archive = $this->makeArchive();
        $name = 'Kairo CORE_PLATFORM_SNAP_chunktest.zip';

        $pieces = $this->split($archive);
        $this->assertGreaterThan(1, count($pieces), 'The fixture must span several pieces.');

        $last = null;

        foreach ($pieces as $index => $piece) {
            $last = $this->service->append(
                uploadId: $this->uploadId,
                index: $index,
                total: count($pieces),
                totalSize: strlen($archive),
                fileName: $name,
                sourcePath: $this->writePiece($index, $piece),
                chunkSha256: hash('sha256', $piece),
            );
        }

        $this->assertTrue($last['done']);
        $this->assertSame($name, $last['filename']);
        $this->assertSame(strlen($archive), $last['size']);

        $vaulted = "{$this->root}/backups/{$name}";
        $this->assertFileExists($vaulted);
        $this->assertSame($archive, file_get_contents($vaulted));

        // The part file is consumed by the move, and the marker records the
        // finished upload so a lost confirmation can be answered again.
        $this->assertFileDoesNotExist("{$this->root}/chunked-uploads/{$this->uploadId}.part");
        $this->assertFileExists("{$this->root}/chunked-uploads/{$this->uploadId}.done");
    }

    public function test_every_early_piece_reports_the_bytes_now_stored(): void
    {
        $archive = $this->makeArchive();
        $pieces = $this->split($archive);

        $first = $this->service->append(
            uploadId: $this->uploadId,
            index: 0,
            total: count($pieces),
            totalSize: strlen($archive),
            fileName: 'reporting.zip',
            sourcePath: $this->writePiece(0, $pieces[0]),
            chunkSha256: hash('sha256', $pieces[0]),
        );

        $this->assertFalse($first['done']);
        $this->assertSame(self::CHUNK, $first['received']);
        $this->assertSame(strlen($archive), $first['total']);
    }

    public function test_a_corrupted_piece_is_refused_until_it_is_resent(): void
    {
        $archive = $this->makeArchive();
        $pieces = $this->split($archive);

        try {
            $this->service->append(
                uploadId: $this->uploadId,
                index: 0,
                total: count($pieces),
                totalSize: strlen($archive),
                fileName: 'retry.zip',
                sourcePath: $this->writePiece(0, $pieces[0]),
                chunkSha256: hash('sha256', strrev($pieces[0]).'x'),
            );
            $this->fail('A wrong checksum must be refused.');
        } catch (ChunkUploadException $e) {
            $this->assertTrue($e->retryable, 'A mangled transfer must be safe to resend.');
            $this->assertStringContainsString('checksum mismatch', $e->getMessage());
        }

        // Nothing was stored, so the clean resend behaves like a first try.
        $this->assertFileDoesNotExist("{$this->root}/chunked-uploads/{$this->uploadId}.part");

        $result = $this->service->append(
            uploadId: $this->uploadId,
            index: 0,
            total: count($pieces),
            totalSize: strlen($archive),
            fileName: 'retry.zip',
            sourcePath: $this->writePiece(0, $pieces[0]),
            chunkSha256: hash('sha256', $pieces[0]),
        );

        $this->assertFalse($result['done']);
        $this->assertSame(self::CHUNK, $result['received']);
    }

    public function test_a_piece_that_arrives_before_its_predecessor_is_refused(): void
    {
        $archive = $this->makeArchive();
        $pieces = $this->split($archive);

        $this->assertGreaterThan(1, count($pieces));

        try {
            $this->service->append(
                uploadId: $this->uploadId,
                index: 1,
                total: count($pieces),
                totalSize: strlen($archive),
                fileName: 'out-of-order.zip',
                sourcePath: $this->writePiece(1, $pieces[1]),
                chunkSha256: hash('sha256', $pieces[1]),
            );
            $this->fail('An out-of-order piece must be refused.');
        } catch (ChunkUploadException $e) {
            $this->assertFalse($e->retryable, 'Reordering cannot be fixed by resending the same piece.');
            $this->assertStringContainsString('missing an earlier piece', $e->getMessage());
        }
    }

    public function test_a_finished_upload_answers_from_its_marker_instead_of_appending_again(): void
    {
        $archive = $this->makeArchive();
        $name = 'Kairo CORE_finished.zip';
        $pieces = $this->split($archive);

        foreach ($pieces as $index => $piece) {
            $result = $this->service->append(
                uploadId: $this->uploadId,
                index: $index,
                total: count($pieces),
                totalSize: strlen($archive),
                fileName: $name,
                sourcePath: $this->writePiece($index, $piece),
                chunkSha256: hash('sha256', $piece),
            );
        }

        $this->assertTrue($result['done']);

        $vaulted = "{$this->root}/backups/{$name}";
        $size = filesize($vaulted);

        // The browser never saw the confirmation and resends the last piece.
        $repeat = $this->service->append(
            uploadId: $this->uploadId,
            index: count($pieces) - 1,
            total: count($pieces),
            totalSize: strlen($archive),
            fileName: $name,
            sourcePath: $this->writePiece(count($pieces) - 1, $pieces[count($pieces) - 1]),
            chunkSha256: hash('sha256', $pieces[count($pieces) - 1]),
        );

        $this->assertTrue($repeat['done']);
        $this->assertSame($name, $repeat['filename']);
        $this->assertSame($size, filesize($vaulted), 'A repeated final piece must not append a second copy.');
        $this->assertSame($archive, file_get_contents($vaulted));
    }

    public function test_oversized_archives_and_unusable_names_are_refused(): void
    {
        $piece = random_bytes(self::CHUNK);

        try {
            $this->service->append(
                uploadId: $this->uploadId,
                index: 0,
                total: 1,
                totalSize: 9 * self::CHUNK, // one chunk past the 8 KB test ceiling
                fileName: 'too-big.zip',
                sourcePath: $this->writePiece(0, $piece),
                chunkSha256: hash('sha256', $piece),
            );
            $this->fail('An oversized archive must be refused.');
        } catch (ChunkUploadException $e) {
            $this->assertFalse($e->retryable);
            $this->assertStringContainsString('Archives up to', $e->getMessage());
        }

        foreach (['../escape.php', 'notes.txt', '.hidden.zip', 'sub/dir.zip'] as $name) {
            try {
                $this->service->append(
                    uploadId: $this->uploadId,
                    index: 0,
                    total: 1,
                    totalSize: self::CHUNK,
                    fileName: $name,
                    sourcePath: $this->writePiece(0, $piece),
                    chunkSha256: hash('sha256', $piece),
                );
                $this->fail("The name {$name} must be refused.");
            } catch (ChunkUploadException $e) {
                $this->assertFalse($e->retryable);
                $this->assertStringContainsString('must end in .zip', $e->getMessage());
            }
        }
    }

    public function test_the_final_piece_must_match_the_reported_file_size(): void
    {
        $piece = random_bytes(self::CHUNK);

        try {
            $this->service->append(
                uploadId: $this->uploadId,
                index: 0,
                total: 1,
                totalSize: self::CHUNK + 500, // claims more bytes than arrive
                fileName: 'short.zip',
                sourcePath: $this->writePiece(0, $piece),
                chunkSha256: hash('sha256', $piece),
            );
            $this->fail('A final piece that does not match the reported size must be refused.');
        } catch (ChunkUploadException $e) {
            $this->assertFalse($e->retryable);
            $this->assertStringContainsString('were expected', $e->getMessage());
        }
    }

    public function test_a_file_that_is_not_a_zip_never_reaches_the_vault(): void
    {
        $bytes = random_bytes(1500);
        $pieces = $this->split($bytes);

        foreach ($pieces as $index => $piece) {
            try {
                $this->service->append(
                    uploadId: $this->uploadId,
                    index: $index,
                    total: count($pieces),
                    totalSize: strlen($bytes),
                    fileName: 'not-a-zip.zip',
                    sourcePath: $this->writePiece($index, $piece),
                    chunkSha256: hash('sha256', $piece),
                );
            } catch (ChunkUploadException $e) {
                $this->assertSame(count($pieces) - 1, $index, 'Only the last piece can be rejected as a non-ZIP.');
                $this->assertFalse($e->retryable);
                $this->assertStringContainsString('not a readable ZIP', $e->getMessage());
            }
        }

        $this->assertFileDoesNotExist("{$this->root}/backups/not-a-zip.zip");
        $this->assertFileDoesNotExist("{$this->root}/chunked-uploads/{$this->uploadId}.part");
    }

    public function test_a_whole_archive_can_be_uploaded_over_http_and_lands_in_the_vault(): void
    {
        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $archive = $this->makeArchive();
        $pieces = $this->split($archive);
        $name = 'Kairo CORE_http_e2e.zip';

        $fieldsFor = fn (int $index): array => [
            'upload_id' => 'http-up-0001',
            'index' => $index,
            'total' => count($pieces),
            'total_size' => strlen($archive),
            'filename' => $name,
            'chunk_sha256' => hash('sha256', $pieces[$index]),
            'chunk' => UploadedFile::fake()->createWithContent('piece.zip', $pieces[$index]),
        ];

        // Nobody in: the endpoint lives behind the same auth wall as downloads.
        $this->post(route('platform.backups.chunk'), $fieldsFor(0))
            ->assertRedirect();

        DB::beginTransaction();

        try {
            $schoolUser = User::whereNotNull('school_id')->first();
            $this->assertNotNull($schoolUser, 'The fixture needs a school user for the 403 path.');

            $this->actingAs($schoolUser)
                ->postJson(route('platform.backups.chunk'), $fieldsFor(0))
                ->assertStatus(403);

            $platformAdmin = User::whereNull('school_id')->firstOrFail();
            $this->actingAs($platformAdmin);

            $last = null;
            foreach ($pieces as $index => $piece) {
                $last = $this->post(route('platform.backups.chunk'), $fieldsFor($index))
                    ->assertOk()
                    ->json();
            }

            $this->assertTrue($last['done'] ?? false, 'The final piece must close the upload.');
            $this->assertSame($name, $last['filename'] ?? null);
            $this->assertSame(strlen($archive), $last['size'] ?? null);
            $this->assertFileExists("{$this->root}/backups/{$name}");
            $this->assertFileDoesNotExist("{$this->root}/chunked-uploads/http-up-0001.part");

            // The archive is a vault row straight away — that is what
            // "appears in the vault" means — carrying its own metadata.
            $row = PlatformBackup::find($last['id'] ?? null);
            $this->assertNotNull($row, 'The endpoint must record the archive as a vault row.');
            $this->assertSame($name, $row->filename);
            $this->assertSame('system', $row->scope);
            $this->assertTrue((bool) $row->include_files);
            $this->assertSame('chunked upload test', $row->notes);
            $this->assertSame(strlen($archive), (int) $row->size_bytes);
            $this->assertSame(hash_file('sha256', "{$this->root}/backups/{$name}"), $row->checksum);

            // A confirmation the browser never saw is answered from the
            // marker with the same row — no second copy, no duplicate row.
            $repeat = $this->post(route('platform.backups.chunk'), $fieldsFor(count($pieces) - 1))
                ->assertOk()
                ->json();

            $this->assertTrue($repeat['done'] ?? false);
            $this->assertSame($last['id'] ?? null, $repeat['id'] ?? null);
            $this->assertSame(1, PlatformBackup::where('filename', $name)->count());
        } finally {
            DB::rollBack();
        }
    }

    /**
     * A real (openable) ZIP: the endpoint refuses anything that is not one.
     */
    private function makeArchive(): string
    {
        $path = "{$this->scratch}/source.zip";
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->fail('Unable to build the fixture archive.');
        }

        $zip->addFromString('backup_meta.json', json_encode([
            'scope' => 'system',
            'include_files' => true,
            'notes' => 'chunked upload test',
        ]));
        // Incompressible, so the archive really does span several pieces.
        $zip->addFromString('backup_payload.sql', random_bytes(3000));
        $zip->close();

        return (string) file_get_contents($path);
    }

    /**
     * @return array<int, string>
     */
    private function split(string $bytes): array
    {
        return str_split($bytes, self::CHUNK);
    }

    private function writePiece(int $index, string $bytes): string
    {
        $path = "{$this->scratch}/piece-{$index}";
        File::put($path, $bytes);

        return $path;
    }
}
