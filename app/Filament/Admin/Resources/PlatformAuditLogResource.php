<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlatformAuditLogResource\Pages\ListPlatformAuditLogs;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table; // Direct page import
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformAuditLog;

class PlatformAuditLogResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Operations');
    }

    protected static ?string $model = PlatformAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Platform Audits';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

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
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label(__('Super Admin')),
                TextColumn::make('action')->searchable(),
                TextColumn::make('ip_address')->label(__('Request IP')),
                TextColumn::make('user_agent')->limit(30),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->slideOver(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformAuditLogs::route('/'),
        ];
    }
}
