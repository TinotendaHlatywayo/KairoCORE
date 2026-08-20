<?php

namespace Modules\Admin\Services;

use App\Jobs\SendTenantEmailJob;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Message;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\EmailConfiguration;
use Modules\Admin\Notifications\EmailConfigurationMissingNotification;

class TenantEmailConfigurationService
{
    /**
     * Resolve the tenant configuration for a school and category.
     */
    public function forSchool(School $school, EmailCategory $category): ?EmailConfiguration
    {
        return EmailConfiguration::query()
            ->forSchool($school->id)
            ->category($category)
            ->first();
    }

    /**
     * Resolve the tenant configuration for the current tenant (when one is present).
     */
    public function forCurrentTenant(EmailCategory $category): ?EmailConfiguration
    {
        $school = current_tenant();
        if (! $school) {
            return null;
        }

        return $this->forSchool($school, $category);
    }

    /**
     * Create or update the single configuration row for a school + category.
     */
    public function upsert(School $school, EmailCategory $category, array $data): EmailConfiguration
    {
        $fillable = (new EmailConfiguration)->getFillable();

        $attributes = array_intersect_key($data, array_flip($fillable));
        $attributes['school_id'] = $school->id;
        $attributes['category'] = $category->value;

        // A blank password must never overwrite a previously stored one.
        if (array_key_exists('password', $attributes) && empty($attributes['password'])) {
            unset($attributes['password']);
        }

        $config = EmailConfiguration::updateOrCreate(
            ['school_id' => $school->id, 'category' => $category->value],
            $attributes
        );

        Log::info("Email configuration updated for school {$school->id} / {$category->value}.");

        return $config;
    }

