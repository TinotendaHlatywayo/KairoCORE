<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\StudentResource;
use App\Models\User;
use App\Services\StudentCsvService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Student;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListStudents extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = StudentResource::class;

    protected static ?string $title = 'Students';

    protected array $extraBodyAttributes = ['data-page' => 'students'];

    public string $studentSort = 'date';

    public string $classFilter = '';

    public string $genderFilter = '';

    public string $statusFilter = '';

    public string $studentView = 'grid';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    /**
     * Directory totals for the header cards. They describe the whole school
     * roster, not the current search/filter slice, so they stay stable while
     * somebody is narrowing the grid.
     *
     * @return array{total: int, active: int, pending: int, female: int, male: int, femalePercent: int}
     */
    public function getStudentStats(): array
    {
        $row = Student::query()
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when users.activated_at is not null or users.account_status = ? then 1 else 0 end) as active', [User::STATUS_ACTIVE])
            ->selectRaw("sum(case when students.gender = 'female' then 1 else 0 end) as female")
            ->selectRaw("sum(case when students.gender = 'male' then 1 else 0 end) as male")
            ->first();

        $total = (int) ($row->total ?? 0);
        $active = (int) ($row->active ?? 0);
        $female = (int) ($row->female ?? 0);
        $male = (int) ($row->male ?? 0);

        return [
            'total' => $total,
            'active' => $active,
            'pending' => max(0, $total - $active),
            'female' => $female,
            'male' => $male,
            'femalePercent' => $total > 0 ? (int) round(($female / $total) * 100) : 0,
        ];
    }

    /**
     * Class select options, keyed as "course:section" so a stream is never
     * confused with the same stream name in another grade.
     *
     * @return array<string, string>
     */
    public function getClassOptions(): array
    {
        return Section::query()
            ->with('course')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (Section $section): array {
                $course = $section->course?->name ?? __('Unassigned');

                return [
                    $section->course_id.':'.$section->id => trim($course.' / '.$section->name),
                ];
            })
            ->all();
    }

    public function updatedStudentSort(): void
    {
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    public function updatedClassFilter(): void
    {
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    public function updatedGenderFilter(): void
    {
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    public function updatedStudentView(): void
    {
        $this->deselectAllTableRecords();
    }

    public function clearStudentFilters(): void
    {
        $this->tableSearch = '';
        $this->studentSort = 'date';
        $this->classFilter = '';
        $this->genderFilter = '';
        $this->statusFilter = '';
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    /**
     * Clicking a stat card narrows the roster. Pending/Active toggle the same
     * status filter, Female/Male toggle the same gender filter, and Total
     * returns to the full, unfiltered roster.
     */
    public function applyStatFilter(string $stat): void
    {
        if ($stat === 'total') {
            $this->clearStudentFilters();

            return;
        }

        if (in_array($stat, ['active', 'pending'], true)) {
            $this->statusFilter = $this->statusFilter === $stat ? '' : $stat;
        }

        if (in_array($stat, ['female', 'male'], true)) {
            $this->genderFilter = $this->genderFilter === $stat ? '' : $stat;
        }

        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        // The directory's search box is broader than the table columns (it also
        // matches class names), so it is applied in applyStudentQueryFilters().
        return $query;
    }

    public function getFilteredTableQuery(): Builder
    {
        return $this->applyStudentQueryFilters(parent::getFilteredTableQuery());
    }

    public function getFilteredSortedTableQuery(): Builder
    {
        $query = $this->getFilteredTableQuery();

        return match ($this->studentSort) {
            'name' => $query->orderBy('first_name')->orderBy('last_name')->orderBy('id'),
            'age' => $query->orderByDesc('date_of_birth')->orderBy('id'),
            'date' => $query->orderByDesc('admission_date')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    protected function applyStudentQueryFilters(Builder $query): Builder
    {
        $search = trim((string) $this->tableSearch);

        if ($search !== '') {
            $terms = preg_split('/\s+/u', $search) ?: [];

            foreach ($terms as $term) {
                $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

                $query->where(function (Builder $termQuery) use ($term): void {
                    $like = '%'.$term.'%';

                    $termQuery
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('student_id_number', 'like', $like)
                        ->orWhere('admission_number', 'like', $like)
                        ->orWhere('national_id', 'like', $like)
                        ->orWhereHas('currentEnrollment.course', fn (Builder $course) => $course->where('name', 'like', $like))
                        ->orWhereHas('currentEnrollment.section', fn (Builder $section) => $section->where('name', 'like', $like));
                });
            }
        }

        if (in_array($this->genderFilter, ['male', 'female', 'other'], true)) {
            $query->where('gender', $this->genderFilter);
        }

        if ($this->statusFilter === 'active') {
            $query->whereHas('user', fn (Builder $user) => $user
                ->where('account_status', User::STATUS_ACTIVE)
                ->orWhereNotNull('activated_at'));
        } elseif ($this->statusFilter === 'pending') {
            $query->whereDoesntHave('user', fn (Builder $user) => $user
                ->where('account_status', User::STATUS_ACTIVE)
                ->orWhereNotNull('activated_at'));
        }

        if ($this->classFilter !== '' && str_contains($this->classFilter, ':')) {
            [$courseId, $sectionId] = array_pad(explode(':', $this->classFilter, 2), 2, null);

            if ($courseId && $sectionId) {
                $query->whereHas('currentEnrollment', fn (Builder $enrollment) => $enrollment
                    ->where('course_id', (int) $courseId)
                    ->where('section_id', (int) $sectionId));
            }
        }

        return $query;
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
        return view('filament.app.resources.student.index-header', [
            'actions' => $this->getCachedHeaderActions(),
            'stats' => $this->getStudentStats(),
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
            ->modalSubmitAction(fn (StaticAction $action) => $action->disabled(fn (): bool => $this->isMountedImportFileMissing()))
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
