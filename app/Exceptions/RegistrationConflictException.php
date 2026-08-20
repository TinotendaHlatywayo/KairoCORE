<?php

namespace App\Exceptions;

use App\Models\User;

/**
 * Thrown when a user registration collides with an existing account for the
 * same school + email. Carries the conflicting account so the caller can
 * present a "replace or merge" choice instead of a raw unique-constraint error.
 */
class RegistrationConflictException extends \Exception
{
    public function __construct(
        public readonly ?User $conflictingUser = null,
    ) {
        parent::__construct('A user account with this email already exists.');
    }
}
