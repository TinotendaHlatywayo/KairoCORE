<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\CMS\Models\CmsWebsite;

class CmsWebsitePolicy
{
    use HandlesAuthorization;

    public function viewAny($user): bool
    {
        return true;
    }

    public function view($user, CmsWebsite $model): bool
    {
        return true;
    }

    public function create($user): bool
    {
        return true;
    }

    public function update($user, CmsWebsite $model): bool
    {
        return true;
    }

    public function delete($user, CmsWebsite $model): bool
    {
        return true;
    }
}
