<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AcademicYearResource\Pages;
use App\Services\Academic\AcademicValidationEngine;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Academics\Models\AcademicYear;
use Modules\Admin\Services\PermissionRegistry;

class AcademicYearResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    protected static ?string $model = AcademicYear::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_calendar');
        }

        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Academics';

    public static function getNavigationLabel(): string
    {
        return term('label.academic_year', 'Academic Calendar');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Calendar Year Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder(__('e.g., Year 2026'))
                            ->maxLength(255)
                            ->helperText(__('Name this academic year uniquely.')),

                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->helperText(__('Start date of the academic year.')),

                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->helperText(__('End date of the academic year.'))
                            ->after('start_date'),
                    ])->columns(3),

                Forms\Components\Section::make('Workflow Status')
                    ->description(__('Track the progress of this academic year setup.'))
                    ->schema([
                        Forms\Components\Select::make('workflow_status')
                            ->label(__('Setup Stage'))
                            ->options([
                                'draft' => __('Draft'),
                                'in_progress' => __('In Progress'),
                                'review' => __('Review'),
                                'active' => __('Active'),
                                'completed' => __('Completed'),
                                'archived' => __('Archived'),
                            ])
                            ->default('draft')
                            ->required()
                            ->helperText(__('Current stage in the academic setup workflow.')),

                        Forms\Components\DateTimePicker::make('workflow_completed_at')
                            ->label(__('Completed At'))
                            ->helperText(__('When this academic year was fully configured.')),
                    ])->columns(2),

                Forms\Components\Section::make('Associated Terms / Semesters')
                    ->description(__('Define the school term schedules for this calendar year.'))
                    ->schema([
                        Forms\Components\Repeater::make('terms')
                            ->relationship('terms')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder(__('e.g., Term 1 or Semester 1')),
                                Forms\Components\DatePicker::make('start_date')
                                    ->required(),
                                Forms\Components\DatePicker::make('end_date')
                                    ->required()
                                    ->after('start_date'),
                            ])
                            ->grid(3)
                            ->defaultItems(3),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->start_date?->format('M d').' - '.$record->end_date?->format('M d')),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('Current Active')),

                Tables\Columns\BadgeColumn::make('workflow_status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'in_progress',
                        'warning' => 'review',
                        'success' => 'active',
                        'success' => 'completed',
                        'gray' => 'archived',
                    ])
                    ->label(__('Stage')),

                Tables\Columns\TextColumn::make('terms_count')
                    ->counts('terms')
                    ->label(__('Terms')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('workflow_status')
                    ->options([
                        'draft' => __('Draft'),
                        'in_progress' => __('In Progress'),
                        'active' => __('Active'),
                        'completed' => __('Completed'),
                        'archived' => __('Archived'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('workflow')
                    ->label(__('Workflow'))
                    ->icon('heroicon-o-play')
                    ->action(function ($record) {
                        redirect()->route('filament.app.pages.academic-operations-center');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function (AcademicYear $record) {
                        $validator = new AcademicValidationEngine($record->school_id);
                        $result = $validator->validateAction('delete_academic_year', ['academic_year_id' => $record->id]);

                        if (! $result['valid']) {
                            Notification::make()
                                ->title(__('Cannot delete academic year'))
                                ->body(implode(' ', $result['errors']))
                                ->danger()
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $validator = new AcademicValidationEngine(auth()->user()?->school_id);

                            foreach ($records as $record) {
                                $result = $validator->validateAction('delete_academic_year', ['academic_year_id' => $record->id]);
                                if (! $result['valid']) {
                                    Notification::make()
                                        ->title('Cannot delete academic year: '.$record->name)
                                        ->body(implode(' ', $result['errors']))
                                        ->danger()
                                        ->send();

                                    throw new Halt;
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('setActive')
                        ->label(__('Set Active'))
                        ->icon('heroicon-o-check')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        }),
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('Archive'))
                        ->icon('heroicon-o-archive-box')
                        ->action(function ($records) {
                            $validator = new AcademicValidationEngine(auth()->user()?->school_id);

                            foreach ($records as $record) {
                                $result = $validator->validateAction('archive_active_year', ['academic_year_id' => $record->id]);
                                if (! $result['valid']) {
                                    Notification::make()
                                        ->title('Cannot archive '.$record->name)
                                        ->body(implode(' ', $result['errors']))
                                        ->danger()
                                        ->send();

                                    throw new Halt;
                                }
                            }

                            $records->each->update(['workflow_status' => 'archived']);
                        }),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }

    public static function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label(__('All Years'))
                ->icon('heroicon-o-calendar-days'),
            'active' => Tab::make()
                ->label(__('Active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->icon('heroicon-o-check-circle'),
            'workflow' => Tab::make()
                ->label(__('In Progress'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('workflow_status', ['in_progress', 'review']))
                ->icon('heroicon-o-cog'),
        ];
    }
}
