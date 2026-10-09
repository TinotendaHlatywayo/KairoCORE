<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Pages\PlatformBillingStatements;
use App\Filament\Admin\Resources\SchoolSubscriptionResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Services\BillingService;

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
                    ->description(__('Billing starts automatically once the tenant grace period (free days after registration) ends, then repeats every 30 days (monthly), 90 days (quarterly) or 365 days (yearly).'))
                    ->schema([
                        Forms\Components\Select::make('school_id')
                            ->label(__('Institution'))
                            // Only schools without a subscription can be picked,
                            // so a second row can never hit the unique index.
                            // The record's own school stays available on edit.
                            ->options(fn (?SaaSSubscription $record) => School::query()
                                ->where(function ($query) use ($record) {
                                    $query->doesntHave('saasSubscription');

                                    if ($record?->school_id) {
                                        $query->orWhere('id', $record->school_id);
                                    }
                                })
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('saas_plan_id')
                            ->label(__('Plan'))
                            ->options(SaaSPlan::where('is_active', true)->pluck('name', 'id'))
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
                Tables\Actions\Action::make('generate_invoice')
                    ->label(__('Generate invoice'))
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SaaSSubscription $record): bool => $record->plan !== null)
                    ->action(function (SaaSSubscription $record): void {
                        try {
                            $invoice = app(BillingService::class)->generateUpcomingInvoice($record);

                            Notification::make()
                                ->title(__('Invoice generated'))
                                ->body(__('Invoice :number created for :school.', [
                                    'number' => $invoice->invoice_number,
                                    'school' => $record->school?->name ?? __('the school'),
                                ]))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title(__('Unable to generate invoice'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('billing_documents')
                    ->label(__('Billing documents'))
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (SaaSSubscription $record): string => PlatformBillingStatements::getUrl(
                        ['school_id' => $record->school_id],
                        true,
                        'admin',
                    ))
                    ->openUrlInNewTab(),
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
