<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;

/**
 * Replaces Filament's stock Authenticate middleware for school panels
 * (workspace + student portal).
 *
 * The stock middleware hard-aborts 403 whenever an authenticated user cannot
 * access the current panel (e.g. a student landing on /workspace or a staff
 * member landing on /student). This middleware instead redirects the user to
 * the panel they are allowed into, or back to the login page if no panel is
 * accessible.
 */
class SchoolPanelAuthenticate extends Authenticate
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
            $panel = collect(Filament::getPanels())
                ->first(fn ($panel) => $user->canAccessPanel($panel));

            if ($panel) {
                return redirect()->to($panel->getUrl());
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest(Filament::getLoginUrl());
        }

        return $next($request);
    }
}
