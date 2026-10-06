<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use App\Services\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fails closed when the central host is asked to serve the tenant workspace.
 *
 * The app panel is registered without ->domain(), so /workspace resolves on
 * both the platform host and every school subdomain. ResolveTenant deliberately
 * binds nothing on the platform host — which means TenantScope sees no
 * current_tenant and silently applies no filter at all, returning rows from
 * every school in one query.
 *
 * Reachable in production: a tenant user signs in with a password at
 * https://kairocore.me/workspace/login, which mints a central-host session
 * cookie. Google sign-in no longer creates that session, but password login
 * still does, and every subsequent workspace request then ran unscoped.
 *
 * Rather than refuse the request outright, resolve the tenant from the
 * signed-in user's own school. The workspace renders with normal tenant
 * scoping and the person sees their own school — nothing else.
 *
 * This middleware either binds a tenant or aborts; it never passes a request
 * through unbound. Guests and platform administrators are normally stopped
 * earlier by SchoolPanelAuthenticate (they cannot reach this point), but this
 * is not relied on: reordered or dropped auth middleware must not be able to
 * produce an unscoped workspace.
 *
 * Runs in the panel's auth middleware, which is after StartSession, so the
 * authenticated user is available here — ResolveTenant runs before the session
 * starts and therefore cannot do this itself.
 */
class EnsureWorkspaceTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Str::startsWith($request->path(), 'workspace')) {
            return $next($request);
        }

        // A tenant is already bound (a real subdomain, or an explicit override
        // such as the one the tenant-isolation tests set up). Nothing to do.
        if (App::has('current_tenant')) {
            return $next($request);
        }

        $school = $this->tenantForSignedInUser();

        if (! $school) {
            // No school can be resolved, so there is nothing to scope to.
            abort(404, 'The workspace is only available under a school subdomain.');
        }

        $this->bind($school);

        return $next($request);
    }

    private function tenantForSignedInUser(): ?School
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || blank($user->school_id)) {
            return null;
        }

        return School::withoutGlobalScopes()->find($user->school_id);
    }

    /**
     * Mirror what ResolveTenant does for a real subdomain request, minus the
     * root URL override — on the platform host the root is already correct.
     */
    private function bind(School $school): void
    {
        App::instance('current_tenant', $school);

        view()->share('school', $school);
        view()->share('features', TenantFeatureService::all());

        URL::defaults(['tenant' => $school->subdomain]);
    }
}
