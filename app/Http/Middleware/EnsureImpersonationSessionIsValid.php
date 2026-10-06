<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Services\PlatformImpersonationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the lifetime of a platform -> school hand-off.
 *
 * Ordinary tenant sessions have no impersonation payload and pass straight
 * through untouched. When one has expired the session is destroyed outright
 * rather than downgraded, so a hand-off can never quietly become a permanent
 * back door into a school.
 */
class EnsureImpersonationSessionIsValid
{
    public function __construct(protected PlatformImpersonationService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $entry = $this->service->current();

        if ($entry === null) {
            return $next($request);
        }

        if ($this->service->isExpired($entry)) {
            $this->service->forgetEntry('expired');

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->to($this->service->platformSchoolsUrl())
                ->with('platform_impersonation_expired', $entry['school_name'] ?? null);
        }

        view()->share('platformImpersonation', $entry);

        return $next($request);
    }
}
