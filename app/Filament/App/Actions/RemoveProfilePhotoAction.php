<?php

namespace App\Filament\App\Actions;

use App\Filament\App\Pages\Auth\EditProfile;
use App\Filament\Student\Pages\StudentProfile;
use App\Models\User;
use App\Notifications\ProfilePhotoRejectedNotification;
use App\Services\ProfilePhotoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;

/**
 * Admin "Remove / Replace Profile Photo" action used on both student and
 * employee records. Deletes the stored photo, records the rejection (so the
 * portal can show the reason) and notifies the linked user to upload a new
 * passport-style photo. The default placeholder stays as the profile photo.
 */
class RemoveProfilePhotoAction extends Action
{
    protected string $photoColumn = 'photo_path';

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'removeProfilePhoto');
    }

    public function photoColumn(string $column): static
    {
        $this->photoColumn = $column;

        return $this;
    }

    public static function getDefaultName(): ?string
    {
        return 'removeProfilePhoto';
    }

    public function getRecord(): ?Model
    {
        return $this->record;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Remove / Replace Photo'))
            ->icon('heroicon-o-photo')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('Remove Profile Photo'))
            ->modalDescription(__('The photo will be removed and the default placeholder will be used. The student/staff member will be notified and asked to upload a new passport-style photo. You can add a note explaining why it was removed.'))
            ->modalSubmitActionLabel(__('Remove Photo'))
            ->form([
                Textarea::make('reason')
                    ->label(__('Reason (optional)'))
                    ->placeholder(__('e.g. Photo was blurry / not a clear single face / not a passport-style photo'))
                    ->rows(3)
                    ->maxLength(500)
                    ->helperText(__('Shown to the user in their portal so they know why their photo was removed.')),
            ])
            ->action(function (array $data, Model $record, Action $action) {
                app(ProfilePhotoService::class)->rejectPhoto(
                    $record,
                    $data['reason'] ?? null,
                    $this->photoColumn,
                );

                $this->notifyLinkedUser($record);

                Notification::make()
                    ->title(__('Photo Removed'))
                    ->body(__('The profile photo has been removed and the user was notified.'))
                    ->success()
                    ->send();
            });
    }

    protected function notifyLinkedUser(Model $record): void
    {
        $user = $record instanceof Student
            ? User::find($record->user_id)
            : ($record instanceof Employee ? User::find($record->user_id) : null);

        if (! $user) {
            return;
        }

        $isStudent = $record instanceof Student;
        $url = $isStudent
            ? StudentProfile::getUrl(panel: 'student')
            : EditProfile::getUrl(panel: 'app');

        $user->notify(new ProfilePhotoRejectedNotification(
            subject: $isStudent
                ? __('Your profile photo was removed')
                : __('Your profile photo was removed'),
            reason: $record->photo_rejected_reason,
            url: $url,
        ));
    }
}
