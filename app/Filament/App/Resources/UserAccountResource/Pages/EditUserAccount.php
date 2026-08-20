<?php

namespace App\Filament\App\Resources\UserAccountResource\Pages;

use App\Filament\App\Resources\UserAccountResource;
use App\Models\User;
use App\Services\UserRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\Department;
use Modules\Admin\Services\PermissionRegistry;

class EditUserAccount extends EditRecord
{
    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getApproveAction(),
            $this->getRejectAction(),
            DeleteAction::make()
                ->visible(fn () => PermissionRegistry::checkPermission('administration.manage_users')),
        ];
    }

    protected function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label(fn () => $this->record->approved_at ? 'Update role & re-activate' : 'Approve registration')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn () => PermissionRegistry::userCan(auth()->user(), 'users.approve'))
            ->requiresConfirmation()
            ->modalHeading(__('Approve this registration'))
            ->modalDescription(fn () => 'Activating '.$this->record->name.' grants immediate workspace access according to the role assigned below.')
            ->form([
                Select::make('role_id')
                    ->label(__('Role to assign'))
                    ->options(fn () => CustomRole::query()->orderBy('name')->pluck('name', 'id'))
                    ->default(fn () => $this->defaultRoleId())
                    ->required(),
                Select::make('department_ids')
                    ->label(__('Departments'))
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText(__('Non-teaching staff inherit the default permissions of each assigned department.'))
                    ->visible(fn () => $this->record->requested_role === 'non_teaching_staff')
                    ->default(fn () => $this->record->departments->pluck('id')->all())
                    ->afterStateUpdated(function (Filament\Forms\Set $set, array $state) {
                        $base = PermissionRegistry::defaultPermissionsForRole($this->record->requested_role ?? 'non_teaching_staff');
                        $extra = [];

                        foreach (Department::query()->whereIn('id', $state)->get() as $department) {
                            $extra = array_merge($extra, $department->permissions ?? []);
                        }

                        $set('permissions', PermissionRegistry::normalizePermissionList(array_merge($base, $extra)));
                    }),
                CheckboxList::make('permissions')
                    ->label(__('Permissions'))
                    ->options(fn () => PermissionRegistry::permissionOptions())
                    ->columns(3)
                    ->gridDirection('row')
                    ->searchable()
                    ->helperText(__('Pre-ticked with the default permissions for this role and its departments. Adjust per account. Each user gets a personal snapshot — editing another role later will not change this account.'))
                    ->default(fn () => PermissionRegistry::defaultPermissionsForUser($this->record)),
            ])
            ->action(function (array $data) {
                app(UserRegistrationService::class)->approve(
                    $this->record,
                    $data['role_id'],
                    auth()->id(),
                    $data['permissions'] ?? null,
                    $data['department_ids'] ?? null,
                );

                Notification::make()
                    ->success()
                    ->title(__('Registration approved'))
                    ->body($this->record->name.' can now sign in to the school workspace.')
                    ->send();

                $this->fillForm();
            });
    }

    protected function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('Reject registration'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => $this->record->account_status !== User::STATUS_ACTIVE
                && PermissionRegistry::userCan(auth()->user(), 'users.reject'))
            ->form([
                Textarea::make('reason')
                    ->label(__('Reason for rejection'))
                    ->placeholder(__('Explain to the applicant why this registration was declined.'))
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (array $data) {
                $reason = $data['reason'];

                app(UserRegistrationService::class)->reject(
                    $this->record,
                    $reason,
                    auth()->id(),
                );

                Notification::make()
                    ->warning()
                    ->title(__('Registration rejected'))
                    ->body('The account remains locked from the workspace.')
                    ->send();

                $this->fillForm();
            });
    }

    protected function defaultRoleId(): ?int
    {
        if (! $this->record->requested_role) {
            return null;
        }

        return UserRegistrationService::ensureRoleForCategory(
            $this->record->school_id,
            $this->record->requested_role,
        )->getKey();
    }
}
