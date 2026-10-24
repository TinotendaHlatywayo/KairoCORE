<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Modules\Recovery\Services\PlatformBackupService;
use Modules\Recovery\Services\PlatformRestoreService;
use Tests\TestCase;
use ZipArchive;

/**
 * The restore path is the one place a bad archive could rewrite files outside
 * the app, and the archive path is where a backup can silently drop every
 * uploaded photo. Both are pure filesystem work, so they are pinned down here
 * without touching the database.
 */
class PlatformBackupFileArchiveTest extends TestCase
{
    private PlatformBackupService $backupService;

    private PlatformRestoreService $restoreService;

    private string $fixtureDir;

    private string $scratchDir;

    /** @var array<int, string> */
    private array $seededFiles = [];

    /** @var array<int, string> */
    private array $createdDirs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupService = new PlatformBackupService;
        $this->restoreService = new PlatformRestoreService;

        $this->fixtureDir = storage_path('app/backup_probe_'.uniqid());
        $this->scratchDir = storage_path('app/backup_scratch_'.uniqid());

        File::ensureDirectoryExists($this->fixtureDir, 0777, true);
        File::ensureDirectoryExists($this->scratchDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->seededFiles as $file) {
            @unlink($file);
        }

        foreach (array_reverse($this->createdDirs) as $dir) {
            @rmdir($dir);
        }

        File::deleteDirectory($this->fixtureDir);
        File::deleteDirectory($this->scratchDir);

