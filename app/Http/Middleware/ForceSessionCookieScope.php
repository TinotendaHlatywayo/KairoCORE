<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Self-healing guard against stale session cookies scoped to a broader domain
 * (e.g. a leftover ".lvh.me" cookie from when SESSION_DOMAIN was overridden).
 *
 * A browser can silently hold two cookies with the same name but different
 * domains. The broader-domain cookie shadows the canonical host-only cookie,
 * which corrupts the session id the server reads and breaks CSRF on every POST
 * (login, logout, Livewire, chat). Because the server sets its own cookie with
 * a blank (host-only) domain, it can never overwrite the stale broader-domain
 * cookie - so we explicitly delete that legacy cookie on every response.
 */
class ForceSessionCookieScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionName = config('session.cookie');
        $sessionDomain = config('session.domain');

        // Capture the base domain BEFORE the request is handled: tenant
        // resolvers (ResolveTenant) reassign config('app.url') to the current
        // subdomain host, which would make us target the wrong cookie scope.
        $legacyDomain = null;
        if (blank($sessionDomain)) {
            $baseHost = parse_url(config('app.url'), PHP_URL_HOST);
            if ($baseHost) {
                $legacyDomain = '.'.$baseHost;
            }
        }

        $response = $next($request);

        // Only the blank (host-only) scope is valid for this multi-panel,
        // multi-subdomain architecture. If a domain was configured the server
        // is already setting the cookie at the right scope and there is
        // nothing to clean up.
        if ($legacyDomain !== null) {
            $this->expireLegacyDomainCookie($response, $sessionName, $legacyDomain);
        }

        return $response;
    }

    protected function expireLegacyDomainCookie(Response $response, string $name, string $legacyDomain): void
    {
        $response->headers->setCookie(
            new Cookie(
                $name,
                '',
                time() - 3600,
                '/',
                $legacyDomain,
                false,
                true,
                false,
                'Lax'
            )
        );
    }
}
