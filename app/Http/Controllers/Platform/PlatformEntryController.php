<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Services\PlatformImpersonationService;
use RuntimeException;

/**
 * Endpoints for the platform -> school hand-off.
 *
 * Both live on the TENANT subdomain (see routes/web.php). They sit outside
 * /workspace so they cannot collide with a Filament resource route, and the
 * exchange route deliberately does not reuse the Google SSO pattern, whose
 * callback host does not match the domain group it is registered under.
 */
class PlatformEntryController extends Controller
{
    /**
     * Exchange a single-use ticket for a real session inside this school.
     */
    public function enter(Request $request, PlatformImpersonationService $service)
    {
        $tenant = app('current_tenant');

        if (! $tenant instanceof School) {
            abort(404);
        }

        try {
            $redeemed = $service->redeemTicket((string) $request->query('ticket'), $tenant);
        } catch (RuntimeException $e) {
            // Land back on the PLATFORM, not on a tenant login page. The two
            // hosts hold separate session cookies, so a flashed error would
            // never render there; carry the reason in the query string so the
            // schools page can show exactly why entry failed (expired link,
            // wrong school, …) instead of silently bouncing to a login form.
            return redirect()
                ->to($service->platformSchoolsUrl().'?notice=entry_failed&reason='.rawurlencode($e->getMessage()));
        }

        $as = $redeemed['user'];
        $platformAdmin = $redeemed['platform_admin'];

        // No remember-me: a hand-off is short-lived by design and must not
        // outlive the browser session as a persistent cookie.
        Auth::login($as, false);
        $request->session()->regenerate();

        $service->rememberEntry($platformAdmin, $tenant, $as);

        return redirect()->to(Filament::getPanel('app')->getUrl());
    }

    /**
     * Leave the school. The central platform session was never touched, so the
     * administrator simply lands back on /platform/schools already signed in.
     */
    public function exit(Request $request, PlatformImpersonationService $service)
    {
        $service->forgetEntry('exited');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($service->platformSchoolsUrl());
    }
}
