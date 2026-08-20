<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Notifications\SchoolRegisteredNotification;
use Illuminate\Support\Facades\Notification;
use Modules\SaaS\Models\PlatformSetting;

/**
 * Orchestrates the platform-level school registration workflow: it reliably
 * notifies the super administrators (in-app + email) whenever a new school
 * applies to join the platform.
 */
class SchoolRegistrationService
{
    /**
     * Notify every platform super administrator about a new school registration.
     *
     * - In-app database notification is ALWAYS delivered to each super admin.
     * - An email is sent to each super admin, plus to the configured platform
     *   notification inbox (Platform Setting "notifications.super_admin_email",
     *   falling back to the platform sender address, e.g. twaynehlatywayo09@gmail.com).
     */
    public function notifySuperAdmin(School $school, User $contact): array
    {
        $sendMail = $this->emailNotificationsEnabled();
        $notification = new SchoolRegisteredNotification($school, $contact, $sendMail);

        $superAdmins = User::whereNull('school_id')->get();
        $mailRecipients = [];

        foreach ($superAdmins as $admin) {
            $admin->notify($notification);
            if ($sendMail && filled($admin->email)) {
                $mailRecipients[] = mb_strtolower(trim($admin->email));
            }
        }

        if ($sendMail) {
            $inbox = $this->superAdminNotificationEmail();
            if (filled($inbox) && ! in_array(mb_strtolower(trim($inbox)), $mailRecipients, true)) {
                Notification::route('mail', $inbox)->notify($notification);
                $mailRecipients[] = mb_strtolower(trim($inbox));
            }
        }

        return $mailRecipients;
    }

    /**
     * Whether platform emails should be sent for new school registrations.
     */
    public function emailNotificationsEnabled(): bool
    {
        $setting = PlatformSetting::get('notifications', 'email_on_school_registration', '1');

        return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * The email inbox that receives new-school registration alerts.
     */
    public function superAdminNotificationEmail(): ?string
    {
        $configured = PlatformSetting::get('notifications', 'super_admin_email');

        if (filled($configured) && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return $configured;
        }

        return platform_email_address();
    }
}
