<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SaaSPlanResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\SaaS\Models\SaaSPlan;

class SaaSPlanResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Subscriptions');
    }

    protected static ?string $model = SaaSPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationGroup = 'Billing & Subscriptions';

    protected static ?string $navigationLabel = 'Subscription Plans';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return Auth::check()
            && $user !== null
            && $user->school_id === null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(table: SaaSPlan::class, ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Fee Rates & Timeframes')
                    ->schema([
                        Forms\Components\TextInput::make('price_monthly')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00)
                            ->required(),
                        Forms\Components\TextInput::make('price_quarterly')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00)
                            ->required(),
                        Forms\Components\TextInput::make('price_yearly')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00)
                            ->required(),
                        Forms\Components\TextInput::make('currency')
                            ->default('USD')
                            ->required(),
                        Forms\Components\TextInput::make('trial_days')
                            ->numeric()
                            ->default(14)
                            ->required(),
                        Forms\Components\TextInput::make('grace_days')
                            ->numeric()
                            ->default(7)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Feature Allotments & Limits')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->relationship('features')
                            ->schema([
                                Forms\Components\TextInput::make('feature_key')
                                    ->required()
                                    ->placeholder(__('e.g., max_students, api_access')),
                                Forms\Components\TextInput::make('feature_value')
                                    ->required()
                                    ->placeholder(__('e.g., 500, true')),
                            ])
                            ->grid(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Operational Visibility')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_popular')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('price_monthly')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('price_quarterly')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('price_yearly')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('trial_days')->label(__('Trial (Days)')),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\IconColumn::make('is_popular')->boolean(),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label(__('Active Subscribers')),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_popular'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaaSPlans::route('/'),
            'create' => Pages\CreateSaaSPlan::route('/create'),
            'edit' => Pages\EditSaaSPlan::route('/{record}/edit'),
        ];
    }
}
