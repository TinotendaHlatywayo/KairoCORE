<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\FeeStructureResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\FeeStructure;

class FeeStructureResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = FeeStructure::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Fee Structures';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        $ratingOptions = [
            'outstanding' => 'Outstanding (10.0)',
            'excellent' => 'Excellent (8.5)',
            'very_good' => 'Very Good (7.0)',
            'good' => 'Good (5.5)',
            'satisfactory' => 'Satisfactory (4.0)',
            'needs_improvement' => 'Needs Improvement (2.0)',
        ];

        return $form
            ->schema([
                Forms\Components\Section::make('Fee Structure Parameters')
                    ->schema([
                        Forms\Components\Select::make('fee_category_id')
                            ->relationship('feeCategory', 'name')
                            ->required(),

                        Forms\Components\Select::make('scope_type')
                            ->label(__('Billing Scope'))
                            ->options(FeeStructure::$scopes)
                            ->default('single')
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'name')
                            ->label(__('Target Class Level'))
                            ->searchable()
                            ->preload()
                            ->required(fn (Forms\Get $get) => $get('scope_type') === 'single')
                            ->visible(fn (Forms\Get $get) => $get('scope_type') === 'single')
                            ->placeholder(__('Select Class Level...')),

                        // Automatically defaults to active year
                        Forms\Components\Select::make('academic_year_id')
                            ->label(__('Academic Year'))
                            ->options(function () {
                                return AcademicYear::where('is_active', true)->pluck('name', 'id');
                            })
                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                            ->required()
                            ->live(),

                        // FIX: Limits terms strictly to the active academic year to prevent duplicates
                        Forms\Components\Select::make('term_id')
                            ->label(__('Term'))
                            ->options(function (Forms\Get $get) {
                                $yearId = $get('academic_year_id') ?? AcademicYear::where('is_active', true)->first()?->id;
                                if (! $yearId) {
                                    return [];
                                }

                                return Term::where('academic_year_id', $yearId)
                                    ->get()
                                    ->mapWithKeys(function ($term) {
                                        return [$term->id => ucwords(strtolower($term->name))];
                                    });
                            })
                            ->required()
                            ->preload()
                            ->placeholder(__('Select Term...')),

                        Forms\Components\Select::make('currency')
                            ->options(['USD' => __('USD'), 'ZiG' => __('ZiG')])
                            ->default('USD')
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->placeholder(__('e.g. 150.00')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('feeCategory.name')->label(__('Category')),

                Tables\Columns\TextColumn::make('scope_type')
                    ->label(__('Billing Scope'))
                    ->formatStateUsing(fn ($state) => FeeStructure::$scopes[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('course.name')
                    ->label(__('Class Level'))
                    ->default('All (Scoped)'),

                Tables\Columns\TextColumn::make('term.name')
                    ->label(__('Term'))
                    ->formatStateUsing(fn ($state) => ucwords(strtolower($state))),

                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('amount')->money(fn ($record) => $record->currency),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeeStructures::route('/'),
            'create' => Pages\CreateFeeStructure::route('/create'),
            'edit' => Pages\EditFeeStructure::route('/{record}/edit'),
        ];
    }
}
