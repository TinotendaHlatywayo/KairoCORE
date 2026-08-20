<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetUserLocale
{
    public function handle(Request $request, Closure $next)
    {
        $school = current_tenant();
        $user = auth()->user();

        $locale = session('locale') ?: ($user?->locale ?: ($school?->locale ?: config('app.fallback_locale', 'en')));

        $supported = ['en', 'sn', 'sw', 'fr', 'pt', 'es'];
        if (! in_array($locale, $supported, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
