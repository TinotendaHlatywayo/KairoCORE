<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    /**
     * Enforces that only users with NULL school_id (SaaS Platform Super-Admins)
     * can view, create, edit, or delete School records.
     */
    public function viewAny(User $user): bool
    {
        return $user->school_id === null;
    }

    public function view(User $user, School $school): bool
    {
        return $user->school_id === null;
    }

    public function create(User $user): bool
    {
        return $user->school_id === null;
    }

    public function update(User $user, School $school): bool
    {
        return $user->school_id === null;
    }

    public function delete(User $user, School $school): bool
    {
        return $user->school_id === null;
    }
}