        parent::tearDown();
    }

    public function test_backup_covers_every_place_an_upload_can_live(): void
    {
        $sources = collect($this->backupService->archiveSources());
        $directories = $sources->where('type', 'dir')->pluck('path')->all();
        $files = $sources->where('type', 'file')->pluck('path')->all();

        // The shared volume (logos, avatars, library scans, CMS media) plus the
        // web-root assets that only Apache can serve.
        $this->assertContains(storage_path('app'), $directories);
        $this->assertContains(public_path('images'), $directories);
        $this->assertContains(public_path('img'), $directories);

        // Every top-level public file is data, never code.
        foreach ($files as $file) {
            $this->assertStringStartsWith(public_path(), $file);
            $this->assertFileExists($file);
        }

        $this->assertNotContains(public_path('index.php'), $files);
        $this->assertNotContains(public_path('.htaccess'), $files);
    }

    public function test_files_are_stored_under_their_app_relative_path(): void
    {
        File::put("{$this->fixtureDir}/photo.jpg", 'JPEG-BYTES');

        $zipPath = "{$this->scratchDir}/archive.zip";
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);

        $stats = $this->backupService->addFilesToZip($zip, [
            ['path' => $this->fixtureDir, 'type' => 'dir'],
        ]);

        $this->assertTrue($zip->close());

        $expected = 'files/'.$this->backupService->relativeToBase($this->fixtureDir).'/photo.jpg';
        $this->assertSame(1, $stats['files']);
        $this->assertContains($expected, $this->entryNames($zipPath));
    }

    public function test_vault_archives_scratch_work_and_symlinks_never_enter_the_archive(): void
    {
        $this->seedProbe('platform_temp_snapshots/probe.sql');
        $this->seedProbe('restore_platform_temp_42/archive.zip');
        $this->seedProbe('livewire-tmp/probe.tmp');
        $this->seedProbe('private/backups/old-archive.zip');
        $this->seedProbe('private/tmp/probe.csv');

        $link = storage_path('app/backup_link_probe');
        @unlink($link);
        $this->assertTrue(@symlink($this->fixtureDir, $link));

        $relatives = [];
        foreach ($this->backupService->walkFiles(storage_path('app'), 'storage/app') as [, $relative]) {
            $relatives[] = $relative;
        }

        foreach ($this->backupService->excludedDirectories() as $excluded) {
            foreach ($relatives as $relative) {
                $this->assertFalse(
                    str_starts_with($relative, $excluded.'/'),
                    "Excluded directory {$excluded} leaked into the archive."
                );
            }
        }

        foreach ($relatives as $relative) {
            $this->assertStringNotContainsString('backup_link_probe', $relative);
        }

        @unlink($link);
    }

    public function test_storage_and_public_entries_land_in_their_own_root(): void
    {
        $zipPath = $this->makeZip([
            'files/storage/app/public/uploads/logo.png' => 'LOGO',
            'files/public/images/og-card.jpg' => 'OGCARD',
            'files/public/kairocore-link-preview.jpg' => 'PREVIEW',
            'backup_payload.sql' => 'SELECT 1;',
        ]);

        $zip = $this->openZip($zipPath);

        $storageRoot = "{$this->scratchDir}/storage_root";
        $publicRoot = "{$this->scratchDir}/public_root";

        $storageStats = $this->restoreService->extractEntries($zip, 'files/storage/', $storageRoot);
        $publicStats = $this->restoreService->extractEntries($zip, 'files/public/', $publicRoot);
        $zip->close();

        $this->assertSame('LOGO', file_get_contents("{$storageRoot}/app/public/uploads/logo.png"));
        $this->assertSame('OGCARD', file_get_contents("{$publicRoot}/images/og-card.jpg"));
        $this->assertSame('PREVIEW', file_get_contents("{$publicRoot}/kairocore-link-preview.jpg"));

        $this->assertSame(1, $storageStats['files']);
        $this->assertSame(2, $publicStats['files']);
        $this->assertSame(0, $storageStats['skipped']);
        $this->assertSame(0, $publicStats['skipped']);

        // The dump itself is never treated as a file entry.
        $this->assertFileDoesNotExist("{$storageRoot}/backup_payload.sql");
    }

    public function test_entries_that_try_to_escape_the_root_are_refused(): void
    {
        $zipPath = $this->makeZip([
            'files/storage/app/../../../escaped.txt' => 'NOPE',
            'files/public/../../escaped.txt' => 'NOPE',
            'files/storage/etc/passwd' => 'OKAY',
        ]);

        $zip = $this->openZip($zipPath);
        $root = "{$this->scratchDir}/escape_root";
        $stats = $this->restoreService->extractEntries($zip, 'files/storage/', $root);
        $zip->close();

        $this->assertSame(1, $stats['files']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertFileDoesNotExist("{$this->scratchDir}/escaped.txt");
        $this->assertFileDoesNotExist($root.'/escaped.txt');
        $this->assertSame('OKAY', file_get_contents("{$root}/etc/passwd"));
    }

    public function test_target_resolution_rejects_every_escaping_path(): void
    {
        $root = '/var/www/html/public';

        $this->assertSame(
            '/var/www/html/public/images/logo.png',
            $this->restoreService->resolveTarget($root, 'images/logo.png')
        );

        $this->assertNull($this->restoreService->resolveTarget($root, '../.env'));
        $this->assertNull($this->restoreService->resolveTarget($root, 'images/../../.env'));
        $this->assertNull($this->restoreService->resolveTarget($root, '/etc/passwd'));
        $this->assertNull($this->restoreService->resolveTarget($root, 'C:\\windows\\system32'));
        $this->assertNull($this->restoreService->resolveTarget($root, ''));
        $this->assertNull($this->restoreService->resolveTarget($root, './logo.png'));
    }

    public function test_an_older_sql_only_archive_still_restores(): void
    {
        $zipPath = $this->makeZip([
            'backup_payload.sql' => 'SELECT 1;',
            'backup_meta.json' => '{"scope":"system"}',
        ]);

        $zip = $this->openZip($zipPath);
        $stats = $this->restoreService->extractEntries($zip, 'files/storage/', "{$this->scratchDir}/legacy");
        $zip->close();

        $this->assertSame(0, $stats['files']);
        $this->assertSame(0, $stats['bytes']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertDirectoryDoesNotExist("{$this->scratchDir}/legacy");
    }

    public function test_public_paths_are_reported_relative_to_the_app_root(): void
    {
        $this->assertSame(
            'public/images',
            $this->backupService->relativeToBase(public_path('images'))
        );
        $this->assertSame(
            'storage/app',
            $this->backupService->relativeToBase(storage_path('app'))
        );
        $this->assertNull($this->backupService->relativeToBase('/etc/hosts'));
    }

    public function test_restore_summary_says_what_was_put_back(): void
    {
        $this->assertSame(
            'Database restored',
            $this->restoreService->buildSummary(null, ['files' => 0, 'bytes' => 0, 'skipped' => 0])
        );

        $summary = $this->restoreService->buildSummary(
            'Website assets: 46 files (21.6 MB) restored',
            ['files' => 712, 'bytes' => 118929408, 'skipped' => 0]
        );

        $this->assertStringContainsString('Database restored', $summary);
        $this->assertStringContainsString('Website assets: 46 files (21.6 MB) restored', $summary);
        $this->assertStringContainsString('Media + uploads: 712 files (113.4 MB) restored', $summary);
        $this->assertLessThanOrEqual(255, strlen($summary));

        $withSkips = $this->restoreService->buildSummary(null, ['files' => 3, 'bytes' => 10, 'skipped' => 2]);
        $this->assertStringContainsString('2 files skipped', $withSkips);
    }

    public function test_byte_sizes_stay_readable(): void
    {
        $this->assertSame('0 B', $this->restoreService->formatBytes(0));
        $this->assertSame('512 B', $this->restoreService->formatBytes(512));
        $this->assertSame('1 KB', $this->restoreService->formatBytes(1024));
        $this->assertSame('1.5 KB', $this->restoreService->formatBytes(1536));
        $this->assertSame('1 MB', $this->restoreService->formatBytes(1048576));
        $this->assertSame('1 GB', $this->restoreService->formatBytes(1073741824));
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function makeZip(array $entries, string $name = 'archive.zip'): string
    {
        $zipPath = "{$this->scratchDir}/{$name}";
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->fail('Unable to build the test archive.');
        }

        foreach ($entries as $entry => $contents) {
            $zip->addFromString($entry, $contents);
        }

        $zip->close();

        return $zipPath;
    }

    private function openZip(string $zipPath): ZipArchive
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            $this->fail('Unable to open the test archive.');
        }

        return $zip;
    }

    /**
     * @return array<int, string>
     */
    private function entryNames(string $zipPath): array
    {
        $zip = $this->openZip($zipPath);
        $names = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }

        $zip->close();

        return $names;
    }

    private function seedProbe(string $relative): string
    {
        $full = storage_path('app/'.$relative);
        $directory = dirname($full);

        if (! is_dir($directory)) {
            File::ensureDirectoryExists($directory, 0777, true);
            $this->createdDirs[] = $directory;
        }

        File::put($full, 'probe');
        $this->seededFiles[] = $full;

        return $full;
    }
}
