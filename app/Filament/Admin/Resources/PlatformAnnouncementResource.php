<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlatformAnnouncementResource\Pages\ListPlatformAnnouncements;
use App\Models\School;
use App\Models\User;
use App\Services\AudienceTargeting;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table; // Direct page import
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformAnnouncement;
use Modules\SaaS\Models\SaaSPlan;

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
                Select::make('audience')
                    ->label(__('Audience'))
                    ->options([
                        PlatformAnnouncement::AUDIENCE_ALL => __('All tenants — every role'),
                        PlatformAnnouncement::AUDIENCE_ADMINS => __('System administrators only'),
                        PlatformAnnouncement::AUDIENCE_TENANTS => __('Selected tenants — everyone'),
                        PlatformAnnouncement::AUDIENCE_USERS => __('Specific people'),
                    ])
                    ->default(PlatformAnnouncement::AUDIENCE_ALL)
                    ->required()
                    ->live()
                    ->helperText(__('Who this announcement is addressed to.')),
                Select::make('target_school_ids')
                    ->label(__('Tenants'))
                    ->options(fn (): array => School::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('audience'), [
                        PlatformAnnouncement::AUDIENCE_TENANTS,
                        PlatformAnnouncement::AUDIENCE_ADMINS,
                    ], true))
                    ->required(fn (Get $get): bool => $get('audience') === PlatformAnnouncement::AUDIENCE_TENANTS)
                    ->helperText(fn (Get $get): string => $get('audience') === PlatformAnnouncement::AUDIENCE_ADMINS
                        ? __('Optional — leave empty to reach the system administrators of every tenant.')
                        : __('The tenants this announcement is addressed to.')),
                Select::make('target_roles')
                    ->label(__('Roles'))
                    ->options(fn (): array => AudienceTargeting::roleOptions())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText(__('Optional — narrow the audience to one or more of the roles a school sees on its Roles page.')),
                Select::make('target_user_ids')
                    ->label(__('People'))
                    ->multiple()
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('audience') === PlatformAnnouncement::AUDIENCE_USERS)
                    ->required(fn (Get $get): bool => $get('audience') === PlatformAnnouncement::AUDIENCE_USERS)
                    ->options(fn (Get $get): array => self::personOptions())
                    ->getSearchResultsUsing(fn (string $search): array => self::personOptions($search))
                    ->getOptionLabelsUsing(fn (array $values): array => User::withoutTenantScope()->whereIn('id', $values)->pluck('name', 'id')->all())
                    ->helperText(__('The specific people this announcement is addressed to.')),
                Select::make('target_plans')
                    ->label(__('Plans'))
                    ->options(fn (): array => SaaSPlan::query()->orderBy('name')->pluck('name', 'slug')->all())
                    ->multiple()
                    ->helperText(__('Optional — leave empty to reach every plan.')),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Toggle::make('show_in_banner')
                    ->label(__('Show as a banner'))
                    ->helperText(__('Show this announcement in the tenant\'s top bar.'))
                    ->default(false),
                Toggle::make('is_active')->default(true),
            ]);
    }

    /**
     * The accounts an announcement can be addressed to by name.
     *
     * @return array<int, string>
     */
    protected static function personOptions(?string $search = null): array
    {
        $query = User::withoutTenantScope()
            ->whereNotNull('school_id')
            ->where('account_status', User::STATUS_ACTIVE)
            ->orderBy('name');

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return $query->limit(50)->pluck('name', 'id')->all();
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
                TextColumn::make('audience')
                    ->label(__('Audience'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        PlatformAnnouncement::AUDIENCE_ADMINS => __('System administrators'),
                        PlatformAnnouncement::AUDIENCE_TENANTS => __('Selected tenants'),
                        PlatformAnnouncement::AUDIENCE_USERS => __('Specific people'),
                        default => __('All tenants'),
                    }),
                Tables\Columns\IconColumn::make('show_in_banner')->boolean(),
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
