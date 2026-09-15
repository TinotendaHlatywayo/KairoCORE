<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\StudentResource;
use App\Services\StudentCsvService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListStudents extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = StudentResource::class;

    protected static ?string $title = 'Student Directory';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static function csvService(): string
    {
        return StudentCsvService::class;
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getHeader(): ?View
    {
        return view('filament.app.resources.student.import.page-actions', [
            'actions' => $this->getCachedHeaderActions(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $service = static::csvService();
        $streamName = $this->csvStreamName();

        return [
            Actions\CreateAction::make(),
            ...$this->makeExportActions(),
            Action::make('importStudentsCsv')
                ->label(__('Import Students (Excel/CSV)'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalHeading(__('Import Students from Excel or CSV'))
                ->modalDescription('Upload your file and the system matches every column automatically.')
                ->modalWidth(MaxWidth::ExtraLarge)
                ->modalSubmitActionLabel(__('Import Students'))
                ->steps($this->csvImportSteps($service, $streamName, 'Students'))
                ->action(function (array $data) use ($service, $streamName) {
                    $this->runStudentImport($data, $service, $streamName);
                }),
        ];
    }

    /** Step 2: one Select per expected system column + the live mismatch report + progress panel. */
    protected function columnMatchingSchema(Get $get, string $service = '', string $streamName = ''): array
    {
        $service = $service !== '' ? $service : static::csvService();
        $streamName = $streamName !== '' ? $streamName : $this->csvStreamName();

        $file = $get('csv_file');

        if (! $file) {
            return [
                Forms\Components\Placeholder::make('no_file_yet')
                    ->label(__('Upload your Excel/CSV file first'))
                    ->content('Go back to the "Upload" step, upload your file, then come here to match its columns.'),
            ];
        }

        $filePath = $service::resolveTempFilePath($file);
        $headers = $service::readCsvHeaders($filePath);

        if (empty($headers)) {
            return [
                Forms\Components\Placeholder::make('unreadable_file')
                    ->label(__('Could not read the file header row'))
                    ->content('Make sure the first row of your file contains the column names, then re-upload it.'),
            ];
        }

        $columns = $service::columns();
        $guessMap = $service::guessMapping($headers);
        $options = array_combine($headers, $headers);

        $selects = [];

        foreach ($columns as $key => $column) {
            $selects[] = Forms\Components\Select::make("columnMap.{$key}")
                ->label($column['label'].($column['required'] ? ' *' : ''))
                ->options($options)
                ->placeholder($column['required'] ? 'Select the matching column' : 'Optional')
                ->searchable()
                ->optionsLimit(100)
                ->live()
                ->default($guessMap[$key]);
        }

        $report = $this->buildColumnReport($headers, $get('columnMap') ?? [], $service);

        $selects[] = Forms\Components\View::make('filament.app.components.csv-import.column-report')
            ->viewData([
                'issues' => $report,
                'fileHeaders' => $headers,
            ]);

        $selects[] = Forms\Components\View::make('filament.app.components.csv-import.progress-panel')
            ->viewData([
                'streamName' => $streamName,
                'message' => __('Click "Import" to begin.'),
            ]);

        return [
            Forms\Components\Fieldset::make('Column matching')
                ->columns(2)
                ->schema($selects),
        ];
    }

    /** Builds the exact mismatch list shown live in step 2. */
    protected function buildColumnReport(array $headers, array $columnMap, string $service = ''): array
    {
        $service = $service !== '' ? $service : static::csvService();
        $columns = $service::columns();
        $mapped = collect($columnMap)
            ->filter(fn ($value): bool => filled($value));

        $issues = [];

        $requiredMissing = collect($columns)
            ->filter(fn (array $column): bool => $column['required'])
            ->filter(fn (array $column, string $key): bool => blank($mapped[$key] ?? null));

        if ($requiredMissing->isNotEmpty()) {
            $issues[] = [
                'type' => 'error',
                'title' => count($requiredMissing) === 1
                    ? '1 required column is not matched yet — the import will not start until you fix this'
                    : count($requiredMissing).' required columns are not matched yet — the import will not start until you fix these',
                'items' => $requiredMissing->keys()
                    ->map(fn (string $key): string => $columns[$key]['label'])
                    ->all(),
            ];
        }

        $unused = collect($headers)
            ->filter(fn (string $header): bool => ! $mapped->contains($header))
            ->filter(function (string $header) use ($requiredMissing, $service): bool {
                $guessMap = $service::guessMapping([$header]);

                return ! $requiredMissing
                    ->keys()
                    ->contains(fn (string $key): bool => ($guessMap[$key] ?? null) === $header);
            });

        if ($unused->isNotEmpty()) {
            $issues[] = [
                'type' => 'warning',
                'title' => count($unused) === 1
                    ? '1 column in your file will be ignored (no system field uses it)'
                    : count($unused).' columns in your file will be ignored (no system field uses them)',
                'items' => $unused->values()->all(),
            ];
        }

        $duplicates = $mapped
            ->flip()
            ->filter(fn ($value, string $header): bool => collect($mapped)->filter(fn ($v): bool => $v === $header)->count() > 1)
            ->keys();

        if ($duplicates->isNotEmpty()) {
            $issues[] = [
                'type' => 'error',
                'title' => 'One file column is mapped to more than one field',
                'items' => $duplicates->all(),
            ];
        }

        if (empty($issues)) {
            $issues[] = [
                'type' => 'success',
                'title' => 'Every required column is matched — ready to import.',
                'items' => [],
            ];
        }

        return $issues;
    }

    protected function runStudentImport(array $data, string $service, string $streamName): void
    {
        $file = $data['csv_file'] ?? null;
        $columnMap = $this->effectiveColumnMap($data, $service, $file);

        if (! $file) {
            Notification::make()
                ->title(__('No file uploaded'))
                ->body('Upload an Excel (XLSX) or CSV file in the first step before importing.')
                ->danger()
                ->send();

            return;
        }

        $columns = $service::columns();

        $requiredMissing = collect($columns)
            ->filter(fn (array $column): bool => $column['required'])
            ->filter(fn (array $column, string $key): bool => blank($columnMap[$key] ?? null));

        if ($requiredMissing->isNotEmpty()) {
            Notification::make()
                ->title(__('Import not started — required columns are not mapped'))
                ->body('Match these columns before importing: '.$requiredMissing->keys()->map(fn (string $key): string => $columns[$key]['label'])->implode(', ').'.')
                ->danger()
                ->duration(8)
                ->send();

            return;
        }

        $filePath = $service::resolveTempFilePath($file);
        $schoolId = app('current_tenant')->id;

        try {
            $result = $service::import(
                $filePath,
                $schoolId,
                $columnMap,
                $this->importProgressClosure($streamName)
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Import failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $failures = collect($result['failures']);

        if ($failures->isEmpty()) {
            Notification::make()
                ->title(__('Import complete'))
                ->body('Imported '.$result['success'].' of '.$result['total'].' students.')
                ->success()
                ->send();

            return;
        }

        $failedCsv = $this->buildFailedRowsCsv($result['failures']);

        Notification::make()
            ->title(__('Import finished with errors'))
            ->body('Imported '.$result['success'].' of '.$result['total'].' students. '.$failures->count().' row(s) were rejected — download the error report below.')
            ->warning()
            ->duration(15)
            ->actions([
                NotificationAction::make('downloadFailedRows')
                    ->label(__('Download rejected rows (CSV)'))
                    ->button()
                    ->color('danger')
                    ->action(fn (): StreamedResponse => response()->streamDownload(
                        fn () => print ($failedCsv),
                        'student-import-rejected-rows.csv',
                        ['Content-Type' => 'text/csv']
                    )),
            ])
            ->send();
    }

    protected function buildFailedRowsCsv(array $failures): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Row Number', 'Error(s)']);

        foreach ($failures as $failure) {
            fputcsv($out, [
                $failure['row'],
                implode(' | ', $failure['errors']),
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }
}
