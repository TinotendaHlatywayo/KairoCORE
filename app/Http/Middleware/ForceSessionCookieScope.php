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
        // TRUSTED_PROXIES=* makes Symfony read X-Forwarded-Port/Host for every
        // request. A malformed client (scanners, some bots) sending an EMPTY
        // value crashed getPort() with "Uninitialized string offset 0" → 500.
        // Drop empty forwarded headers before TrustProxies consumes them.
        foreach (['X-Forwarded-Port', 'X-Forwarded-Host', 'X-Forwarded-Proto', 'X-Forwarded-For'] as $header) {
            $value = $request->headers->get($header);
            if ($value !== null && trim((string) $value) === '') {
                $request->headers->remove($header);
            }
        }

        $sessionName = config('session.cookie');
        $sessionDomain = config('session.domain');

        $legacyDomains = [];
        if (blank($sessionDomain)) {
            $hosts = array_filter([
                parse_url((string) config('app.url'), PHP_URL_HOST),
                $request->getHost(),
            ]);

            // Also cover base hosts for subdomains (e.g. rujeko.lvh.me -> lvh.me)
            $knownBases = ['lvh.me', 'localhost'];
            foreach ($hosts as $host) {
                foreach ($knownBases as $base) {
                    if ($host === $base || str_ends_with($host, '.'.$base)) {
                        $hosts[] = $base;
                    }
                }
            }

            foreach (array_unique($hosts) as $host) {
                $legacyDomains[] = '.'.$host;
            }
        }

        $response = $next($request);

        if (blank($sessionDomain)) {
            foreach (array_unique($legacyDomains) as $legacyDomain) {
                $this->expireLegacyDomainCookie($response, $sessionName, $legacyDomain);
            }
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
