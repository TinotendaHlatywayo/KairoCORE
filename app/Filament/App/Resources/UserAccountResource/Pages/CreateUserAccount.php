<?php

namespace App\Filament\App\Resources\UserAccountResource\Pages;

use App\Filament\App\Resources\UserAccountResource;
use App\Models\User;
use App\Services\UserRegistrationService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateUserAccount extends CreateRecord
{
    protected static string $resource = UserAccountResource::class;

    public ?int $conflictingUserId = null;

    protected function getRedirectUrl(): string
    {
        return UserAccountResource::getUrl('index');
    }

    public function hasPendingConflict(): bool
    {
        return $this->conflictingUserId !== null;
    }

    public function conflictingUser(): ?User
    {
        return $this->conflictingUserId
            ? User::withTrashed()->find($this->conflictingUserId)
            : null;
    }

    public function create(bool $another = false): void
    {
        if ($this->conflictingUserId !== null) {
            $this->resolveRegistrationConflict();

            return;
        }

        parent::create($another);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $conflict = app(UserRegistrationService::class)->findConflicting(
            Filament::getTenant()?->getKey(),
            $data['email'] ?? null,
        );

        if ($conflict) {
            $this->conflictingUserId = $conflict->getKey();

            throw new Halt;
        }

        return $data;
    }

    /**
     * The first submit collided with an existing account; the admin picked how
     * to proceed (replace or merge) in the conflict panel and submitted again.
     * The submitted form state is re-read here because protected page state is
     * not persisted between Livewire requests.
     */
    protected function resolveRegistrationConflict(): void
    {
        $conflicting = User::withTrashed()->find($this->conflictingUserId);

        if (! $conflicting) {
            $this->conflictingUserId = null;

            return;
        }

        $state = $this->form->getState();
        $mode = $state['conflict_mode'] ?? 'merge';
        unset($state['conflict_mode']);

        $user = app(UserRegistrationService::class)->register(
            Filament::getTenant(),
            $state,
            $mode,
        );

        $user->forceFill([
            'custom_role_id' => $state['custom_role_id'] ?? $user->custom_role_id,
            'account_status' => $state['account_status'] ?? $user->account_status,
            'permissions' => $state['permissions'] ?? $user->permissions,
        ])->save();

        if (array_key_exists('departments', $state)) {
            $user->departments()->sync($state['departments'] ?? []);
        }

        $this->record = $user;
        $this->conflictingUserId = null;

        $this->getCreatedNotification()?->send();

        $this->redirect($this->getRedirectUrl());
    }
}
