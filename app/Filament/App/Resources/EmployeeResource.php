<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\Imports\EmployeeImporter;
use App\Services\Csv\EmployeeCsvService;
use App\Services\ProfilePhotoService;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Forms;
use Filament\Forms\Components\Wizard as FormWizard;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Split as InfolistSplit;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAsset;
use Modules\HR\Models\SalaryGrade;
use Modules\HR\Models\SalaryGradeHistory;

class EmployeeResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'HR & Payroll';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormWizard::make([
                    FormWizard\Step::make('Personal Information')
                        ->schema([
                            Forms\Components\TextInput::make('first_name')->placeholder(__('e.g. Tinotenda'))->required(),
                            Forms\Components\TextInput::make('last_name')->placeholder(__('e.g. Hlatywayo'))->required(),
                            Forms\Components\TextInput::make('national_id')
                                ->required()
                                ->placeholder(__('e.g. 63-2541524C25'))
                                ->label(__('National ID / Passport')),
                            Forms\Components\Select::make('gender')
                                ->options(['male' => __('Male'), 'female' => __('Female')])
                                ->required(),
                            Forms\Components\DatePicker::make('date_of_birth')->required(),
                            Forms\Components\Select::make('marital_status')
                                ->options(['single' => __('Single'), 'married' => __('Married'), 'divorced' => __('Divorced')])
                                ->required(),
                            Forms\Components\TextInput::make('phone_number')->placeholder(__('e.g. +263786366855'))->tel()->required(),
                            Forms\Components\TextInput::make('email')->placeholder(__('e.g. tino@schoolcore.test'))->email()->required(),
                            Forms\Components\Textarea::make('physical_address')->placeholder(__('e.g. 1646 Mabvazuva, Harare'))->required(),
                            Forms\Components\TextInput::make('emergency_contact_name')->placeholder(__('e.g. Marwi Kandepa'))->required(),
                            Forms\Components\TextInput::make('emergency_contact_phone')->placeholder(__('e.g. +263786366855'))->tel()->required(),
                        ])->columns(2),

                    FormWizard\Step::make('Employment Details')
                        ->schema([
                            Forms\Components\TextInput::make('department')->placeholder(__('e.g. Academics'))->required(),
                            Forms\Components\TextInput::make('designation')->placeholder(__('e.g. English Teacher'))->required(),
                            Forms\Components\Select::make('role')
                                ->options([
                                    'Teacher' => __('Teacher'),
                                    'Support Staff' => __('Support Staff'),
                                    'Accountant' => __('Accountant'),
                                    'Administrator' => __('Administrator'),
                                    'Driver' => __('Driver'),
                                ])
                                ->placeholder(__('Select System Role'))
                                ->required(),
                            Forms\Components\Select::make('employment_type')
                                ->options([
                                    'Permanent' => __('Permanent'),
                                    'Contract' => __('Contract'),
                                    'Part-time' => __('Part-time'),
                                    'Volunteer' => __('Volunteer'),
                                ])
                                ->placeholder(__('Select Employment Type'))
                                ->reactive()->required(),
                            Forms\Components\DatePicker::make('contract_end_date')
                                ->placeholder(__('Select Contract End Date'))
                                ->visible(fn (callable $get) => $get('employment_type') === 'Contract')
                                ->required(),
                            Forms\Components\DatePicker::make('date_joined')
                                ->placeholder(__('Select Date Joined'))
                                ->required(),
                        ])->columns(2),

                    FormWizard\Step::make('Grade & Salary Assignment')
                        ->schema([
                            Forms\Components\Select::make('current_grade_id')
                                ->label(__('Salary Grade Scale'))
                                ->options(SalaryGrade::all()->pluck('name', 'id'))
                                ->placeholder(__('Select Assigned Salary Grade'))
                                ->required(),
                        ]),

                    FormWizard\Step::make('Family & Medical')
                        ->schema([
                            Forms\Components\KeyValue::make('spouse_details'),
                            Forms\Components\KeyValue::make('dependents'),
                            Forms\Components\KeyValue::make('next_of_kin'),
                            Forms\Components\Textarea::make('medical_conditions')->placeholder(__('e.g. None / Asthma logs')),
                            Forms\Components\Textarea::make('allergies')->placeholder(__('e.g. Penicillin / Nuts')),
                            Forms\Components\Textarea::make('emergency_medical_notes')->placeholder(__('e.g. Administer inhaler if breathing stops')),
                        ])->columns(2),

                    FormWizard\Step::make('Documents')
                        ->schema([
                            Forms\Components\FileUpload::make('avatar_path')
                                ->label(__('Passport Photograph'))
                                ->disk('public')
                                ->directory('hr/avatars')
                                ->image()
                                ->imageEditor()
                                ->imageEditorAspectRatios(['1:1'])
                                ->maxSize(2048)
                                ->placeholder(__('Drag & drop or Click to select Passport Photo'))
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                                ->imageResizeMode('force')
                                ->imageResizeTargetWidth('300')
                                ->imageResizeTargetHeight('300')
                                ->helperText(__('Max file size: 2MB. Optional. Defaults to default employee profile cover if omitted.')),

                            Forms\Components\FileUpload::make('document_contract')
                                ->label(__('Signed Contract (PDF)'))
                                ->disk('public')
                                ->directory('hr/documents')
                                ->placeholder(__('Drag & drop or Click to select Signed Contract PDF'))
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(5120),

                            Forms\Components\FileUpload::make('document_academic')
                                ->label(__('Academic Qualifications (PDF)'))
                                ->disk('public')
                                ->directory('hr/documents')
                                ->placeholder(__('Drag & drop or Click to select Academic Certificates'))
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(5120),

                            Forms\Components\FileUpload::make('document_professional')
                                ->label(__('Professional Certifications (PDF)'))
                                ->disk('public')
                                ->directory('hr/documents')
                                ->placeholder(__('Drag & drop or Click to select Professional Certifications'))
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(5120),
                        ])->columns(2),
                ])->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSplit::make([
                    InfolistGroup::make([
                        InfolistSection::make('Profile')
                            ->schema([
                                ImageEntry::make('avatar_path')
                                    ->label('')
                                    ->disk('public')
                                    ->circular()
                                    ->height(140)
                                    ->state(fn ($record) => asset(resolve_public_asset_path($record->avatar_path) ?? 'images/employee_profile.jpeg'))
                                    ->extraAttributes(['class' => 'flex justify-center mb-2']),
                                TextEntry::make('first_name')
                                    ->label('')
                                    ->formatStateUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')))
                                    ->weight('bold')
                                    ->size('lg')
                                    ->alignCenter()
                                    ->color('primary'),
                                TextEntry::make('designation')
                                    ->label('')
                                    ->icon('heroicon-o-briefcase')
                                    ->alignCenter(),
                                TextEntry::make('department')
                                    ->label('')
                                    ->icon('heroicon-o-building-office')
                                    ->alignCenter(),
                                TextEntry::make('status')
                                    ->label(__('Active Status'))
                                    ->badge()
                                    ->alignCenter()
                                    ->colors([
                                        'success' => 'active',
                                        'danger' => 'suspended',
                                        'warning' => 'on_leave',
                                        'info' => 'terminated',
                                    ]),
                            ]),
                        InfolistSection::make('Employment Snapshot')
                            ->schema([
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('employee_number')
                                            ->label(__('Employee ID'))
                                            ->icon('heroicon-o-identification'),
                                        TextEntry::make('national_id')
                                            ->label(__('National ID')),
                                        TextEntry::make('gender')
                                            ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '-'),
                                        TextEntry::make('date_of_birth')
                                            ->label(__('Date of Birth / Age'))
                                            ->date()
                                            ->suffix(fn ($record) => $record->date_of_birth ? ' ('.(int) $record->date_of_birth->age.' yrs)' : ''),
                                        TextEntry::make('employment_type')
                                            ->label(__('Employment Type')),
                                        TextEntry::make('date_joined')
                                            ->label(__('Date Joined'))
                                            ->date(),
                                        TextEntry::make('currentGrade.name')
                                            ->label(__('Salary Grade Scale')),
                                        TextEntry::make('role')
                                            ->label(__('Job Role')),
                                        TextEntry::make('email')
                                            ->label(__('Email'))
                                            ->icon('heroicon-o-envelope'),
                                        TextEntry::make('phone_number')
                                            ->label(__('Phone Contact'))
                                            ->icon('heroicon-o-phone'),
                                        TextEntry::make('physical_address')
                                            ->label(__('Living Address'))
                                            ->icon('heroicon-o-map-pin'),
                                    ]),
                            ]),
                    ])->grow(false),

                    InfolistGroup::make([
                        InfolistSection::make('Detailed Operational Metrics')
                            ->schema([
                                Tabs::make('Detailed Metrics')
                                    ->tabs([
                                        Tab::make('Employment & Pay Scale')
                                            ->schema([
                                                InfolistGrid::make(2)
                                                    ->schema([
                                                        TextEntry::make('department')->icon('heroicon-o-building-office'),
                                                        TextEntry::make('role'),
                                                        TextEntry::make('employment_type')->label(__('Type')),
                                                        TextEntry::make('date_joined')->date(),
                                                        TextEntry::make('contract_end_date')->label(__('Expiry Dates'))->date(),
                                                        TextEntry::make('currentGrade.name')->label(__('Salary Grade Bracket')),
                                                        TextEntry::make('currentGrade.base_salary')
                                                            ->label(__('Grade Base Salary'))
                                                            ->money('USD'),
                                                        TextEntry::make('suspension_reason')
                                                            ->label(__('Suspension / Termination Reason'))
                                                            ->visible(fn ($record) => in_array($record->status ?? null, ['suspended', 'terminated'], true)),
                                                    ]),
                                            ]),

                                        Tab::make('Family & Next of Kin')
                                            ->schema([
                                                InfolistGrid::make(2)
                                                    ->schema([
                                                        TextEntry::make('marital_status')->label(__('Marital Status')),
                                                        TextEntry::make('emergency_contact_name')->label(__('Emergency Kin Contact')),
                                                        TextEntry::make('emergency_contact_phone')->label(__('Kin Phone Number')),
                                                    ]),
                                                KeyValueEntry::make('spouse_details')
                                                    ->label(__('Spouse Details'))
                                                    ->keyLabel(__('Field'))
                                                    ->valueLabel(__('Value')),
                                                KeyValueEntry::make('dependents')
                                                    ->label(__('Dependents'))
                                                    ->keyLabel(__('Name'))
                                                    ->valueLabel(__('Detail')),
                                                KeyValueEntry::make('next_of_kin')
                                                    ->label(__('Next of Kin'))
                                                    ->keyLabel(__('Field'))
                                                    ->valueLabel(__('Value')),
                                            ]),

                                        Tab::make('Health & Medical Profiles')
                                            ->schema([
                                                InfolistGrid::make(1)
                                                    ->schema([
                                                        TextEntry::make('medical_conditions')->label(__('Medical Conditions Log')),
                                                        TextEntry::make('allergies')->label(__('Allergies List')),
                                                        TextEntry::make('emergency_medical_notes')->label(__('Immediate Medical Instructions')),
                                                    ]),
                                            ]),

                                        Tab::make('Uploaded Qualifications Documents')
                                            ->schema([
                                                InfolistGrid::make(3)
                                                    ->schema([
                                                        TextEntry::make('document_contract')
                                                            ->label(__('Signed Contract'))
                                                            ->formatStateUsing(fn ($state) => $state ? 'Download Contract PDF' : 'No Contract Uploaded')
                                                            ->url(fn ($state) => $state ? asset('storage/'.$state) : null)
                                                            ->openUrlInNewTab()
                                                            ->color('primary')
                                                            ->weight('bold')
                                                            ->icon('heroicon-o-document-arrow-down'),

                                                        TextEntry::make('document_academic')
                                                            ->label(__('Academic Qualifications'))
                                                            ->formatStateUsing(fn ($state) => $state ? 'Download Academic PDF' : 'No Certificates Uploaded')
                                                            ->url(fn ($state) => $state ? asset('storage/'.$state) : null)
                                                            ->openUrlInNewTab()
                                                            ->color('primary')
                                                            ->weight('bold')
                                                            ->icon('heroicon-o-document-arrow-down'),

                                                        TextEntry::make('document_professional')
                                                            ->label(__('Professional Certifications'))
                                                            ->formatStateUsing(fn ($state) => $state ? 'Download Professional PDF' : 'No Certifications Uploaded')
                                                            ->url(fn ($state) => $state ? asset('storage/'.$state) : null)
                                                            ->openUrlInNewTab()
                                                            ->color('primary')
                                                            ->weight('bold')
                                                            ->icon('heroicon-o-document-arrow-down'),
                                                    ]),
                                            ]),
                                    ]),
                            ]),
                    ])->grow(true),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')->searchable(),
                Tables\Columns\TextColumn::make('first_name')->searchable(),
                Tables\Columns\TextColumn::make('last_name')->searchable(),
                Tables\Columns\TextColumn::make('department'),
                Tables\Columns\TextColumn::make('designation'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                        'warning' => 'on_leave',
                    ]),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->label(__('Department'))
                    ->options([
                        'Administration' => 'Administration',
                        'Academics' => 'Academics',
                        'Finance' => 'Finance',
                        'Sports' => 'Sports & Extracurricular',
                    ]),
                SelectFilter::make('role')
                    ->label(__('Job Role'))
                    ->options([
                        'Teacher' => 'Teacher',
                        'Support Staff' => 'Support Staff',
                        'Accountant' => 'Accountant',
                        'Administrator' => 'Administrator',
                        'Driver' => 'Driver',
                    ]),
                SelectFilter::make('employment_type')
                    ->label(__('Employment Type'))
                    ->options([
                        'Permanent' => 'Permanent',
                        'Contract' => 'Contract',
                        'Part-time' => 'Part-time',
                        'Volunteer' => 'Volunteer',
                    ]),
                SelectFilter::make('current_grade_id')
                    ->label(__('Salary Grade Scale'))
                    ->relationship('currentGrade', 'name'),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'on_leave' => 'On Leave',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Action::make('changeStatus')
                    ->label(__('Status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'terminated' => 'Terminated',
                                'on_leave' => 'On Leave',
                            ])->required()->reactive(),
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label(__('Reason'))
                            ->required(fn (callable $get) => in_array($get('status'), ['suspended', 'terminated'])),
                    ])
                    ->action(function (Employee $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'suspension_reason' => $data['suspension_reason'] ?? null,
                        ]);

                        Notification::make()
                            ->title(__('Status Updated Successfully'))
                            ->success()
                            ->send();
                    }),

                Action::make('promote')
                    ->label(__('Promote'))
                    ->icon('heroicon-o-chevron-double-up')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('new_grade_id')
                            ->label(__('Target Salary Grade'))
                            ->options(SalaryGrade::all()->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\DatePicker::make('effective_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('reason')
                            ->label(__('Promotion/Change Reason'))
                            ->required(),
                    ])
                    ->action(function (Employee $record, array $data) {
                        $oldGradeId = $record->current_grade_id;

                        SalaryGradeHistory::create([
                            'school_id' => $record->school_id,
                            'employee_id' => $record->id,
                            'previous_grade_id' => $oldGradeId,
                            'new_grade_id' => $data['new_grade_id'],
                            'base_salary' => SalaryGrade::find($data['new_grade_id'])->base_salary,
                            'effective_date' => $data['effective_date'],
                            'reason' => $data['reason'],
                            'approved_by_id' => Auth::id(),
                        ]);

                        $record->update(['current_grade_id' => $data['new_grade_id']]);

                        Notification::make()
                            ->title(__('Promotion Recorded'))
                            ->success()
                            ->send();
                    }),

                Action::make('assignAsset')
                    ->label(__('Asset'))
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('asset_name')->required(),
                        Forms\Components\TextInput::make('serial_number')->required(),
                        Forms\Components\DatePicker::make('issued_date')->required()->default(now()),
                    ])
                    ->action(function (Employee $record, array $data) {
                        EmployeeAsset::create([
                            'school_id' => $record->school_id,
                            'employee_id' => $record->id,
                            'asset_name' => $data['asset_name'],
                            'serial_number' => $data['serial_number'],
                            'issued_date' => $data['issued_date'],
                            'status' => 'issued',
                        ]);

                        Notification::make()
                            ->title(__('Asset Assigned to Employee'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    BulkAction::make('bulk_export_selected_csv')
                        ->label(__('Export Selected to CSV'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $filename = 'employee-export-selection-'.now()->format('Y-m-d').'.csv';
                            $headers = [
                                'Content-type' => 'text/csv',
                                'Content-Disposition' => "attachment; filename={$filename}",
                                'Pragma' => 'no-cache',
                                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                                'Expires' => '0',
                            ];

                            $callback = function () use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['Employee ID', 'First Name', 'Last Name', 'Email', 'Phone', 'National ID', 'Department', 'Designation', 'Status']);

                                foreach ($records as $emp) {
                                    fputcsv($file, [
                                        $emp->employee_number,
                                        $emp->first_name,
                                        $emp->last_name,
                                        $emp->email,
                                        $emp->phone_number,
                                        $emp->national_id,
                                        $emp->department,
                                        $emp->designation,
                                        $emp->status,
                                    ]);
                                }
                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),

                    BulkAction::make('bulk_change_status')
                        ->label(__('Bulk Change Status'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'suspended' => 'Suspended',
                                    'on_leave' => 'On Leave',
                                ])->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $emp) {
                                $emp->update(['status' => $data['status']]);
                            }

                            Notification::make()
                                ->title(__('Status updated for selected employees'))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->content(fn () => view('filament.app.resources.employee.employee-cards'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['currentGrade', 'user']))
            ->paginated([8, 16, 24, 48, 'all'])
            ->defaultPaginationPageOption(8)
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
            'view' => ViewEmployee::route('/{record}'),
        ];
    }
}

