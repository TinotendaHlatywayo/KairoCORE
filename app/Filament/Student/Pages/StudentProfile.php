<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use App\Services\ProfilePhotoService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class StudentProfile extends Page
{
    protected static string $view = 'filament.student.pages.student-profile';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'My Account';

    protected static ?string $navigationLabel = 'Personal Details';

    protected static ?string $title = 'Personal Details';

    protected static ?string $slug = 'my-profile';

    public static function getNavigationLabel(): string
    {
        return __('Personal Details');
    }

    public function mount(): void {}

    /**
     * Save a profile photo supplied as a base64 data URL.
     *
     * The image is re-encoded with GD (never stored verbatim) so a malicious or
     * corrupted payload can never be persisted. A client-side passport-style
     * cropper (Cropper.js + face-api.js) verifies the face before this method
     * is reached; the server re-checks dimensions and aspect ratio anyway.
     */
    public function savePhoto(string $dataUrl): void
    {
        $student = HomeworkResource::currentStudent();

        if (! $student) {
            Notification::make()
                ->title(__('Profile Not Linked'))
                ->body(__('No student record is linked to this account yet.'))
                ->warning()
                ->send();

            return;
        }

        [$path, $error] = app(ProfilePhotoService::class)
            ->storeFromDataUrl($dataUrl, 'student-photos');

        if ($error) {
            Notification::make()
                ->title(__('Upload Failed'))
                ->body($error)
                ->danger()
                ->send();

            return;
        }

        $old = $student->photo_path;

        $student->update([
            'photo_path' => $path,
            'photo_rejected_at' => null,
            'photo_rejected_reason' => null,
            'photo_rejected_by' => null,
        ]);

        if ($old && $old !== $path) {
            app(ProfilePhotoService::class)->deleteStored($old);
        }

        $this->dispatch('profilePhotoUpdated');

        Notification::make()
            ->title(__('Photo Uploaded'))
            ->body(__('Your profile photo has been saved successfully.'))
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();
        $documents = $student?->documents()->get() ?? collect();
        $guardians = $student?->guardians()->get() ?? collect();

        $user = Auth::user();

        return [
            'student' => $student,
            'documents' => $documents,
            'guardians' => $guardians,
            'enrollment' => $student?->currentEnrollment,
            'user' => $user,
            'hasPhoto' => filled($student?->photo_path),
            'photoRejection' => filled($student?->photo_rejected_at) ? [
                'reason' => $student->photo_rejected_reason,
                'rejected_at' => $student->photo_rejected_at,
            ] : null,
        ];
    }
}
