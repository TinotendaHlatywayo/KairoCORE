<?php

namespace App\Services;

use App\Mail\AdmissionConfirmation;
use App\Models\School;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\TenantEmailConfigurationService;
use Modules\Students\Models\Student;

/**
 * Sends the admission confirmation email configured under
 * Admission Settings when a student is successfully enrolled.
 */
class AdmissionNotificationService
{
    public function __construct(
        private TenantEmailConfigurationService $emailConfigService,
        private AccountActivationService $activationService,
    ) {}

    public function send(Student $student, ?string $recipientEmail = null, ?int $schoolId = null): bool
    {
        $schoolId = $schoolId ?? ($student->school_id ?? session('current_tenant')?->id);

        $school = School::find($schoolId);
        if (! $school) {
            return false;
        }

        // The tenant configuration governs whether email is enabled for the
        // admissions category. Legacy flag is kept in sync by the settings page.
        $config = $this->emailConfigService->forSchool($school, EmailCategory::Admissions);
        if (! $config || ! $config->is_enabled) {
            return false;
        }

        $recipientEmail = $recipientEmail ?: $this->resolveRecipientEmail($student);

        if (! $recipientEmail || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $subject = SystemSetting::get('admission', 'email_subject', 'Admission Confirmation — {school_name}');
        $body = SystemSetting::get('admission', 'email_body') ?: $this->defaultBody();

        $year = $student->enrollments()->with('academicYear')->latest()->first()?->academicYear?->name
            ?? SystemSetting::get('admission', 'current_year', '');

        // Issue an activation token so the confirmation email includes a
        // direct link for the student to set their password.
        $activationUrl = null;
        if ($student->user) {
            try {
                $token = $this->activationService->issueToken($student->user);
                $activationUrl = route('account.activate', ['token' => $token]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $subject = $this->interpolate($subject, $student, $school, $year, $activationUrl);
        $body = $this->interpolate($body, $student, $school, $year, $activationUrl);

        return $this->emailConfigService->queueSend(
            new AdmissionConfirmation(
                $student,
                $recipientEmail,
                $subject,
                $body,
                $school->name,
                activationUrl: $activationUrl,
            ),
            EmailCategory::Admissions,
            $school
        );
    }

    protected function resolveRecipientEmail(Student $student): ?string
    {
        // The email registered during the online application.
        if ($student->application && $student->application->parent_email) {
            return $student->application->parent_email;
        }

        // Email captured during physical (in-system) registration.
        if ($student->parent_email) {
            return $student->parent_email;
        }

        // Fall back to a linked user account email.
        return $student->user?->email;
    }

    protected function interpolate(string $text, Student $student, School $school, string $year, ?string $activationUrl = null): string
    {
        $level = $student->currentEnrollment?->course?->name
            ?? $student->application?->course?->name
            ?? '';

        return strtr($text, [
            '{student_name}' => $student->full_name,
            '{student_id}' => $student->student_id_number,
            '{admission_number}' => $student->admission_number,
            '{school_name}' => $school->name,
            '{academic_year}' => $year,
            '{level}' => $level,
            '{activation_url}' => $activationUrl ?? '',
            '{hours}' => (string) config('auth.activation_token_ttl_hours', 48),
        ]);
    }

    protected function defaultBody(): string
    {
        return "Dear Parent/Guardian,\n\nWe are pleased to confirm that {student_name} has been offered admission to {school_name} for the {academic_year} academic year in {level}.\n\nStudent ID Number: {student_id}\nAdmission/Student Number: {admission_number}\n\nWelcome to our school community!";
    }
}