class ListEmployees extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = EmployeeResource::class;

    protected static ?string $title = 'Employees';

    protected static function csvService(): string
    {
        return EmployeeCsvService::class;
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
        return view('filament.app.components.csv-import.page-actions', [
            'actions' => $this->getCachedHeaderActions(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $schoolId = Auth::user()->school_id;

        return [
            Actions\CreateAction::make()->label(__('New Employee'))->color('primary'),

            Actions\Action::make('download_import_template')
                ->label(__('Download CSV Template'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action(function () {
                    $filename = 'employee-import-template.csv';
                    $headers = [
                        'Content-type' => 'text/csv',
                        'Content-Disposition' => "attachment; filename={$filename}",
                        'Pragma' => 'no-cache',
                        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                        'Expires' => '0',
                    ];

                    $callback = function () {
                        $file = fopen('php://output', 'w');

                        fputcsv($file, [
                            'First Name',
                            'Last Name',
                            'Email',
                            'Phone Number',
                            'National ID',
                            'Department',
                            'Designation',
                            'Salary Grade Name',
                        ]);

                        fputcsv($file, [
                            'Tinotenda',
                            'Hlatywayo',
                            'twaynehlatywayo09@gmail.com',
                            '+263786366855',
                            '42-987654-Y-18',
                            'Academics',
                            'Biology Teacher',
                            'Educator Scale B',
                        ]);

                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }),

            // SINGLE-STEP ULTRA BULLETPROOF CSV IMPORTER
            ImportAction::make('import_employees_csv')
                ->label(__('Import Employees (CSV)'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->importer(EmployeeImporter::class)
                ->options([
                    'school_id' => $schoolId,
                ]),

            // EXPORT ALL — CSV / PDF
            ...$this->makeExportActions(),
        ];
    }
}
class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('removeProfilePhoto')
                ->label(__('Remove / Replace Photo'))
                ->icon('heroicon-o-photo')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('Remove Profile Photo'))
                ->modalDescription(__('The photo will be removed and the default placeholder will be used. The staff member will be notified and asked to upload a new passport-style photo.'))
                ->visible(fn () => filled($this->getRecord()->avatar_path) && $this->getRecord()->avatar_path !== 'images/employee_profile.jpeg')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label(__('Reason (optional)'))
                        ->placeholder(__('e.g. Photo was blurry / not a clear single face / not a passport-style photo'))
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    ProfilePhotoService::removeEmployeePhoto($this->getRecord(), $data['reason'] ?? null);

                    Notification::make()
                        ->title(__('Photo Removed'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getHeading(): string
    {
        $record = $this->getRecord();

        return trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: __('Employee Profile');
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label(__('Edit'))
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn () => EmployeeResource::getUrl('edit', ['record' => $this->getRecord()])),

            Actions\Action::make('removeProfilePhoto')
                ->label(__('Remove / Replace Photo'))
                ->icon('heroicon-o-photo')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('Remove Profile Photo'))
                ->modalDescription(__('The photo will be removed and the default placeholder will be used. The staff member will be notified and asked to upload a new passport-style photo.'))
                ->visible(fn () => filled($this->getRecord()->avatar_path) && $this->getRecord()->avatar_path !== 'images/employee_profile.jpeg')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label(__('Reason (optional)'))
                        ->placeholder(__('e.g. Photo was blurry / not a clear single face / not a passport-style photo'))
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    ProfilePhotoService::removeEmployeePhoto($this->getRecord(), $data['reason'] ?? null);

                    Notification::make()
                        ->title(__('Photo Removed'))
                        ->success()
                        ->send();
                }),

            Actions\Action::make('changeStatus')
                ->label(__('Status'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'suspended' => 'Suspended',
                            'terminated' => 'Terminated',
                            'on_leave' => 'On Leave',
                        ])->required()->reactive(),
                    Forms\Components\Textarea::make('suspension_reason')
                        ->label(__('Reason'))
                        ->required(fn (callable $get) => in_array($get('status'), ['suspended', 'terminated'])),
                ])
                ->action(function (array $data) {
                    $this->getRecord()->update([
                        'status' => $data['status'],
                        'suspension_reason' => $data['suspension_reason'] ?? null,
                    ]);

                    Notification::make()
                        ->title(__('Status Updated Successfully'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
