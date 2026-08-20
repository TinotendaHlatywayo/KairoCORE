<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If not logged in, pass request to let Filament handle redirection to login
        if (! auth()->check()) {
            return $next($request);
        }

        // 2. Security Check: If school_id is NOT null, this is a school user trying to breach the platform.
        //    Invalidate their session and send them to the platform login instead of a dead-end 403 so
        //    they can sign in with super-admin credentials.
        if (auth()->user()->school_id !== null) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login', ['notice' => 'school_user']);
        }

        return $next($request);
    }
}
