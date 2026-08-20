<?php

namespace Modules\Admin\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\Admin\Models\SystemAuditLog;

class AuditLogger
{
    /**
     * Log administrative transactions instantly.
     */
    public static function log(string $action, string $module, ?array $oldValues = null, ?array $newValues = null, string $outcome = 'success'): void
    {
        $schoolId = session('current_tenant')?->id;
        if (! $schoolId && Auth::check()) {
            /** @var User|null $user */
            $user = Auth::user();
            $schoolId = $user ? $user->school_id : null;
        }

        if (! $schoolId) {
            return; // Tenancy boundary not resolved yet
        }

        SystemAuditLog::create([
            'school_id' => $schoolId,
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'outcome' => $outcome,
        ]);
    }
}
