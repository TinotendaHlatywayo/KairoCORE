<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\GradingScaleResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\GradingScale;
use Modules\Admin\Services\PermissionRegistry;

class GradingScaleResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = GradingScale::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_assessments');
        }

        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Grading Scales';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grading Scale Profile')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g. ZIMSEC Ordinary Level, Cambridge O-Level')),
                    ]),

                Forms\Components\Section::make('Grading Points & Letter Ranges')
                    ->description(__('Define raw percentage intervals to map output symbols.'))
                    ->schema([
                        Forms\Components\Repeater::make('points')
                            ->relationship('points') // Maps directly to Modules/Academics/Models/GradingPoint
                            ->schema([
                                Forms\Components\TextInput::make('symbol')
                                    ->label(__('Letter Grade'))
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder(__('e.g. A, B, C, U')),
                                Forms\Components\TextInput::make('min_score')
                                    ->label(__('Minimum Score (%)'))
                                    ->numeric()
                                    ->required()
                                    ->default(0.00),
                                Forms\Components\TextInput::make('max_score')
                                    ->label(__('Maximum Score (%)'))
                                    ->numeric()
                                    ->required()
                                    ->default(100.00),
                                Forms\Components\TextInput::make('remark')
                                    ->label(__('Comment / Descriptor'))
                                    ->placeholder(__('e.g. Distinction, Merit, Credit')),
                            ])
                            ->columns(4)
                            ->defaultItems(1),
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
                Tables\Columns\TextColumn::make('points_count')
                    ->counts('points')
                    ->label(__('Grade Levels Defined'))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGradingScales::route('/'),
            'create' => Pages\CreateGradingScale::route('/create'),
            'edit' => Pages\EditGradingScale::route('/{record}/edit'),
        ];
    }
}
