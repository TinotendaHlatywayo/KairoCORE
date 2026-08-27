<?php

namespace Modules\DigitalAssessment\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Admin\Services\PermissionRegistry;
use Modules\DigitalAssessment\Models\QuestionBank;

class QuestionBankPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.manage_questions');
    }

    public function view(User $user, QuestionBank $question): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.manage_questions');
    }

    public function create(User $user): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.manage_questions');
    }

    public function update(User $user, QuestionBank $question): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.manage_questions');
    }

    public function delete(User $user, QuestionBank $question): bool
    {
        return PermissionRegistry::checkPermission('digital_assessment.manage_questions');
    }
}
