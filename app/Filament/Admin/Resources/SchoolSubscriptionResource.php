<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SchoolSubscriptionResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSSubscription;

class SchoolSubscriptionResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Subscriptions');
    }

    protected static ?string $model = SaaSSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Billing & Subscriptions';

    protected static ?string $navigationLabel = 'School Subscriptions';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription')
                    ->schema([
                        Forms\Components\Select::make('school_id')
                            ->label(__('Institution'))
                            ->options(School::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('saas_plan_id')
                            ->label(__('Plan'))
                            ->options(SaaSPlan::pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Select::make('billing_period')
                            ->options([
                                'monthly' => __('Monthly'),
                                'quarterly' => __('Quarterly'),
                                'yearly' => __('Yearly'),
                            ])
                            ->default('monthly')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'trialing' => __('Trialing'),
                                'active' => __('Active'),
                                'grace_period' => __('Grace Period'),
                                'expired' => __('Expired'),
                                'suspended' => __('Suspended'),
                            ])
                            ->default('trialing')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Billing Window')
                    ->schema([
                        Forms\Components\DateTimePicker::make('trial_ends_at'),
                        Forms\Components\DateTimePicker::make('starts_at'),
                        Forms\Components\DateTimePicker::make('ends_at'),
                        Forms\Components\DateTimePicker::make('grace_ends_at'),
                        Forms\Components\DatePicker::make('next_payment_date'),
                        Forms\Components\DatePicker::make('last_payment_date'),
                    ])->columns(3),

                Forms\Components\Section::make('Pricing & Ledger')
                    ->schema([
                        Forms\Components\TextInput::make('custom_price_monthly')
                            ->numeric()
                            ->prefix('$')
                            ->helperText(__('Leave empty to use the plan price.')),
                        Forms\Components\TextInput::make('credit_balance')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('auto_deactivate_after_days')
                            ->numeric()
                            ->default(5),
                        Forms\Components\TextInput::make('dunning_days_before')
                            ->numeric()
                            ->default(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')
                    ->label(__('Institution'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label(__('Plan'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_period')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'grace_period' => 'warning',
                        'suspended' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('billing_amount')
                    ->label(__('Billing Amount'))
                    ->money('USD')
                    ->state(fn (SaaSSubscription $record): string => (string) $record->getBillingAmount()),
                Tables\Columns\TextColumn::make('next_payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label(__('Days Left'))
                    ->state(fn (SaaSSubscription $record): string => (string) $record->getDaysRemaining()),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'grace_period' => 'Grace Period',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                    ]),
                Tables\Filters\SelectFilter::make('saas_plan_id')
                    ->label(__('Plan'))
                    ->options(SaaSPlan::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchoolSubscriptions::route('/'),
            'create' => Pages\CreateSchoolSubscription::route('/create'),
            'edit' => Pages\EditSchoolSubscription::route('/{record}/edit'),
        ];
    }
}
