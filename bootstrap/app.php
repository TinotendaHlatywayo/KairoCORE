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

        // Set the app locale from session/user/school. This runs inside the
        // "web" group AFTER StartSession so session('locale') and
        // auth()->user() are already available — the global position would run
        // before the session is started and force every request to English.
        // The {tenant} routes also re-run it after ResolveTenant so the school
        // locale is applied (see routes/web.php).
        $middleware->web(append: [
            SetUserLocale::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
        ]);

        // When auth middleware rejects a guest, send them to the LOGIN PAGE of
        // the panel they were trying to reach. The URL must be ABSOLUTE
        // (scheme + host): returning a bare "host/path" string makes Laravel
        // treat it as a relative path and produce a broken 404 redirect.
        // Panel-awareness matters because each portal owns its own login
        // screen (workspace/staff, student, platform super-admin).
        $middleware->redirectGuestsTo(function ($request) {
            $path = trim($request->path(), '/');

            $loginPath = match (true) {
                $path === 'platform' || str_starts_with($path, 'platform/') => '/platform/login',
                $path === 'student' || str_starts_with($path, 'student/') => '/student/login',
                default => '/workspace/login',
            };

            return $request->getSchemeAndHttpHost().$loginPath;
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['message' => 'The requested resource was not found.'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['message' => 'Too many requests. Please slow down.'], 429);
            }
            return response()->view('errors.429', [], 429);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['message' => 'Access denied.'], 403);
            }
            return response()->view('errors.419', [], 419);
        });

        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['message' => 'Page expired. Please try again.'], 419);
            }
            return response()->view('errors.419', [], 419);
        });

        $exceptions->renderable(function (\Exception $e) {
            // NEVER swallow framework-managed exceptions: returning null here
            // falls through to Laravel's default handling, which is what makes
            // AuthenticationException redirect guests to the LOGIN page,
            // ValidationException return 422, and HttpExceptions keep their
            // status codes. Turning them into a 500 page broke the login
            // redirect for unauthenticated workspace visits.
            if (
                $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ) {
                return null;
            }

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['message' => 'An internal server error occurred.'], 500);
            }
            return response()->view('errors.500', [], 500);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['message' => 'Service temporarily unavailable.'], 503);
            }
            return response()->view('errors.503', [], 503);
        });
    })->create();
