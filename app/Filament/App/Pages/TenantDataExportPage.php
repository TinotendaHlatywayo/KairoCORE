<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantDataExportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ModulePermissionAccess;

    protected static ?string $navigationGroup = 'System Administration';

    public static function getNavigationGroup(): ?string
    {

        return __(static::$navigationGroup);

    }

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string $view = 'filament.app.pages.tenant-data-export-page';

    protected static ?int $navigationSort = 110;

    protected static ?string $navigationLabel = 'Data Export';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    // Re-labels the main screen heading to match
    protected static ?string $title = 'Data Export';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    // Aligned to standard dynamic $data payload array
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('dataset')
                    ->label(__('Choose Data Module to Export'))
                    ->options([
                        'students' => __('Student Directory'),
                        'employees' => __('Staff Directory (HR)'),
                        'invoices' => __('Billing Invoices Ledger'),
                        'student_attendances' => __('Student Attendance Logs'),
                        'staff_attendances' => __('Staff Daily Attendance Logs'),
                        'courses' => __('Form / Class Grade Levels'),
                        'sections' => __('Class Stream Allocations'),
                        'subjects' => __('Academic Curriculum Subjects'),
                        'clinic_visits' => __('Clinic Outpatient Visits'),
                        'library_books' => __('Library Books Registry'),
                        'inventory_items' => __('Inventory Stock Catalog'),
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function downloadDataset(): ?StreamedResponse
    {
        // Resilient Container-to-Session tenant ID resolver
        $school = app()->has('current_tenant') ? app('current_tenant') : session('current_tenant');
        $schoolId = $school->id ?? null;

        if (! $schoolId) {
            return null;
        }

        $formState = $this->form->getState();
        $table = $formState['dataset'];

        if (! Schema::hasTable($table)) {
            return null;
        }

        $columns = Schema::getColumnListing($table);
        $query = DB::table($table)
            ->where('school_id', $schoolId);

        return response()->streamDownload(function () use ($columns, $query) {
            $file = fopen('php://output', 'w');

            // Generate clean CSV headers
            $headers = array_map(function ($col) {
                return ucwords(str_replace('_', ' ', $col));
            }, $columns);

            fputcsv($file, $headers);

            // Stream rows lazily instead of loading the whole table into memory,
            // so large datasets (attendance logs, clinic visits) export without
            // exhausting PHP's memory limit.
            foreach ($query->lazyById() as $row) {
                $rowArray = (array) $row;
                $outputRow = [];
                foreach ($columns as $column) {
                    $outputRow[] = $rowArray[$column] ?? '';
                }
                fputcsv($file, $outputRow);
            }
            fclose($file);
        }, "export_{$table}_".now()->format('Ymd_His').'.csv');
    }
}
