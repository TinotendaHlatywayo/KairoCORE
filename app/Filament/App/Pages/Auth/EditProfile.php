<?php

namespace App\Filament\App\Pages\Auth;

use App\Services\ProfilePhotoService;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * App-panel account/profile page.
 *
 * The stock Filament profile page validates the email against the whole users
 * table, but Kairo CORE scopes uniqueness per school (composite unique index on
 * [school_id, email]). This page narrows the rule to the current tenant so a
 * user can keep the same email address as a colleague at another school. It
 * also adds an optional phone field and a friendly profile header.
 */
class EditProfile extends BaseEditProfile
{
    protected static string $view = 'filament.app.pages.auth.edit-profile';

    protected function getEmailFormComponent(): Component
    {
        $tenant = current_tenant();

        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/edit-profile.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(
                ignoreRecord: true,
                modifyRuleUsing: function (Rule $rule) use ($tenant) {
                    return $rule->where('school_id', $tenant?->id);
                }
            );
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label(__('Phone'))
            ->tel()
            ->maxLength(60)
            ->placeholder(__('+263 ...'));
    }

    protected function getLocaleFormComponent(): Component
    {
        return Select::make('locale')
            ->label(__('Interface Language'))
            ->options([
                'en' => __('English'),
                'sn' => __('Shona'),
                'sw' => __('Swahili'),
                'fr' => __('Français'),
                'pt' => __('Português'),
                'es' => __('Español'),
            ])
            ->nullable()
            ->placeholder(__('System Default (School Language)'));
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPhoneFormComponent(),
                        $this->getLocaleFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);
        $locale = $data['locale'] ?? null;

        $isPlatform = str_starts_with(request()->path(), 'platform');
        $sessionKey = $isPlatform ? 'locale_admin' : 'locale';

        if ($locale) {
            session([$sessionKey => $locale]);
            app()->setLocale($locale);
        } else {
            session()->forget($sessionKey);
        }

        return $record;
    }

    public function getRoleLabel(): string
    {
        $user = $this->getUser();

        if ($user?->isSuperAdmin()) {
            return 'Platform Administrator';
        }

        return $user?->customRole?->name
            ?? $user?->requestedRoleLabel()
            ?? 'Active Account';
    }

    /**
     * Staff profile photo (saved to the linked Employee record). Uses the same
     * passport-style crop + face validation + server-side GD checks as the
     * student portal.
     */
    public function savePhoto(string $dataUrl): void
    {
        $user = $this->getUser();
        $employee = $user?->employee;

        if (! $employee) {
            Notification::make()
                ->title(__('Profile Not Linked'))
                ->body(__('No staff record is linked to this account yet.'))
                ->warning()
                ->send();

            return;
        }

        [$path, $error] = app(ProfilePhotoService::class)
            ->storeFromDataUrl($dataUrl, 'hr/avatars');

        if ($error) {
            Notification::make()
                ->title(__('Upload Failed'))
                ->body($error)
                ->danger()
                ->send();

            return;
        }

        $old = $employee->avatar_path;

        $employee->update([
            'avatar_path' => $path,
            'photo_rejected_at' => null,
            'photo_rejected_reason' => null,
            'photo_rejected_by' => null,
        ]);

        if ($old && $old !== $path && $old !== 'images/employee_profile.jpeg') {
            app(ProfilePhotoService::class)->deleteStored($old);
        }

        $this->dispatch('profilePhotoUpdated');

        Notification::make()
            ->title(__('Photo Uploaded'))
            ->body(__('Your profile photo has been saved successfully.'))
            ->success()
            ->send();
    }

    public function getEmployeePhoto(): ?string
    {
        $employee = $this->getUser()?->employee;

        if ($employee?->avatar_path && $employee->avatar_path !== 'images/employee_profile.jpeg') {
            return resolve_public_asset_path($employee->avatar_path);
        }

        return null;
    }

    public function getEmployeePhotoRejection(): ?array
    {
        $employee = $this->getUser()?->employee;

        if ($employee && filled($employee->photo_rejected_at)) {
            return [
                'reason' => $employee->photo_rejected_reason,
                'rejected_at' => $employee->photo_rejected_at,
            ];
        }

        return null;
    }
}
