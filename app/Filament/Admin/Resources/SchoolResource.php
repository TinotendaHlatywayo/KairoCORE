<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SchoolResource\Pages;
use App\Filament\Admin\Resources\SchoolResource\RelationManagers\UsersRelationManager;
use App\Models\School;
use App\Models\User;
use App\Services\AccountActivationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Services\TenantImpersonationEngine;

class SchoolResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Tenants');
    }

    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?string $navigationLabel = 'Institutions';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (config('tenancy.mode') === 'single') {
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Institution Profile')
                    ->description(__('Core organizational identifiers and contact info'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Institution Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subdomain')
                            ->label(__('Subdomain Workspace'))
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->suffix('.'.parse_url(config('app.url'), PHP_URL_HOST)),
                        Forms\Components\TextInput::make('country')
                            ->label(__('Country'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('physical_address')
                            ->label(__('Physical Address'))
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('Primary Phone'))
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Select::make('language')
                            ->label(__('System Language'))
                            ->options([
                                'english' => __('English'),
                                'spanish' => __('Spanish'),
                                'french' => __('French'),
                                'portuguese' => __('Portuguese'),
                                'shona' => __('Shona'),
                                'swahili' => __('Swahili'),
                            ])
                            ->default('english'),
                        Forms\Components\Select::make('institution_type')
                            ->label(__('Type of Institution'))
                            ->options([
                                'primary' => __('Primary'),
                                'secondary' => __('Secondary'),
                                'tertiary' => __('Tertiary'),
                                'both' => __('Both Primary and Secondary'),
                                'other' => __('Other'),
                            ])
                            ->default('secondary'),
                        Forms\Components\TextInput::make('other_institution_type')
                            ->label(__('Specify Other Institution Type'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('motto')
                            ->label(__('Institution Motto'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email_address')
                            ->label(__('Official Email'))
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_number')
                            ->label(__('Secondary Phone'))
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website_url')
                            ->label(__('Website URL'))
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Registration Contact')
                    ->description(__('The person who submitted the registration (from the pending administrator account)'))
                    ->schema([
                        Forms\Components\Placeholder::make('registered_contact_name')
                            ->label(__('Registered Contact'))
                            ->content(fn (School $record): string => $record->exists && $record->users()->exists()
                                ? $record->users()->orderBy('id')->value('name')
                                : __('—')),
                        Forms\Components\Placeholder::make('registered_contact_email')
                            ->label(__('Contact Email'))
                            ->content(fn (School $record): string => $record->exists && $record->users()->exists()
                                ? $record->users()->orderBy('id')->value('email')
                                : __('—')),
                    ])->columns(2),

                Forms\Components\Section::make('Platform Status & Setup')
                    ->description(__('Access rules, trial periods, and academic presets'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options([
                                'pending' => __('Pending Approval'),
                                'active' => __('Active Workspace'),
                                'suspended' => __('Suspended (Locked)'),
                                'expired' => __('Expired Subscription'),
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Select::make('region')
                            ->label(__('Regional System Preset'))
                            ->options([
                                'zimbabwe' => __('Zimbabwe (ZWG/USD Primary)'),
                                'south_africa' => __('South Africa (ZAR/USD Primary)'),
                                'us' => __('United States / International (USD)'),
                            ])
                            ->default('zimbabwe')
                            ->required(),
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label(__('Trial Expiration Date')),
                    ])->columns(3),

                Forms\Components\Section::make('Branding & Logos')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label(__('Official Institution Logo'))
                            ->image()
                            ->directory('school-logos'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Institution'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('subdomain')
                    ->label(__('Workspace'))
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label(__('Country'))
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('region')
                    ->label(__('Region'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label(__('Trial Ends'))
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label(__('Admins')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Registered'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('Pending Approval'),
                        'active' => __('Active'),
                        'suspended' => __('Suspended'),
                        'expired' => __('Expired'),
                    ]),
                Tables\Filters\SelectFilter::make('region')
                    ->options([
                        'zimbabwe' => 'Zimbabwe',
                        'south_africa' => 'South Africa',
                        'us' => 'United States',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve & Send Activation'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (School $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (School $record) {
                        $adminUser = $record->users()
                            ->where('requested_role', 'administrator')
                            ->where('account_status', User::STATUS_PENDING)
                            ->first() ?? $record->users()->where('account_status', User::STATUS_PENDING)->first();

                        $token = null;
                        if ($adminUser) {
                            // Issued via the secure account-activation service:
                            // single-use, expires after 48 hours, cryptographically random.
                            $token = app(AccountActivationService::class)->issueAndSend($adminUser);

                            $adminUser->forceFill([
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                            ])->save();
                        }

                        $record->update([
                            'status' => 'pending', // Still pending — becomes 'active' when the contact completes activation
                            'trial_ends_at' => now()->addMonths(3),
                        ]);

                        if (! $adminUser || ! $token) {
                            Notification::make()
                                ->title(__('Institution Approved'))
                                ->body(__('No pending administrator was found, or the activation email could not be delivered. Use "Resend Activation" once the record is ready.'))
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('Institution Approved & Activation Email Sent'))
                            ->body("Activation link sent to {$adminUser->email}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('resendActivation')
                    ->label(__('Resend Activation Link'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (School $record) => $record->status === 'pending'
                        && $record->users()->where('account_status', User::STATUS_PENDING)->exists())
                    ->requiresConfirmation()
                    ->action(function (School $record) {
                        $adminUser = $record->users()
                            ->where('requested_role', 'administrator')
                            ->where('account_status', User::STATUS_PENDING)
                            ->first() ?? $record->users()->where('account_status', User::STATUS_PENDING)->first();

                        $token = $adminUser ? app(AccountActivationService::class)->issueAndSend($adminUser) : null;

                        if (! $token) {
                            Notification::make()
                                ->title(__('Could Not Resend'))
                                ->body(__('The activation email could not be delivered. Check the platform mail configuration.'))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('Activation Link Resent'))
                            ->body("A fresh link was sent to {$adminUser->email}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('suspend')
                    ->label(__('Suspend Access'))
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->visible(fn (School $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (School $record) {
                        $record->update(['status' => 'suspended']);
                        Notification::make()->title(__('Institution Suspended'))->warning()->send();
                    }),

                Tables\Actions\Action::make('activate')
                    ->label(__('Restore Access'))
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (School $record) => $record->status === 'suspended')
                    ->action(function (School $record) {
                        $record->update(['status' => 'active']);
                        Notification::make()->title(__('Institution Access Restored'))->success()->send();
                    }),

                Tables\Actions\Action::make('impersonate')
                    ->label(__('Impersonate'))
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->action(function (School $record) {
                        $link = TenantImpersonationEngine::generateSecureLink($record->id);

                        return redirect()->away($link);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->using(fn (School $record) => $record->forceDelete())
                    ->requiresConfirmation()
                    ->modalDescription('This permanently deletes the school and ALL of its data (users, students, records, files). This cannot be undone.')
                    ->modalIcon('heroicon-o-exclamation-triangle'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
        ];
    }
}
