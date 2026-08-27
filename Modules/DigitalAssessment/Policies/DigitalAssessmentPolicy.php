<?php

namespace Modules\DigitalAssessment\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Admin\Services\PermissionRegistry;
use Modules\DigitalAssessment\Models\DigitalAssessment;

class DigitalAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.create_assessments')
            || PermissionRegistry::checkPermission('digital_assessment.view_assessments');
    }

    public function view(User $user, DigitalAssessment $assessment): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.create_assessments')
            || PermissionRegistry::checkPermission('digital_assessment.view_assessments');
    }

    public function create(User $user): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.create_assessments');
    }

    public function update(User $user, DigitalAssessment $assessment): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.create_assessments');
    }

    public function delete(User $user, DigitalAssessment $assessment): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.create_assessments');
    }
}
