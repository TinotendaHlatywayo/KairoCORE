<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SubjectResource\Pages;
use App\Services\Academic\AcademicValidationEngine;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\Subject;
use Modules\Admin\Services\PermissionRegistry;

class SubjectResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    protected static ?string $model = Subject::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_subjects');
        }

        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subject Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., Mathematics'))
                            ->helperText(__('Full subject name as it appears on reports.')),

                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., MATH'))
                            ->helperText(__('Short code used for identification.')),

                        Forms\Components\Select::make('type')
                            ->label(__('Subject Type'))
                            ->options([
                                'theory' => __('Theory'),
                                'practical' => __('Practical'),
                                'both' => __('Both (Theory & Practical)'),
                            ])
                            ->required()
                            ->helperText(__('Select how this subject is assessed.')),

                        Forms\Components\TextInput::make('credit_weight')
                            ->label(__('Credit Weight'))
                            ->numeric()
                            ->step(0.01)
                            ->default(1.00)
                            ->required()
                            ->helperText(__('Credit hours or weighting for this subject.')),

                        Forms\Components\Toggle::make('is_elective')
                            ->label(__('Is Elective'))
                            ->default(false)
                            ->helperText(__('Mark as elective subject if students can choose.')),
                    ])->columns(2),

                Forms\Components\Section::make('Workflow')
                    ->schema([
                        Forms\Components\Select::make('workflow_status')
                            ->label(__('Setup Status'))
                            ->options([
                                'pending' => __('Pending Setup'),
                                'in_progress' => __('In Progress'),
                                'complete' => __('Complete'),
                            ])
                            ->default('pending'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('success')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'theory' => 'info',
                        'practical' => 'warning',
                        'both' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_weight')
                    ->label(__('Credits'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_elective')
                    ->label(__('Elective'))
                    ->boolean(),

                Tables\Columns\BadgeColumn::make('workflow_status')
                    ->label(__('Stage'))
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'in_progress',
                        'success' => 'complete',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'theory' => __('Theory'),
                        'practical' => __('Practical'),
                        'both' => __('Both'),
                    ]),
                Tables\Filters\Filter::make('elective')
                    ->label(__('Electives Only'))
                    ->query(fn (Tables\Query $query) => $query->where('is_elective', true)),
                Tables\Filters\Filter::make('core')
                    ->label(__('Core Only'))
                    ->query(fn (Tables\Query $query) => $query->where('is_elective', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Subject $record) {
                        $validator = new AcademicValidationEngine($record->school_id);
                        $result = $validator->validateAction('delete_subject', ['subject_id' => $record->id]);

                        if (! $result['valid']) {
                            Notification::make()
                                ->title(__('Cannot delete subject'))
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
                                $result = $validator->validateAction('delete_subject', ['subject_id' => $record->id]);
                                if (! $result['valid']) {
                                    Notification::make()
                                        ->title('Cannot delete subject: '.$record->name)
                                        ->body(implode(' ', $result['errors']))
                                        ->danger()
                                        ->send();

                                    throw new Halt;
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulkMarkComplete')
                        ->label(__('Mark Complete'))
                        ->icon('heroicon-o-check')
                        ->action(function ($records) {
                            $records->each->update([
                                'workflow_status' => 'complete',
                                'workflow_completed_at' => now(),
                            ]);
                        }),
                    Tables\Actions\BulkAction::make('bulkExport')
                        ->label(__('Export'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            return redirect()->route('filament.app.resources.subjects.export', [
                                'ids' => $records->pluck('id'),
                            ]);
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
