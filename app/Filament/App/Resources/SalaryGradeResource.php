<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Services\Csv\SalaryGradeCsvService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Modules\HR\Models\SalaryGrade;

class SalaryGradeResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    protected static ?string $model = SalaryGrade::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?string $modelLabel = 'Salary Grade';

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('base_salary')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('housing_allowance')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('transport_allowance')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\TextInput::make('duty_allowance')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\Toggle::make('overtime_eligible')
                    ->required(),

                Forms\Components\Section::make(__('Custom Allowances & Deductions'))
                    ->schema([
                        Forms\Components\Repeater::make('custom_allowances')
                            ->label(__('Additional Allowances'))
                            ->schema([
                                Forms\Components\TextInput::make('name')->required()->label(__('Allowance Name')),
                                Forms\Components\TextInput::make('amount')->numeric()->prefix('$')->required()->label(__('Amount')),
                            ])->columns(2),
                        Forms\Components\Repeater::make('custom_deductions')
                            ->label(__('Additional Deductions'))
                            ->schema([
                                Forms\Components\TextInput::make('name')->required()->label(__('Deduction Name')),
                                Forms\Components\TextInput::make('amount')->numeric()->prefix('$')->required()->label(__('Amount')),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('base_salary')->money('USD'),
                Tables\Columns\TextColumn::make('housing_allowance')->money('USD'),
                Tables\Columns\TextColumn::make('transport_allowance')->money('USD'),
                Tables\Columns\IconColumn::make('overtime_eligible')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('clone')
                    ->label(__('Clone Scale'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label(__('New Grade Name'))
                            ->required(),
                    ])
                    ->action(function (SalaryGrade $record, array $data) {
                        $clone = $record->replicate();
                        $clone->name = $data['name'];
                        $clone->save();

                        Notification::make()
                            ->title(__('Salary Grade Cloned'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('exportPdf')
                    ->label(__('Export Selected as PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('modules.hr.salary-grades-pdf', [
                            'school' => current_tenant(),
                            'grades' => $records,
                        ])->setPaper('a4', 'landscape');

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Salary-Grades-Report.pdf',
                            ['Content-Type' => 'application/pdf']
                        );
                    }),
                Tables\Actions\BulkAction::make('exportCsv')
                    ->label(__('Export Selected as CSV'))
                    ->icon('heroicon-o-document-text')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $csv = "Grade Name,Base Salary,Housing Allowance,Transport Allowance,Overtime Eligible\n";
                        foreach ($records as $r) {
                            $csv .= "\"{$r->name}\",\"{$r->base_salary}\",\"{$r->housing_allowance}\",\"{$r->transport_allowance}\",\"" . ($r->overtime_eligible ? 'Yes' : 'No') . "\"\n";
                        }
                        return response()->streamDownload(
                            fn () => print($csv),
                            'Salary-Grades.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalaryGrades::route('/'),
            'create' => CreateSalaryGrade::route('/create'),
            'edit' => EditSalaryGrade::route('/{record}/edit'),
        ];
    }
}

class ListSalaryGrades extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = SalaryGradeResource::class;

    protected static function csvService(): string
    {
        return SalaryGradeCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Salary Grade')),
            ...$this->csvBulkActions(),
        ];
    }
}
class CreateSalaryGrade extends CreateRecord
{
    protected static string $resource = SalaryGradeResource::class;
}
class EditSalaryGrade extends EditRecord
{
    protected static string $resource = SalaryGradeResource::class;
}
