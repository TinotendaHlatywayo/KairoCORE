<?php

namespace Modules\Admin\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\Admin\Models\SystemAuditLog;

class AuditLogger
{
    /**
     * >0 while platform-owned work is running that must stay invisible to the
     * tenant (e.g. provisioning the shadow account during "Enter school").
     * Counted, so nested mutes are safe.
     */
    private static int $muted = 0;

    /**
     * Run $callback with tenant audit logging suppressed.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function mute(callable $callback): mixed
    {
        self::$muted++;

        try {
            return $callback();
        } finally {
            self::$muted--;
        }
    }

    /**
     * Log administrative transactions instantly.
     */
    public static function log(string $action, string $module, ?array $oldValues = null, ?array $newValues = null, string $outcome = 'success'): void
    {
        if (self::$muted > 0) {
            return;
        }

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
