<?php

namespace App\Notifications;

use App\Filament\App\Resources\StudentResource;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Students\Models\Student;

/** Notifies school staff that a student has been successfully enrolled. */
class NewEnrollmentNotification extends Notification
{
    use Queueable;

    public function __construct(public Student $student) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'enrollment',
            'student_id' => $this->student->id,
            'admission_number' => $this->student->admission_number,
            'student_name' => $this->student->full_name,
            'url' => StudentResource::getUrl('view', ['record' => $this->student]),
        ];
    }
}