    /**
     * Validate a configuration before it can be used to send email.
     * Returns a list of human-readable problems (empty array = ready).
     */
    public function validate(EmailConfiguration $config): array
    {
        $errors = [];

        if (! $config->is_enabled) {
            $errors[] = 'Email is disabled for this category.';
        }

        if (empty($config->from_email) || ! filter_var($config->from_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid "From" email address is required.';
        }

        if ($config->usesPlatformMailer() && is_platform_email($config->from_email)) {
            $errors[] = 'The "From" address may not reuse the platform sending account. Use a school-specific address.';
        }

        if ($config->usesSmtp()) {
            if (empty($config->host)) {
                $errors[] = 'An SMTP host is required when the mailer is set to SMTP.';
            }
            if (empty($config->username) || empty($config->password)) {
                $errors[] = 'SMTP username and password are required when the mailer is set to SMTP.';
            }
            if (! empty($config->port) && ! is_numeric($config->port)) {
                $errors[] = 'The SMTP port must be numeric.';
            }
        }

        return $errors;
    }

    /**
     * The address identity used to send a mailable for the given category.
     *
     * @return array{from: Address, replyTo: ?Address}
     */
    public function identity(EmailConfiguration $config): array
    {
        $fromName = $config->from_name ?: ($config->school->name ?? null);

        $replyTo = null;
        if (! empty($config->reply_to_email)) {
            $replyTo = new Address($config->reply_to_email, $config->reply_to_name);
        }

        return [
            'from' => new Address($config->from_email, $fromName),
            'replyTo' => $replyTo,
        ];
    }

    /**
     * Resolve the runtime mailer name that should be used for this config.
     *
     * - 'platform' mailer delegates to the application's default transport, but
     *   with the tenant's sender identity applied to the mailable.
     * - 'smtp' / 'sendmail' / 'log' register a one-off, school-scoped mailer so
     *   the tenant's own credentials are used end-to-end.
     */
    public function resolveMailer(EmailConfiguration $config): string
    {
        if ($config->usesPlatformMailer()) {
            return config('mail.default', 'log');
        }

        $name = $this->mailerName($config);

        if ($config->usesSmtp()) {
            config([
                "mail.mailers.{$name}" => [
                    'transport' => 'smtp',
                    'scheme' => $config->encryption === 'ssl' ? 'ssl' : ($config->encryption === 'tls' ? 'tls' : null),
                    'host' => $config->host,
                    'port' => $config->port ?: 587,
                    'username' => $config->username,
                    'password' => $config->password,
                    'timeout' => 30,
                    'local_domain' => null,
                ],
            ]);

            return $name;
        }

        // sendmail / log fall back to those runtime mailers.
        config([
            "mail.mailers.{$name}" => ['transport' => $config->mailer],
        ]);

        return $name;
    }

    protected function mailerName(EmailConfiguration $config): string
    {
        return 'tenant_'.$config->school_id.'_'.$config->category;
    }

    /**
     * Whether a school has a valid, enabled configuration for a category.
     */
    public function isUsable(School $school, EmailCategory $category): bool
    {
        $config = $this->forSchool($school, $category);

        return $config !== null && $config->isUsable();
    }

    /**
     * Apply the tenant sender identity + runtime mailer to a MailMessage-based
     * notification. The runtime mailer must have been registered first via
     * resolveMailer(); this method also registers it for convenience.
     */
    public function configureNotificationMailMessage(MailMessage $message, EmailCategory $category, School $school): MailMessage
    {
        $config = $this->forSchool($school, $category);
        if (! $config || ! $config->isUsable()) {
            return $message;
        }

        $mailer = $this->resolveMailer($config);
        $identity = $this->identity($config);

        $message->mailer($mailer);
        $message->from($identity['from']->address, $identity['from']->name);
        if ($identity['replyTo']) {
            $message->replyTo($identity['replyTo']->address, $identity['replyTo']->name);
        }

        // School-signed emails close with "Regards, <School Name>".
        if (filled($school->name)) {
            $message->salutation(__('Regards, ').$school->name);
        }

        return $message;
    }

    /**
     * Send a mailable using the tenant's configuration for the given category.
     * Returns false (and never falls back to platform email) when the tenant
     * configuration is missing or invalid.
     */
    public function send(Mailable $mailable, EmailCategory $category, ?School $school = null): bool
    {
        $config = $school
            ? $this->forSchool($school, $category)
            : $this->forCurrentTenant($category);

        if (! $config) {
            $this->logUnsendable($school, $category, 'No email configuration found.');

            return false;
        }

        $errors = $this->validate($config);
        if (! empty($errors)) {
            $this->logUnsendable($school ?? $config->school, $category, implode(' ', $errors));

            return false;
        }

        try {
            $mailer = $this->resolveMailer($config);
            $identity = $this->identity($config);

            $mailable->from($identity['from']->address, $identity['from']->name);
            if ($identity['replyTo']) {
                $mailable->replyTo($identity['replyTo']->address, $identity['replyTo']->name);
            }

            Mail::mailer($mailer)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            Log::error('Email send failed for tenant.', [
                'school_id' => $config->school_id,
                'category' => $config->category,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate the tenant configuration synchronously, then hand the actual
     * SMTP delivery to the queue so the request never blocks on a round-trip.
     * Returns true when the configuration is valid and the email was accepted
     * for delivery (or sent inline under the sync connection in tests).
     */
    public function queueSend(Mailable $mailable, EmailCategory $category, ?School $school = null): bool
    {
        $config = $school
            ? $this->forSchool($school, $category)
            : $this->forCurrentTenant($category);

        if (! $config) {
            $this->logUnsendable($school, $category, 'No email configuration found.');

            return false;
        }

        $errors = $this->validate($config);
        if (! empty($errors)) {
            $this->logUnsendable($school ?? $config->school, $category, implode(' ', $errors));

            return false;
        }

        SendTenantEmailJob::dispatch($mailable, $category, $config->school_id);

        return true;
    }

    /**
     * Send a test email using the given configuration values (typically the
     * unsaved form state). Returns [success, message]. On success the persisted
     * configuration row (if any) is marked as verified.
     */
    public function sendTestEmail(School $school, EmailCategory $category, array $data, string $to): array
    {
        $config = new EmailConfiguration($data);
        $config->school_id = $school->id;
        $config->category = $category->value;
        $config->is_enabled = true;

        $errors = $this->validate($config);
        if (! empty($errors)) {
            return [false, implode(' ', $errors)];
        }

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return [false, 'A valid recipient email address is required.'];
        }

        try {
            $mailer = $this->resolveMailer($config);
            $identity = $this->identity($config);

            Mail::mailer($mailer)->send(function (Message $message) use ($identity, $to) {
                $message->to($to)
                    ->subject('Test email from '.($identity['from']->name ?: 'your school'))
                    ->from($identity['from']->address, $identity['from']->name)
                    ->html('<p>This is a test email to confirm your school\'s email configuration is working correctly.</p>');
            });

            $this->markVerified($school, $category);

            return [true, 'Test email sent successfully.'];
        } catch (\Throwable $e) {
            return [false, 'Test email failed: '.$e->getMessage()];
        }
    }

    /**
     * Mark the persisted configuration for a school + category as verified.
     */
    public function markVerified(School $school, EmailCategory $category): void
    {
        $config = $this->forSchool($school, $category);
        if (! $config) {
            return;
        }

        $config->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    protected function logUnsendable(?School $school, EmailCategory $category, string $reason): void
    {
        Log::warning('Tenant email skipped.', [
            'school_id' => $school?->id,
            'category' => $category->value,
            'reason' => $reason,
        ]);

        if ($school) {
            $this->notifyMissingConfig($school, $category, $reason);
        }
    }

    /**
     * Alert school administrators (database notification only) that a tenant
     * email was skipped because the configuration is missing or invalid.
     */
    public function notifyMissingConfig(School $school, EmailCategory $category, string $reason): void
    {
        try {
            $roleIds = CustomRole::query()
                ->where('school_id', $school->id)
                ->get()
                ->filter(fn (CustomRole $role) => $this->roleCanManageEmailConfig($role))
                ->pluck('id');

            if ($roleIds->isEmpty()) {
                return;
            }

            $admins = User::query()
                ->where('school_id', $school->id)
                ->whereIn('custom_role_id', $roleIds)
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send(
                $admins,
                new EmailConfigurationMissingNotification($category, $reason)
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins about missing email configuration.', [
                'school_id' => $school->id,
                'category' => $category->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function roleCanManageEmailConfig(CustomRole $role): bool
    {
        if ($role->name === 'Administrator') {
            return true;
        }

        return is_array($role->permissions) && in_array('administration.manage_email_config', $role->permissions);
    }
}
