<?php

namespace App\Filament\App\Concerns;

use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Drop-in CSV bulk import + CSV/PDF export for any List page.
 *
 * Use with a CsvBulkService subclass:
 *
 *     use HasCsvBulkActions;
 *
 *     protected static function csvService(): string
 *     {
 *         return EmployeeCsvService::class;
 *     }
 *
 *     protected function getHeaderActions(): array
 *     {
 *         return [
 *             Actions\CreateAction::make(),
 *             ...$this->csvBulkActions(),   // Import + Export (CSV/PDF)
 *         ];
 *     }
 */
trait HasCsvBulkActions
{
    /** The CsvBulkService subclass that defines columns + import/export logic. */
    abstract protected static function csvService(): string;

    protected function csvBulkActions(): array
    {
        return [
            $this->makeImportAction(),
            ...$this->makeExportActions(),
        ];
    }

    protected function makeExportActions(): array
    {
        $service = static::csvService();
        $filename = Str::slug($this->getExportTitle());

        return [
            ActionGroup::make([
                Action::make('export_csv')
                    ->label(__('Export as CSV'))
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(fn (): StreamedResponse => $this->exportCsv($service, $filename)),
                Action::make('export_pdf')
                    ->label(__('Export as PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(fn (): StreamedResponse => $this->exportPdf($service, $filename)),
            ])
                ->label(__('Export All'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),
        ];
    }

    protected function getExportTitle(): string
    {
        $service = class_basename(static::csvService());
        $entity = preg_replace('/CsvService$/', '', $service);

        return Str::plural(Str::headline($entity ?: 'Data'));
    }

    protected function makeImportAction(): Action
    {
        $service = static::csvService();
        $streamName = $this->csvStreamName();
        $title = $this->getExportTitle();

        return Action::make('import_csv')
            ->label(__("Import {$title} (CSV)"))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->modalHeading(__("Import {$title} from CSV"))
            ->modalDescription(__('Two-phase import: upload your file, then match its columns to the system columns. Any mismatch is flagged before anything is saved.'))
            ->modalWidth(MaxWidth::ExtraLarge)
            ->modalSubmitActionLabel(__("Import {$title}"))
            ->steps([
                Forms\Components\Wizard\Step::make(__('Upload'))
                    ->description(__('Download the template and fill it in'))
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('download_csv_template')
                                ->label(__('Download CSV Template'))
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->action(fn (): StreamedResponse => $this->downloadCsvTemplate($service)),
                        ]),
                        Forms\Components\FileUpload::make('csv_file')
                            ->label(__('CSV File'))
                            ->helperText(__("The template above contains the exact system columns. Replace the example row with your {$title} records."))
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'text/x-csv', 'application/csv', 'application/vnd.ms-excel'])
                            ->maxSize(4096)
                            ->required()
                            ->live()
                            ->storeFiles(false),
                    ]),
                Forms\Components\Wizard\Step::make(__('Match Columns'))
                    ->description(__('Map your file columns to the system columns'))
                    ->schema(fn (Get $get): array => $this->columnMatchingSchema($get, $service, $streamName)),
            ])
            ->action(function (array $data) use ($service, $streamName) {
                $this->runCsvImport($data, $service, $streamName);
            });
    }

    protected function exportCsv(string $service, string $filename): StreamedResponse
    {
        $schoolId = app('current_tenant')->id;

        return response()->streamDownload(function () use ($service, $schoolId) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it cleanly
            fputcsv($out, $service::exportHeaders());

            foreach ($service::exportRows($schoolId) as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, "{$filename}-export-".now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function exportPdf(string $service, string $filename): StreamedResponse
    {
        $schoolId = app('current_tenant')->id;
        $headers = $service::exportHeaders();
        $rows = collect($service::exportRows($schoolId))->all();

        $pdf = Pdf::loadView('filament.app.components.csv-export-pdf', [
            'school' => School::find($schoolId),
            'title' => $this->getExportTitle(),
            'subtitle' => __('Exported from Kairo CORE on ').now()->format('d M Y H:i'),
            'headers' => $headers,
            'rows' => $rows,
            'primaryColor' => '#5b4fe9',
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "{$filename}-export-".now()->format('Y-m-d-His').'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function downloadCsvTemplate(string $service): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print ($service::templateCsv()),
            'import-template.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    protected function csvStreamName(): string
    {
        return Str::slug(class_basename(static::csvService())).'-import-progress';
    }

    /** Step 2: one Select per expected system column + the live mismatch report + progress panel. */
    protected function columnMatchingSchema(Get $get, string $service, string $streamName): array
    {
        $file = $get('csv_file');

        if (! $file) {
            return [
                Forms\Components\Placeholder::make('no_file_yet')
                    ->label(__('Upload your CSV first'))
                    ->content(__('Go back to the "Upload" step, upload your file, then come here to match its columns.')),
            ];
        }

        $filePath = $service::resolveTempFilePath($file);
        $headers = $service::readCsvHeaders($filePath);

        if (empty($headers)) {
            return [
                Forms\Components\Placeholder::make('unreadable_file')
                    ->label(__('Could not read the CSV header row'))
                    ->content(__('Make sure the first row of your file contains the column names, then re-upload it.')),
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
                ->placeholder($column['required'] ? __('Select the matching column') : __('Optional'))
                ->searchable()
                ->optionsLimit(100)
                ->live()
                ->default($guessMap[$key]);
        }

        $report = $this->buildColumnReport($headers, $get('columnMap') ?? [], $columns);

        $selects[] = Forms\Components\View::make('filament.app.components.csv-import.column-report')
            ->viewData([
                'issues' => $report,
                'fileHeaders' => $headers,
            ]);

        $selects[] = Forms\Components\View::make('filament.app.components.csv-import.progress-panel')
            ->viewData([
                'streamName' => $streamName,
                'message' => __('Click "Import" to begin — progress appears here.'),
            ]);

        return [
            Forms\Components\Fieldset::make(__('Column matching'))
                ->columns(2)
                ->schema($selects),
        ];
    }

    /** Builds the exact mismatch list shown live in step 2. */
    protected function buildColumnReport(array $headers, array $columnMap, array $columns): array
    {
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
                    ? __('1 required column is not matched yet — the import will not start until you fix this')
                    : count($requiredMissing).__(' required columns are not matched yet — the import will not start until you fix these'),
                'items' => $requiredMissing->keys()
                    ->map(fn (string $key): string => $columns[$key]['label'])
                    ->all(),
            ];
        }

        $unused = collect($headers)
            ->filter(fn (string $header): bool => ! $mapped->contains($header))
            ->filter(function (string $header) use ($requiredMissing): bool {
                $guessMap = collect($requiredMissing->keys())
                    ->mapWithKeys(fn (string $key): array => [$key => $this->singleGuess($key, $header)])
                    ->filter();

                return ! $guessMap->contains($header);
            });

        if ($unused->isNotEmpty()) {
            $issues[] = [
                'type' => 'warning',
                'title' => count($unused) === 1
                    ? __('1 column in your file will be ignored (no system field uses it)')
                    : count($unused).__(' columns in your file will be ignored (no system field uses them)'),
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
                'title' => __('One file column is mapped to more than one field'),
                'items' => $duplicates->all(),
            ];
        }

        if (empty($issues)) {
            $issues[] = [
                'type' => 'success',
                'title' => __('Every required column is matched — ready to import.'),
                'items' => [],
            ];
        }

        return $issues;
    }

    protected function singleGuess(string $key, string $header): ?string
    {
        $columns = static::csvService()::columns();
        $guesses = array_map('strtolower', $columns[$key]['guesses'] ?? [$columns[$key]['label']]);

        return in_array(strtolower($header), $guesses, true) ? $header : null;
    }

    protected function runCsvImport(array $data, string $service, string $streamName): void
    {
        $file = $data['csv_file'] ?? null;
        $columnMap = $data['columnMap'] ?? [];

        if (! $file) {
            Notification::make()
                ->title(__('No file uploaded'))
                ->body(__('Upload a CSV file in the first step before importing.'))
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
                ->body(__('Match these columns before importing: ').$requiredMissing->keys()->map(fn (string $key): string => $columns[$key]['label'])->implode(', ').'.')
                ->danger()
                ->persistent()
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
                function (int $processed, int $total, bool $rowFailed, array $errors) use ($streamName) {
                    $percent = $total > 0 ? (int) floor(($processed / $total) * 100) : 100;

                    $status = $rowFailed
                        ? '<span class="text-danger-600">'.count($errors).__(' row(s) rejected so far').'</span>'
                        : '<span class="text-gray-400">'.__('No errors yet').'</span>';

                    $html = '<div class="flex items-center gap-3">'
                        .'<div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">'
                        .'<div class="h-full rounded-full bg-primary-500 transition-all duration-200" style="width: '.$percent.'%"></div>'
                        .'</div>'
                        .'<span class="w-16 text-right text-xs font-medium text-gray-500">'.$percent.'%</span>'
                        .'</div>'
                        .'<p class="mt-1 text-xs">'.$status.'</p>';

                    $this->stream($streamName, $html, replace: true);
                }
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
                ->body(__('Imported ').$result['success'].__(' of ').$result['total'].__(' records.'))
                ->success()
                ->send();

            return;
        }

        $failedCsv = $this->buildFailedRowsCsv($result['failures']);

        Notification::make()
            ->title(__('Import finished with errors'))
            ->body(__('Imported ').$result['success'].__(' of ').$result['total'].__(' records. ').$failures->count().__(' row(s) were rejected — download the error report below.'))
            ->warning()
            ->persistent()
            ->actions([
                Action::make('downloadFailedRows')
                    ->label(__('Download rejected rows (CSV)'))
                    ->button()
                    ->color('danger')
                    ->action(fn (): StreamedResponse => response()->streamDownload(
                        fn () => print ($failedCsv),
                        'import-rejected-rows.csv',
                        ['Content-Type' => 'text/csv']
                    )),
            ])
            ->send();
    }

    protected function buildFailedRowsCsv(array $failures): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [__('Row Number'), __('Error(s)')]);

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
