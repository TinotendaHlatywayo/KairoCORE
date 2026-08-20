<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SaaSTransactionResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\SaaSTransaction;

class SaaSTransactionResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Platform Control');
    }

    protected static ?string $model = SaaSTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Platform Control';

    protected static ?string $navigationLabel = 'SaaS Transactions';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_reference')->searchable(),
                TextColumn::make('school.name')->label(__('School Name'))->searchable(),
                TextColumn::make('amount')->label(__('Revenues'))->prefix('$'),
                TextColumn::make('payment_gateway_key')->label(__('Gateway')),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaaSTransactions::route('/'),
        ];
    }
}
