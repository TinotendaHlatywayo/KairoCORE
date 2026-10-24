<?php

namespace App\Filament\Admin\Pages;

use App\Models\School;
use App\Support\UploadReason;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Recovery\Jobs\GeneratePlatformBackupJob;
use Modules\Recovery\Jobs\RestorePlatformBackupJob;
use Modules\Recovery\Models\PlatformBackup;
use Modules\Recovery\Models\PlatformRestoreLog;
use Modules\Recovery\Services\PlatformBackupService;
use Modules\Recovery\Services\PlatformRestoreService;

class PlatformBackupManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $view = 'filament.admin.pages.platform-backup-manager';

    protected static ?int $navigationSort = 1;

    public ?array $uploadData = [];

    public array $backupsList = [];

    public array $restoreLogsList = [];

    public string $backupScope = 'system';

    public ?int $tenantSchoolId = null;

    public array $selectedTenantIds = [];

    public array $tenantOptions = [];

    public string $backupNotes = '';

    public string $backupDestination = 'server'; // server | download | both

    /**
     * Whether a system-scope archive should also carry the uploaded files.
     * Files are not partitioned per tenant, so a tenant backup that restored
     * them would overwrite the media of every other school.
     */
    public bool $includeFiles = true;

    public ?int $restoreBackupId = null;

    public string $vaultStatusFilter = '';

    public ?string $vaultDateFrom = null;

    public ?string $vaultDateTo = null;

    public bool $showAllRestoreLogs = false;

    public array $selectedBackupIds = [];

    public static function canAccess(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $this->form->fill();
        $this->tenantOptions = School::query()->orderBy('name')->pluck('name', 'id')->all();
        $this->refreshBackupsList();
    }

    public function refreshBackupsList(): void
    {
        $query = PlatformBackup::with('school')->latest();

        if ($this->vaultStatusFilter !== '') {
            $query->where('status', $this->vaultStatusFilter);
        }
        if ($this->vaultDateFrom) {
            $query->whereDate('created_at', '>=', $this->vaultDateFrom);
        }
        if ($this->vaultDateTo) {
            $query->whereDate('created_at', '<=', $this->vaultDateTo);
        }

        $this->backupsList = $query->get()
            ->map(function (PlatformBackup $backup) {
                $data = $backup->toArray();
                $data['school_name'] = $backup->school?->name;
                $data['scope'] = $backup->scope ?? 'system';
                $data['scope_label'] = match ($backup->scope ?? 'system') {
                    'tenant' => $backup->school?->name ? ('Tenant: '.$backup->school->name) : __('Tenant'),
                    default => __('Whole system'),
                };
                $data['download_url'] = route('platform.backups.download', $backup->id);
                $data['size_mb'] = round(($backup->size_bytes ?? 0) / 1048576, 2);

                return $data;
            })
            ->all();

        // Deselect rows that no longer exist after a poll refresh.
        $existingIds = array_column($this->backupsList, 'id');
        $this->selectedBackupIds = array_values(array_intersect($this->selectedBackupIds, $existingIds));

        $logsQuery = PlatformRestoreLog::with('backup')->latest();
        $this->restoreLogsList = $logsQuery
            ->limit($this->showAllRestoreLogs ? 50 : 3)
            ->get()
            ->map(fn (PlatformRestoreLog $log) => [
                'id' => $log->id,
                'status' => $log->status,
                'backup' => $log->backup?->filename,
                'error' => $log->error_details,
                'summary' => $log->summary,
                'created_at' => optional($log->created_at)->diffForHumans(),
            ])
            ->all();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('backup_zip')
                    ->label(__('Recovery archive (.zip)'))
                    ->disk('local')
                    ->directory('backups')
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                    // Same ceiling the server enforces, so an oversized pick is
                    // reported here (with the real number) instead of only
                    // failing somewhere in the upload pipeline.
                    ->maxSize(UploadReason::limitMegabytes() * 1024)
                    ->helperText(__('Archives up to :limit can be uploaded. Larger ones cannot travel through a browser — copy them onto the server under storage/app/backups and they will appear in the vault below.', ['limit' => UploadReason::limitPhrase()]))
                    ->preserveFilenames()
                    ->required(),
            ])
            ->statePath('uploadData');
    }

    public function triggerPlatformBackup(): void
    {
        if ($this->backupScope === 'tenant' && ! $this->tenantSchoolId) {
            Notification::make()->title(__('Select a tenant to back up'))->danger()->send();

            return;
        }

        if ($this->backupScope === 'selected' && empty($this->selectedTenantIds)) {
            Notification::make()->title(__('Select at least one tenant to back up'))->danger()->send();

            return;
        }

        $service = app(PlatformBackupService::class);

        try {
            $schoolIds = match ($this->backupScope) {
                'tenant' => [(int) $this->tenantSchoolId],
                'selected' => array_map('intval', $this->selectedTenantIds),
                default => null,
            };

            // Create the vault row immediately, then queue generation: a full
            // platform archive can take minutes and would otherwise blow past
            // the browser/proxy request timeout (Cloudflare 520) and leave the
            // button looking dead while PHP keeps running.
            $includeFiles = $this->backupScope === 'system' && $this->includeFiles;
            $backup = $service->createRecord($schoolIds, $this->backupNotes ?: null, $includeFiles);

            GeneratePlatformBackupJob::dispatch($backup->id, $schoolIds, $includeFiles);

            $this->refreshBackupsList();
            $this->backupNotes = '';

            Notification::make()
                ->title(__('Backup generation started'))
                ->body($includeFiles
                    ? __('The archive (database + every uploaded file) is being built in the background. Its status updates in the recovery vault below.')
                    : __('The archive is being built in the background. Its status updates in the recovery vault below.'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Backup failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Import a locally-held archive into the vault without restoring it.
     */
    public function uploadExternalBackup(): void
    {
        $backup = $this->registerUploadedArchive();

        if (! $backup) {
            return;
        }

        $this->form->fill();
        $this->refreshBackupsList();

        Notification::make()
            ->title(__('Backup archive imported successfully'))
            ->success()
            ->send();
    }

    /**
     * Import a locally-held archive and immediately restore it.
     */
    public function restoreFromUploadedBackup(): void
    {
        $backup = $this->registerUploadedArchive();

        if (! $backup) {
            return;
        }

        $this->form->fill();
        $this->performRestore($backup);
    }

    protected function registerUploadedArchive(): ?PlatformBackup
    {
        $data = $this->form->getState();
        $fileName = basename($data['backup_zip'] ?? '');

        if (! $fileName) {
            Notification::make()->title(__('Please choose an archive to upload.'))->danger()->send();

            return null;
        }

        $disk = Storage::disk('local');
        $relative = "backups/{$fileName}";

        if (! $disk->exists($relative)) {
            Notification::make()
                ->title(__('The uploaded archive never reached the server'))
                ->body(__('The transfer stopped before the file was stored — usually because the archive is larger than this site accepts (:limit), or because the connection dropped halfway. Try again, or copy the archive onto the server under storage/app/backups and refresh this page.', ['limit' => UploadReason::limitPhrase()]))
                ->danger()
                ->send();

            return null;
        }

        $filePath = $disk->path($relative);

        $scope = 'system';
        $schoolId = null;
        $notes = null;
        // Archives written before files were tracked have no flag at all, and
        // are honest about carrying only the dump.
        $includeFiles = false;

        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            // Truncated bytes: the body was cut off in transit (proxy body
            // limit, dropped connection) or the upload was never complete.
            $disk->delete($relative);

            Notification::make()
                ->title(__('The uploaded archive is damaged'))
                ->body(__('The file is not a readable ZIP archive, which means the upload stopped part-way through — the archive is larger than this site accepts (:limit), or the connection dropped. Re-upload it, or copy the intact archive onto the server under storage/app/backups.', ['limit' => UploadReason::limitPhrase()]))
                ->danger()
                ->send();

            return null;
        }

        $meta = $zip->getFromName('backup_meta.json');
        if ($meta) {
            $decoded = json_decode($meta, true) ?: [];
            $scope = $decoded['scope'] ?? 'system';
            $schoolId = $decoded['school_id'] ?? null;
            $notes = $decoded['notes'] ?? null;
            $includeFiles = (bool) ($decoded['include_files'] ?? false);
        }
        $zip->close();

        return PlatformBackup::create([
            'filename' => $fileName,
            'scope' => $scope,
            'school_id' => $schoolId,
            'notes' => $notes,
            'include_files' => $includeFiles,
            'size_bytes' => filesize($filePath),
            'checksum' => hash_file('sha256', $filePath),
            'disk' => 'local',
            'is_verified' => true,
            'status' => 'completed',
        ]);
    }

    public function downloadBackup(int $id)
    {
        return redirect()->route('platform.backups.download', $id);
    }

    public function executePlatformRestore(int $id): void
    {
        $backup = PlatformBackup::find($id);
        if (! $backup) {
            return;
        }

        $this->performRestore($backup);
    }

    public function restoreSelectedBackup(): void
    {
        if (! $this->restoreBackupId) {
            Notification::make()->title(__('Select a backup to restore'))->danger()->send();

            return;
        }

        $backup = PlatformBackup::find($this->restoreBackupId);
        if (! $backup) {
            Notification::make()->title(__('That backup no longer exists'))->danger()->send();

            return;
        }

        $this->performRestore($backup);
    }

    protected function performRestore(PlatformBackup $backup): void
    {
        // Previous attempts that died mid-run (fatal/timeout) stay "processing"
        // forever; clear them so the status list never lies about a restore.
        PlatformRestoreLog::whereIn('status', ['pending', 'processing'])->update([
            'status' => 'failed',
            'error_details' => 'Interrupted: superseded by a newer restore attempt.',
        ]);

        $log = PlatformRestoreLog::create([
            'backup_id' => $backup->id,
            'performed_by_id' => Auth::id(),
            'status' => 'pending',
        ]);

        ['ok' => $assetsReplaced] = $this->replaceWebsiteAssets($log, $backup);

        // Queue the restore instead of running it inside the request: a full
        // restore takes minutes, and a synchronous run both blocked the UI
        // ("dead" button) and exceeded the Cloudflare/proxy origin timeout
        // (520), killing the response while the work kept going. The list
        // below polls every 5s, so the pending -> processing -> completed
        // progression stays visible without holding the request open.
        try {
            RestorePlatformBackupJob::dispatch($log->id);
            $this->refreshBackupsList();
            Notification::make()
                ->title(__('Restore started'))
                ->body($assetsReplaced
                    ? __('The website assets were put back first, and the database is restoring now. Follow its status under Recent restore activity.')
                    : __('The restore is running in the background. Follow its status under Recent restore activity.'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_details' => Str::limit($e->getMessage(), 5000),
            ]);
            $this->refreshBackupsList();
            Notification::make()
                ->title(__('Restore failed to start'))
                ->body(Str::limit($e->getMessage(), 500, ' …'))
                ->danger()
                ->send();
        }
    }

    /**
     * Replace the web-root assets (favicons, og images, logos, website photos)
     * straight from the archive.
     *
     * This has to happen here: only the web container serves public/, and the
     * worker writing into its own copy would never reach Apache. Returns a
     * short human-readable note so the restore log records what was put back
     * before the queued database phase runs.
     *
     * @return array{summary: string, ok: bool}
     */
    protected function replaceWebsiteAssets(PlatformRestoreLog $log, PlatformBackup $backup): array
    {
        $service = app(PlatformRestoreService::class);

        try {
            $stats = $service->restorePublicAssets($backup);
        } catch (\Throwable $e) {
            report($e);
            $note = __('Website assets: not restored (:reason)', [
                'reason' => Str::limit($e->getMessage(), 80, ' …'),
            ]);
            $log->update(['summary' => $note]);

            return ['summary' => $note, 'ok' => false];
        }

        if ($stats['files'] === 0) {
            return ['summary' => '', 'ok' => true];
        }

        $note = __('Website assets: :count files (:size) restored', [
            'count' => $stats['files'],
            'size' => $service->formatBytes($stats['bytes']),
        ]);

        if ($stats['skipped'] > 0) {
            $note .= ' · '.__(':count skipped', ['count' => $stats['skipped']]);
        }

        $log->update(['summary' => $note]);

        return ['summary' => $note, 'ok' => true];
    }

    public function deleteBackupRecord(int $id): void
    {
        $backup = PlatformBackup::find($id);
        if ($backup) {
            Storage::disk($backup->disk ?: 'local')->delete("backups/{$backup->filename}");
            $backup->delete();
            $this->refreshBackupsList();

            Notification::make()->title(__('Backup file removed.'))->success()->send();
        }
    }

    public function clearVaultFilters(): void
    {
        $this->vaultStatusFilter = '';
        $this->vaultDateFrom = null;
        $this->vaultDateTo = null;
        $this->refreshBackupsList();
    }

    public function toggleRestoreLogExpansion(): void
    {
        $this->showAllRestoreLogs = ! $this->showAllRestoreLogs;
        $this->refreshBackupsList();
    }

    public function toggleSelectAllBackups(): void
    {
        // wire:model checkboxes produce string ids — normalise before comparing.
        $all = array_map('intval', array_column($this->backupsList, 'id'));
        $selected = array_map('intval', $this->selectedBackupIds);
        sort($all);
        sort($selected);

        $this->selectedBackupIds = $all === $selected
            ? []
            : array_map('strval', array_column($this->backupsList, 'id'));
    }

    public function downloadSelectedBackups()
    {
        if ($this->selectedBackupIds === []) {
            Notification::make()->title(__('Select at least one backup first'))->danger()->send();

            return null;
        }

        return redirect()->away(
            route('platform.backups.download-many', ['ids' => implode(',', $this->selectedBackupIds)])
        );
    }

    public function deleteSelectedBackups(): void
    {
        if ($this->selectedBackupIds === []) {
            Notification::make()->title(__('Select at least one backup first'))->danger()->send();

            return;
        }

        $deleted = 0;
        PlatformBackup::whereIn('id', array_map('intval', $this->selectedBackupIds))->get()
            ->each(function (PlatformBackup $backup) use (&$deleted) {
                Storage::disk($backup->disk ?: 'local')->delete("backups/{$backup->filename}");
                $backup->delete();
                $deleted++;
            });

        $this->selectedBackupIds = [];
        $this->refreshBackupsList();

        Notification::make()
            ->title(__('Selected backups removed (:count)', ['count' => $deleted]))
            ->success()
            ->send();
    }
}
