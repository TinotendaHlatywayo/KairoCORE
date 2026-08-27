<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetUserLocale
{
    public function handle(Request $request, Closure $next)
    {
        App::setLocale(resolve_app_locale());

        return $next($request);
    }
}