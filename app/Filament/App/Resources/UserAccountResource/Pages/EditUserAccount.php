<?php

namespace App\Filament\App\Resources\UserAccountResource\Pages;

use App\Filament\App\Resources\UserAccountResource;
use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\UserRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
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
            $this->getResendActivationAction(),
            DeleteAction::make()
                ->visible(fn () => PermissionRegistry::checkPermission('administration.manage_users')),
        ];
    }

    /**
     * Spread the account's saved additions back across the grouped editor.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return UserAccountResource::hydratePermissions(
            $data,
            sourceKey: 'permissions',
            fieldPrefix: 'extra_permissions',
        );
    }

    /**
     * Collapse the grouped editor back into the flat list of additions.
     *
     * Only the additions are stored. The role's own permissions are not copied
     * onto the account, so changing the role later genuinely changes what the
     * person can do.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UserAccountResource::dehydratePermissions(
            $data,
            targetKey: 'permissions',
            fieldPrefix: 'extra_permissions',
        );
    }

    /**
     * Re-read the record into the form.
     *
     * Filament's own fillForm() is protected, but the role select needs it:
     * the "extra permissions" editor is built from what the assigned role
     * already grants, so changing the role has to rebuild it. Without this the
     * boxes stay as they were, and a permission the new role now supplies is
     * saved on top of it as a personal addition — which then survives every
     * later change to the role.
     */
    public function refreshFormFromRecord(): void
    {
        $this->fillForm();
    }

    protected function getResendActivationAction(): Action
    {
        return Action::make('resendActivation')
            ->label(__('Resend activation link'))
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->visible(fn () => $this->record->account_status === User::STATUS_PENDING)
            ->requiresConfirmation()
            ->modalHeading(__('Resend activation link'))
            ->modalDescription(fn () => 'A fresh, single-use activation link will be emailed to '.$this->record->email.'.')
            ->action(function () {
                $sent = app(AccountActivationService::class)->issueAndSend($this->record);

                if ($sent) {
                    Notification::make()
                        ->success()
                        ->title(__('Activation link sent'))
                        ->body('A fresh activation link was emailed to '.$this->record->email.'.')
                        ->send();
                } else {
                    Notification::make()
                        ->danger()
                        ->title(__('Could not send the email'))
                        ->body(__('The activation email could not be delivered. Check the mail settings and try again.'))
                        ->send();
                }
            });
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
                    ->afterStateUpdated(function () {
                        // Departments add to the role. Nothing is copied onto the
                        // account's own permission list, so the role stays the
                        // single source of truth for what the job can do.
                    }),
                Placeholder::make('inherited_summary')
                    ->label(__('Already Granted By The Role'))
                    ->content(fn () => __('This account already reaches :count capabilities through its role and departments. Tick below only what you want to add on top.', [
                        'count' => count(PermissionRegistry::defaultPermissionsForUser($this->record)),
                    ])),
                CheckboxList::make('permissions')
                    ->label(__('Extra Permissions On Top Of The Role'))
                    ->options(fn () => PermissionRegistry::permissionOptions())
                    ->columns(3)
                    ->gridDirection('row')
                    ->searchable()
                    ->helperText(__('Added to the role, never subtracted from it. Clearing a tick here returns the account to its role\'s permissions rather than to no permissions.'))
                    ->default(fn () => PermissionRegistry::personalPermissionsFor($this->record)),
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
