<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlatformBillingMessageResource\Pages\ListPlatformBillingMessages;
use App\Models\User;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformBillingMessageTemplate;

/**
 * Editable wording for every automated billing message. The `key` maps a
 * template to a step of the lifecycle and must not change, so it is read-only
 * once created.
 */
class PlatformBillingMessageResource extends Resource
{
    protected static ?string $model = PlatformBillingMessageTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Billing & Subscriptions';

    protected static ?string $navigationLabel = 'Billing Messages';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 21;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->disabled()->dehydrated(false),
                TextInput::make('key')->disabled()->dehydrated(false),
                TextInput::make('subject')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('body')->required()->rows(8)->columnSpanFull()
                    ->helperText(__('Placeholders: {school_name} {amount} {currency} {billing_date} {plan_name} {days} {retention_months} {invoice_number} {subdomain} {url} {status}')),
                Toggle::make('send_platform_message')->label(__('Deliver in-app')),
                Toggle::make('send_email')->label(__('Deliver by email')),
                Toggle::make('is_active')->label(__('Active')),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->wrap(),
                TextColumn::make('key')->badge()->color('gray'),
                Tables\Columns\IconColumn::make('send_platform_message')->label(__('In-app'))->boolean(),
                Tables\Columns\IconColumn::make('send_email')->label(__('Email'))->boolean(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformBillingMessages::route('/'),
        ];
    }
}
