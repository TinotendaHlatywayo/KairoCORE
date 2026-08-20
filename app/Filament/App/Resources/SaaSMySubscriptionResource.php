<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SaaSMySubscriptionResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\SaaSSubscription;

class SaaSMySubscriptionResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Subscription & Billing');
    }

    protected static ?string $model = SaaSSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Subscription & Billing';

    protected static ?string $navigationLabel = 'My Subscription';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return Auth::check()
            && $user !== null
            && $user->school_id !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $schoolId = Auth::user()->school_id;

        return $table
            ->query(SaaSSubscription::query()->where('school_id', $schoolId))
            ->columns([
                Tables\Columns\TextColumn::make('plan.name')->label(__('Plan Name')),
                Tables\Columns\TextColumn::make('billing_period')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'grace_period' => 'warning',
                        'expired', 'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('Renewal Expiration Date'))
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('manage')
                    ->label(__('Go to Billing Portal'))
                    ->icon('heroicon-o-arrow-right-circle')
                    ->url(fn () => route('filament.app.pages.saas-billing-overview')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaaSMySubscriptions::route('/'),
        ];
    }
}
