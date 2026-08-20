<?php

declare(strict_types=1);

use App\Http\Middleware\ForceSessionCookieScope;
use App\Http\Middleware\NoStoreHtml;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SetUserLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Exclude only external service webhooks that cannot provide browser CSRF tokens
        $middleware->validateCsrfTokens(except: [
            'saas/paynow/webhook',
        ]);

        // Runs before everything so a stale broader-domain session cookie
        // (left over from a previous SESSION_DOMAIN) can never keep shadowing
        // the canonical host-only cookie and corrupting sessions/CSRF.
        $middleware->prepend([
            ForceSessionCookieScope::class,
        ]);

        // Never let the browser heuristically cache HTML pages. Filament uses
        // SPA navigation and Vite emits hashed asset URLs, so a stale page
        // HTML copy would keep referencing deleted CSS builds and render the
        // UI completely unstyled (giant icons, raw fallbacks).
        $middleware->append(NoStoreHtml::class);
        $middleware->append(SetUserLocale::class);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
        ]);

        // When auth middleware rejects a guest, it tries route('login') but that
        // route lives inside the {tenant} domain group. Build the redirect URL
        // using the current request host so the {tenant} param is included.
        $middleware->redirectGuestsTo(function ($request) {
            $host = $request->getHost();

            return $host.'/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
