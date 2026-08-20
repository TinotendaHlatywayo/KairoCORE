<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks unapproved (pending / rejected / suspended) school accounts out of the
 * workspace immediately, even if a stale session already authenticated them.
 *
 * Pending registrations cannot enter any protected school functionality; the
 * session is destroyed so a reload bounces them back to the login screen.
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->school_id !== null && $user->account_status !== User::STATUS_ACTIVE) {
            Filament::auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest(Filament::getLoginUrl());
        }

        return $next($request);
    }
}
