<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\ReportingWorkflowResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\ReportTemplate;
use Modules\Academics\Models\Section;

class ReportingWorkflowResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Reports & Intelligence');
    }

    protected static ?string $model = ReportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Reports & Intelligence';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Reporting Workflow');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Reporting Workflow')
                    ->tabs([
                        Tab::make('Template')
                            ->label(__('1. Template Design'))
                            ->schema([
                                Forms\Components\Section::make('Template Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->placeholder(__('e.g., Term 2 Report Card 2024')),
                                        Forms\Components\Select::make('design_theme')
                                            ->label(__('Design Theme'))
                                            ->options(ReportTemplate::$themes)
                                            ->required(),
                                        Forms\Components\Select::make('target_level')
                                            ->label(__('Target Level'))
                                            ->options(ReportTemplate::$brackets)
                                            ->required(),
                                        Forms\Components\Select::make('scope_type')
                                            ->label(__('Scope'))
                                            ->options(ReportTemplate::$scopes)
                                            ->required(),
                                        Forms\Components\Select::make('course_id')
                                            ->label(__('Course (if scope=course)'))
                                            ->options(Course::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                            ->searchable(),
                                        Forms\Components\Select::make('section_id')
                                            ->label(__('Section (if scope=section)'))
                                            ->options(Section::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                            ->searchable(),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('Active'))
                                            ->default(true),
                                    ])->columns(2),

                                Forms\Components\Section::make('Layout Configuration')
                                    ->schema([
                                        Forms\Components\KeyValue::make('layout_config')
                                            ->label(__('Layout Config'))
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add Config'),
                                    ]),
                            ]),

                        Tab::make('Scope & Subjects')
                            ->label(__('2. Scope & Subjects'))
                            ->schema([
                                Forms\Components\Section::make('Scope Configuration')
                                    ->description(__('Configure which students this report applies to'))
                                    ->schema([
                                        Forms\Components\Select::make('target_level')
                                            ->label(__('Educational Level'))
                                            ->options(ReportTemplate::$brackets)
                                            ->required(),
                                        Forms\Components\Select::make('scope_type')
                                            ->label(__('Scope Type'))
                                            ->options(ReportTemplate::$scopes)
                                            ->required()
                                            ->live(),
                                        Forms\Components\Select::make('course_id')
                                            ->label(__('Course/Grade'))
                                            ->options(Course::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                            ->searchable()
                                            ->visible(fn (Forms\Get $get) => $get('scope_type') === 'course'),
                                        Forms\Components\Select::make('section_id')
                                            ->label(__('Class Stream'))
                                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                                            ->searchable()
                                            ->visible(fn (Forms\Get $get) => $get('scope_type') === 'section'),
                                    ])->columns(2),
                            ]),
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
                Tables\Columns\BadgeColumn::make('design_theme')
                    ->colors([
                        'gray' => 'classic_line',
                        'info' => 'modern_grid',
                        'success' => 'elegant_editorial',
                        'warning' => 'minimal_compact',
                        'primary' => 'royal_crest',
                    ])
                    ->label(__('Theme')),
                Tables\Columns\BadgeColumn::make('target_level')
                    ->colors([
                        'gray' => 'ecd',
                        'info' => 'primary',
                        'success' => 'lower_secondary',
                        'warning' => 'upper_secondary',
                        'primary' => 'all',
                    ])
                    ->label(__('Level')),
                Tables\Columns\BadgeColumn::make('scope_type')
                    ->colors([
                        'gray' => 'level',
                        'info' => 'course',
                        'success' => 'section',
                    ])
                    ->label(__('Scope')),
                Tables\Columns\TextColumn::make('course.name')
                    ->label(__('Course'))
                    ->placeholder(__('—')),
                Tables\Columns\TextColumn::make('section.name')
                    ->label(__('Section'))
                    ->placeholder(__('—')),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label(__('Active'))
                    ->options([
                        '1' => __('Active'),
                        '0' => __('Inactive'),
                    ]),
                Tables\Filters\SelectFilter::make('design_theme')
                    ->options(ReportTemplate::$themes),
                Tables\Filters\SelectFilter::make('target_level')
                    ->options(ReportTemplate::$brackets),
                Tables\Filters\SelectFilter::make('scope_type')
                    ->options(ReportTemplate::$scopes),
                Tables\Filters\SelectFilter::make('course_id')
                    ->label(__('Course'))
                    ->relationship('course', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate')
                    ->label(__('Generate Reports'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(function ($record) {
                        Notification::make()->title('Report generation queued for '.$record->name)->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('Activate'))
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('Deactivate'))
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportingWorkflows::route('/'),
            'create' => Pages\CreateReportingWorkflow::route('/create'),
            'edit' => Pages\EditReportingWorkflow::route('/{record}/edit'),
        ];
    }
}
