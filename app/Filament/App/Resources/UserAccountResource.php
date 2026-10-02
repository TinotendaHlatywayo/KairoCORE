<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\UserAccountResource\Pages;
use App\Models\User;
use App\Security\RoleCatalogue;
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
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('System Administration');
    }

    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'System Administration';

    protected static bool $shouldRegisterNavigation = false;

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
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    // The current role is deliberately still offered. Hiding it
                    // made the field look like it had already been answered, and
                    // left no way to put somebody back on the role they had.
                    ->helperText(__('Changing this changes everything the account can reach, except the extra permissions listed below.'))
                    // Keep the catalogue identity on the account in step. The
                    // runtime reads custom_role_id, but requested_role is what
                    // the employee record, the widgets and the role editor read,
                    // so leaving it behind would make the account report two
                    // different jobs depending on which screen asked.
                    ->afterStateUpdated(function ($state, $livewire) {
                        $role = $state ? CustomRole::find($state) : null;

                        if ($livewire instanceof Pages\EditUserAccount) {
                            $record = $livewire->getRecord();

                            // The record is updated first and the form is then
                            // rebuilt from it. Refilling first would read the
                            // OLD custom_role_id straight back into the select,
                            // so the save would store the role the person had
                            // before rather than the one just chosen — which is
                            // exactly the "I changed it and nothing happened"
                            // report.
                            $record->forceFill([
                                'custom_role_id' => $state,
                                'requested_role' => $role?->role_key,
                            ])->save();

                            // The grouped "extra permissions" editor is built
                            // from what the role already grants, so its ticked
                            // boxes have to be rebuilt from the new role.
                            // Otherwise a permission the new role now supplies
                            // stays ticked and is saved on top of it as a
                            // personal addition, which then survives every
                            // later change to the role.
                            $livewire->refreshFormFromRecord();
                        }
                    }),
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
                    ->options(RoleCatalogue::employeeRoles())
                    ->helperText(__('Sets what this account can reach by default. You can add anything extra below without changing the role.'))
                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateUserAccount)
                    ->disabled(fn ($livewire) => $livewire instanceof Pages\EditUserAccount)
                    ->dehydrated(fn ($livewire) => $livewire instanceof Pages\CreateUserAccount),
                // ignoreRecord is deliberately NOT used here. It only means
                // anything for a BelongsTo (a record must not be offered as its
                // own parent); on a BelongsToMany it makes Filament compare the
                // related table against the owner model's key column, which
                // produces `where users.id != ?` against `departments` and a
                // 500 on every edit screen.
                Forms\Components\Select::make('departments')
                    ->label(__('Departments'))
                    ->relationship(name: 'departments', titleAttribute: 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText(__('Departments add their own permissions on top of the role.')),
                Forms\Components\Placeholder::make('inherited_permissions_summary')
                    ->label(__('Permissions From Their Role'))
                    ->content(fn (Forms\Get $get, $livewire) => static::inheritedSummary($get, $livewire))
                    ->columnSpanFull(),
                Forms\Components\Tabs::make('extra_permissions_tabs')
                    ->label(__('Extra Permissions For This Account'))
                    ->columnSpanFull()
                    ->tabs(fn (Forms\Get $get, $livewire) => array_merge(
                        [
                            Forms\Components\Tabs\Tab::make(__('Personal Additions'))
                                ->schema([
                                    Forms\Components\Placeholder::make('additions_help')
                                        ->label('')
                                        ->content(__('Tick only what this person needs beyond their role. Ticking something here never removes what their role already allows, and clearing it later returns them to their role, not below it.')),
                                ]),
                        ],
                        static::permissionOverrideTabs(
                            inheritedKeys: fn () => static::inheritedPermissionKeys($get, $livewire),
                            fieldPrefix: 'extra_permissions',
                        ),
                    ))
                    // The tab list is rebuilt whenever the role changes, so the
                    // ticked values have to be re-expanded or they would be
                    // dropped on the next save.
                    ->live(debounce: 500),
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

    /**
     * The capabilities the account already has before anything is added to it.
     *
     * On the create screen the role is still a choice, so the answer follows the
     * selected role plus the departments ticked alongside it — which is what the
     * approver is about to hand out. On the edit screen the role has already
     * been assigned, so the account itself is the authority. Reading the same
     * resolver the runtime uses is the point: if this list disagreed with what
     * the person can actually reach, the screen would offer them capabilities
     * they already had and hide ones they did not.
     *
     * @return array<int, string>
     */
    protected static function inheritedPermissionKeys(Forms\Get $get, mixed $livewire): array
    {
        $departments = static::departmentPermissions($get);

        // An explicitly chosen role wins: this is the role the approver is
        // about to hand out, whatever the registration asked for.
        if (filled($roleId = $get('custom_role_id'))) {
            $role = CustomRole::query()->find($roleId);

            if ($role) {
                return PermissionRegistry::normalizePermissionList(
                    array_merge($role->permissions ?? [], $departments)
                );
            }
        }

        // On the edit screen the role has already been assigned, so the account
        // itself is the authority on what it reaches.
        $record = $livewire instanceof Pages\EditUserAccount ? $livewire->getRecord() : null;

        if ($record) {
            return PermissionRegistry::normalizePermissionList(
                PermissionRegistry::defaultPermissionsForUser($record)
            );
        }

        $roleKey = $get('requested_role');

        if (! RoleCatalogue::exists((string) $roleKey)) {
            return PermissionRegistry::normalizePermissionList($departments);
        }

        return PermissionRegistry::normalizePermissionList(array_merge(
            RoleCatalogue::permissionsFor($roleKey),
            $departments,
        ));
    }

    /**
     * The permissions the departments ticked on this form contribute.
     *
     * Departments add to a role, so they belong in the same inherited set — a
     * capability a department already supplies must not be offered again as an
     * addition.
     *
     * @return array<int, string>
     */
    protected static function departmentPermissions(Forms\Get $get): array
    {
        $ids = array_filter((array) $get('departments'));

        if ($ids === []) {
            return [];
        }

        $permissions = [];

        foreach (Department::query()->whereIn('id', $ids)->get() as $department) {
            $permissions = array_merge($permissions, $department->permissions ?? []);
        }

        return $permissions;
    }

    /**
     * A plain-language account of what the role already covers.
     *
     * Without this the "Extra Permissions" tabs read as a list of things to
     * grant, and an administrator has no way to tell which of the roles they are
     * choosing between actually reaches further.
     */
    protected static function inheritedSummary(Forms\Get $get, mixed $livewire): string
    {
        $permissions = static::inheritedPermissionKeys($get, $livewire);

        if ($permissions === []) {
            return __('Choose a role above and this will list what it already lets this person reach.');
        }

        $summary = RoleCatalogue::summarise($permissions);

        $lines = array_map(
            fn (string $item): string => '• '.$item,
            array_slice($summary, 0, 12)
        );

        if (count($summary) > 12) {
            $lines[] = __('• …and :count more areas.', ['count' => count($summary) - 12]);
        }

        $roleLabel = ($livewire instanceof Pages\EditUserAccount && $livewire->getRecord())
            ? ($livewire->getRecord()->customRole?->name ?? __('their role'))
            : (RoleCatalogue::exists((string) $get('requested_role'))
                ? RoleCatalogue::label((string) $get('requested_role'))
                : __('their role'));

        return __('Already covered by :role — :count capabilities in all:', [
            'role' => $roleLabel,
            'count' => count($permissions),
        ])."\n".implode("\n", $lines);
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
