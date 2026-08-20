<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Issues secure, time-sensitive, single-use activation tokens and delivers
 * the corresponding activation email to the registered contact.
 */
class AccountActivationService
{
    /**
     * Lifetime of an activation token in hours (default 48).
     */
    public function ttlHours(): int
    {
        return (int) config('auth.activation_token_ttl_hours', 48);
    }

    /**
     * Cryptographically secure, URL-safe token (not guessable).
     */
    public function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Persist a fresh token and its expiry on the user. Invalidates any
     * previously issued token so only the newest link can be used.
     */
    public function issueToken(User $user): string
    {
        $token = $this->generateToken();

        $user->forceFill([
            'activation_token' => $token,
            'activation_token_expires_at' => now()->addHours($this->ttlHours()),
        ])->save();

        return $token;
    }

    /**
     * Send the activation email. The message contains only the activation link
     * (no passwords) and is sent from the platform sender identity.
     */
    public function sendActivationEmail(School $school, User $user, string $token): bool
    {
        try {
            Notification::route('mail', $user->email)
                ->notify(new AccountActivationNotification($school, $user, $token));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Issue a fresh token and email it to the contact. Returns the token on
     * success or null when the email could not be delivered.
     */
    public function issueAndSend(User $user): ?string
    {
        $school = $user->school;
        if (! $school) {
            return null;
        }

        $token = $this->issueToken($user);

        if (! $this->sendActivationEmail($school, $user, $token)) {
            return null;
        }

        return $token;
    }
}
