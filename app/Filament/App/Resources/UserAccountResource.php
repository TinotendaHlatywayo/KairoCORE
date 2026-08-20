<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\UserAccountResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\Department;
use Modules\Admin\Services\PermissionRegistry;

/**
 * Directory of individual user accounts with the administrator approval
 * workflow for new registrations.
 *
 * Access is limited to administrators holding the users.approve /
 * users.reject permissions (or full system administration rights). A badge on
 * the navigation item highlights how many accounts currently await review.
 */
class UserAccountResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('System Administration');
    }

    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User Accounts';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = User::query()->where('account_status', User::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Accounts awaiting approval';
    }

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('administration.manage_users')
            || PermissionRegistry::userCan(auth()->user(), 'users.approve')
            || PermissionRegistry::userCan(auth()->user(), 'users.reject');
    }

    public static function canApprove(): bool
    {
        return PermissionRegistry::userCan(auth()->user(), 'users.approve');
    }

    public static function canReject(): bool
    {
        return PermissionRegistry::userCan(auth()->user(), 'users.reject');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(60),
                Forms\Components\Select::make('custom_role_id')
                    ->label(__('Assigned Role'))
                    ->options(fn (?User $record) => CustomRole::query()
                        ->where('school_id', current_tenant()?->id)
                        ->when($record, fn (Builder $q) => $q->where('id', '!=', $record->custom_role_id))
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('password')
                    ->label(fn ($livewire) => $livewire instanceof CreateRecord ? 'Temporary Password' : 'Reset Password')
                    ->password()
                    ->revealable()
                    ->helperText(fn ($livewire) => $livewire instanceof CreateRecord
                        ? 'The user can change this after their first sign-in.'
                        : 'Leave blank to keep the current password.')
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->rule(Password::default())
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : $state),
                Forms\Components\Select::make('account_status')
                    ->options([
                        User::STATUS_ACTIVE => 'Active',
                        User::STATUS_PENDING => 'Pending Approval',
                        User::STATUS_REJECTED => 'Rejected',
                        User::STATUS_SUSPENDED => 'Suspended',
                    ])
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('requested_role')
                    ->label(__('Requested Registration Role'))
                    ->options(User::REGISTRATION_ROLES)
                    ->helperText(__('Determines the default permission set pre-ticked for this account.'))
                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateUserAccount)
                    ->disabled(fn ($livewire) => $livewire instanceof Pages\EditUserAccount)
                    ->dehydrated(fn ($livewire) => $livewire instanceof Pages\CreateUserAccount)
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        if ($state) {
                            $set('permissions', PermissionRegistry::defaultPermissionsForRole($state));
                        }
                    }),
                Forms\Components\Select::make('departments')
                    ->label(__('Departments'))
                    ->relationship(name: 'departments', titleAttribute: 'name', ignoreRecord: true)
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText(__('Non-teaching staff inherit the default permissions of each assigned department.'))
                    ->visible(fn (Forms\Get $get) => $get('requested_role') === 'non_teaching_staff')
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, array $state) {
                        $base = PermissionRegistry::defaultPermissionsForRole($get('requested_role') ?? 'non_teaching_staff');
                        $extra = [];

                        foreach (Department::query()->whereIn('id', $state)->get() as $department) {
                            $extra = array_merge($extra, $department->permissions ?? []);
                        }

                        $set('permissions', PermissionRegistry::normalizePermissionList(array_merge($base, $extra)));
                    }),
                Forms\Components\CheckboxList::make('permissions')
                    ->label(__('Permissions'))
                    ->options(fn () => PermissionRegistry::permissionOptions())
                    ->columns(3)
                    ->searchable()
                    ->gridDirection('row')
                    ->helperText(__('Ticked by default from the role and department bundles; untick or tick to tailor this account.'))
                    ->formatStateUsing(fn (?User $record, $state) => $record && $state === null
                        ? PermissionRegistry::effectivePermissionsForUser($record)
                        : $state)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('rejected_reason')
                    ->label(__('Rejection Reason'))
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Forms\Components\View::make('filament.app.resources.user-account.registration-conflict')
                    ->columnSpanFull()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateUserAccount && $livewire->hasPendingConflict())
                    ->viewData(fn ($livewire) => [
                        'conflictingUser' => $livewire->conflictingUser(),
                    ]),
                Forms\Components\Radio::make('conflict_mode')
                    ->label(__('How should we handle the existing account?'))
                    ->options([
                        'merge' => __('Merge — keep the existing account and re-queue it for approval with the new details'),
                        'replace' => __('Replace — permanently delete the existing account and create a fresh one'),
                    ])
                    ->default('merge')
                    ->required()
                    ->columnSpanFull()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateUserAccount && $livewire->hasPendingConflict()),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requested_role')
                    ->label(__('Requested Role'))
                    ->formatStateUsing(fn (?string $state) => $state ? User::REGISTRATION_ROLES[$state] ?? ucwords(str_replace('_', ' ', $state)) : '—')
                    ->placeholder(__('—'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customRole.name')
                    ->label(__('Assigned Role'))
                    ->placeholder(__('—')),
                Tables\Columns\TextColumn::make('account_status')
                    ->badge()
                    ->formatStateUsing(fn (User $record) => $record->accountStatusLabel())
                    ->color(fn (User $record) => match ($record->account_status) {
                        User::STATUS_ACTIVE => 'success',
                        User::STATUS_PENDING => 'warning',
                        User::STATUS_REJECTED => 'danger',
                        User::STATUS_SUSPENDED => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Registered'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label(__('Approved'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_status')
                    ->label(__('Status'))
                    ->options([
                        User::STATUS_ACTIVE => 'Active',
                        User::STATUS_PENDING => 'Pending Approval',
                        User::STATUS_REJECTED => 'Rejected',
                        User::STATUS_SUSPENDED => 'Suspended',
                    ])
                    ->default(User::STATUS_PENDING)
                    ->placeholder(__('All statuses')),
                Tables\Filters\SelectFilter::make('requested_role')
                    ->label(__('Requested Role'))
                    ->options(User::REGISTRATION_ROLES),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->label(__('Review')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => PermissionRegistry::checkPermission('administration.manage_users')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customRole:id,name']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserAccounts::route('/'),
            'create' => Pages\CreateUserAccount::route('/create'),
            'edit' => Pages\EditUserAccount::route('/{record}/edit'),
        ];
    }
}
