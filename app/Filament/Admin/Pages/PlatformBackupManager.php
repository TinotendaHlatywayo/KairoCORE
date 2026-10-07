<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Jobs\GeneratePlatformBackupJob;
use Modules\Recovery\Jobs\RestorePlatformBackupJob;
use Modules\Recovery\Models\PlatformBackup;
use Modules\Recovery\Models\PlatformRestoreLog;
use Modules\Recovery\Services\PlatformBackupService;

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

    public ?int $restoreBackupId = null;

    public static function canAccess(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $this->form->fill();
        $this->tenantOptions = \App\Models\School::query()->orderBy('name')->pluck('name', 'id')->all();
        $this->refreshBackupsList();
    }

    public function refreshBackupsList(): void
    {
        $this->backupsList = PlatformBackup::with('school')->latest()->get()
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

        $this->restoreLogsList = PlatformRestoreLog::with('backup')->latest()->limit(8)->get()
            ->map(fn (PlatformRestoreLog $log) => [
                'id' => $log->id,
                'status' => $log->status,
                'backup' => $log->backup?->filename,
                'error' => $log->error_details,
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
                    ->preserveFilenames()
                    ->required(),
            ])
            ->statePath('uploadData');
    }

    public function triggerPlatformBackup(PlatformBackupService $service): void
    {
        if ($this->backupScope === 'tenant' && ! $this->tenantSchoolId) {
            Notification::make()->title(__('Select a tenant to back up'))->danger()->send();

            return;
        }

        if ($this->backupScope === 'selected' && empty($this->selectedTenantIds)) {
            Notification::make()->title(__('Select at least one tenant to back up'))->danger()->send();

            return;
        }

        try {
            $schoolIds = match ($this->backupScope) {
                'tenant' => [(int) $this->tenantSchoolId],
                'selected' => array_map('intval', $this->selectedTenantIds),
                default => null,
            };

            // Create the vault row immediately, then hand the heavy dump to the
            // queue worker so the web request never times out on big datasets.
            $backup = $service->createRecord($schoolIds, $this->backupNotes ?: null);

            \Modules\Recovery\Jobs\GeneratePlatformBackupJob::dispatch($backup->id, $schoolIds);

            $this->refreshBackupsList();
            $this->backupNotes = '';

            Notification::make()
                ->title(__('Backup started'))
                ->body(__('It is running in the background and will appear in the vault when ready. This page refreshes automatically.'))
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
            Notification::make()->title(__('Upload read error.'))->danger()->send();

            return null;
        }

        $filePath = $disk->path($relative);

        $scope = 'system';
        $schoolId = null;
        $notes = null;

        $zip = new \ZipArchive;
        if ($zip->open($filePath) === true) {
            $meta = $zip->getFromName('backup_meta.json');
            if ($meta) {
                $decoded = json_decode($meta, true) ?: [];
                $scope = $decoded['scope'] ?? 'system';
                $schoolId = $decoded['school_id'] ?? null;
                $notes = $decoded['notes'] ?? null;
            }
            $zip->close();
        }

        return PlatformBackup::create([
            'filename' => $fileName,
            'scope' => $scope,
            'school_id' => $schoolId,
            'notes' => $notes,
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
        $log = PlatformRestoreLog::create([
            'backup_id' => $backup->id,
            'performed_by_id' => Auth::id(),
            'status' => 'pending',
        ]);

        // Restores touch every table and can run for minutes, so they run on
        // the queue worker too — never inside the web request.
        \Modules\Recovery\Jobs\RestorePlatformBackupJob::dispatch($log->id);

        $this->refreshBackupsList();

        Notification::make()
            ->title(__('Restore started'))
            ->body(__('It is running in the background. Watch the restore status below; the page refreshes automatically.'))
            ->success()
            ->send();
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
}