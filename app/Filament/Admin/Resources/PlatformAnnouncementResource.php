<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlatformAnnouncementResource\Pages\ListPlatformAnnouncements;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table; // Direct page import
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformAnnouncement;

class PlatformAnnouncementResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Communication');
    }

    protected static ?string $model = PlatformAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Announcements';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')->required(),
                Textarea::make('content')->required()->columnSpanFull(),
                Select::make('type')
                    ->options([
                        'info' => __('Informational News'),
                        'warning' => __('Minor Advisory Alert'),
                        'danger' => __('Urgent Notification'),
                        'maintenance' => __('Scheduled Maintenance Window'),
                    ])->required(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Toggle::make('is_active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('type')->badge()->color(fn (string $state): string => match ($state) {
                    'info' => 'info',
                    'warning' => 'warning',
                    'danger' => 'danger',
                    'maintenance' => 'gray',
                }),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                TextColumn::make('starts_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformAnnouncements::route('/'),
        ];
    }
}
