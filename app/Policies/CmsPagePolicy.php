<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\CMS\Models\CmsPage;

class CmsPagePolicy
{
    use HandlesAuthorization;

    public function viewAny($user): bool
    {
        return true;
    }

    public function view($user, CmsPage $model): bool
    {
        return true;
    }

    public function create($user): bool
    {
        return true;
    }

    public function update($user, CmsPage $model): bool
    {
        return true;
    }

    public function delete($user, CmsPage $model): bool
    {
        return true;
    }
}
