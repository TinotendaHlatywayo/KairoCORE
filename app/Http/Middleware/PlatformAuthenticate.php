<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;

/**
 * Replaces Filament's built-in Authenticate middleware for the platform panel.
 *
 * Filament aborts 403 whenever an authenticated user cannot access a panel
 * (e.g. a school user whose session cookie is shared across the .lvh.me
 * domain). That turns "Platform Login" into a dead-end 403 for anyone who
 * happens to hold a school session. Instead we gracefully log the school user
 * out and send them to the platform login so they can sign in as a
 * super administrator.
 */
class PlatformAuthenticate extends Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            return $this->unauthenticated($request, $guards);
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        $user = $guard->user();

        if ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentPanel())) {
            // School user hitting the platform panel: kill the session and ask
            // them to sign in with super-admin credentials.
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest(Filament::getLoginUrl().'?notice=school_user');
        }

        return $next($request);
    }

    /**
     * Guests get the session_expired notice on the login page so the redirect
     * is explained (Filament's stock redirectTo() returns the bare login URL).
     * The school_user path above keeps its own explicit notice.
     */
    protected function redirectTo($request): ?string
    {
        $loginUrl = Filament::getLoginUrl();

        return $loginUrl ? $loginUrl.'?notice=session_expired' : null;
    }
}
