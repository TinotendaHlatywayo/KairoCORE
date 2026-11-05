<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
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
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListStudents extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

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
        $actions = [
            Actions\CreateAction::make(),
        ];

        // Moving the directory in and out of the school is its own decision from
        // being able to read it, so each action is offered only when this page
        // grants it. Someone who can list students but not export them simply
        // has no Export button.
        if ($this->canOnPage('export')) {
            $actions = array_merge($actions, $this->makeExportActions());
        }

        if ($this->canOnPage('import')) {
            $actions[] = $this->makeStudentImportAction();
        }

        $actions[] = $this->getHelpAction();

        return array_filter($actions);
    }

    protected function makeStudentImportAction(): Action
    {
        $service = static::csvService();
        $streamName = $this->csvStreamName();

        return Action::make('importStudentsCsv')
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
            });
    }

    protected function missingStreamSchema(Get $get, string $service): array
    {
        $file = $get('csv_file');

        if (! $file || ! is_a($service, StudentCsvService::class, true)) {
            return [];
        }

        $filePath = $service::resolveTempFilePath($file);
        $headers = $service::readCsvHeaders($filePath);

        if ($headers === []) {
            return [];
        }

        $columnMap = $get('columnMap') ?? $service::guessMapping($headers);
        $schoolId = app('current_tenant')->id;
        $missing = $service::missingStreams($filePath, $columnMap, $schoolId);

        if ($missing === []) {
            return [];
        }

        $rows = [];

        foreach ($missing as $key => $stream) {
            $rows[] = Forms\Components\Select::make("missingStreams.{$key}")
                ->label($stream['course'].' / '.$stream['section'])
                ->options([
                    'create' => __('Create this class stream'),
                    'skip' => __('Do not create (rows for it will be rejected)'),
                ])
                ->default('create')
                ->required();
        }

        return [
            Forms\Components\Section::make(__('Create missing class streams?'))
                ->description(__('These grades/streams are in your file but do not exist in the system yet. Create them automatically during this import, or leave them out.'))
                ->columns(2)
                ->schema($rows),
        ];
    }

    protected function runStudentImport(array $data, string $service, string $streamName): void
    {
        $file = $data['csv_file'] ?? null;
        $columnMap = $this->effectiveColumnMap($data, $service, $file);

        if (! $file) {
            Notification::make()
                ->title(__('No file uploaded'))
                ->body(__('Upload an Excel (XLSX/XLS) or CSV file in the first step before importing.'))
                ->danger()
                ->send();

            return;
        }

        $columns = $service::columns();

        $requiredMissing = collect($columns)
            ->filter(fn (array $column): bool => $column['required'])
            ->filter(fn (array $column, string $key): bool => blank($columnMap[$key] ?? null));

        // A "special class" register often has no grade column at all — the
        // stream is the class. Let those rows through; validation assigns the
        // Special Class level and the missing-stream prompt creates it.
        if ($requiredMissing->has('course') && filled($columnMap['section'] ?? null)) {
            $requiredMissing->forget('course');
        }

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
        $streamChoices = $data['missingStreams'] ?? [];
        $previousHandler = StudentCsvService::$missingStreamHandler;

        StudentCsvService::$missingStreamHandler = function (int $tenantId, string $courseName, string $sectionName) use ($streamChoices): bool {
            $key = StudentCsvService::streamKey($courseName, $sectionName);

            if (($streamChoices[$key] ?? 'create') === 'skip') {
                return false;
            }

            $level = str_contains(mb_strtolower($courseName), 'form')
                ? (preg_match('/form\s*([12])\b/i', $courseName) === 1 ? 'lower_secondary' : 'upper_secondary')
                : 'primary';

            $course = Course::withoutTenantScope()->firstOrCreate(
                ['school_id' => $tenantId, 'name' => $courseName],
                ['level' => $level],
            );

            Section::withoutTenantScope()->firstOrCreate(
                ['school_id' => $tenantId, 'course_id' => $course->id, 'name' => $sectionName],
                ['capacity' => 40],
            );

            return true;
        };

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
        } finally {
            StudentCsvService::$missingStreamHandler = $previousHandler;
        }

        $failures = collect($result['failures']);
        $skipped = (int) ($result['skipped'] ?? 0);
        $skippedNote = $skipped > 0 ? ' '.$skipped.' already-registered student(s) were skipped.' : '';

        if ($failures->isEmpty()) {
            if ((int) $result['success'] === 0) {
                Notification::make()
                    ->title(__($skipped > 0 ? 'No new students were imported' : 'No students were imported'))
                    ->body(__($skipped > 0
                        ? 'All valid rows were already registered in this school.'
                        : 'No data rows were found below the column headings. Fill in at least one student row and upload the file again.').$skippedNote)
                    ->warning()
                    ->duration(10)
                    ->send();

                return;
            }

            Notification::make()
                ->title(__('Import complete'))
                ->body('Imported '.$result['success'].' of '.$result['total'].' students.'.$skippedNote)
                ->success()
                ->send();

            return;
        }

        $reasonSummary = $failures
            ->flatMap(fn (array $f): array => $f['errors'])
            ->countBy()
            ->map(fn (int $count, string $reason): string => $reason.' ('.$count.')')
            ->implode('; ');

        $failedCsv = $this->buildFailedRowsCsv($result['failures']);

        Notification::make()
            ->title(__('Import finished with errors'))
            ->body('Imported '.$result['success'].' of '.$result['total'].' students. '.$failures->count().' row(s) were rejected.'.$skippedNote.' Reasons: '.$reasonSummary.'. Download the rejected rows below.')
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
