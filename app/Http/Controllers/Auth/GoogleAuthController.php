<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Whether Google Single Sign-On is enabled for the platform.
     */
    protected function enabled(): bool
    {
        return (bool) config('services.google.enabled', false);
    }

    public function redirect(Request $request)
    {
        if (! $this->enabled()) {
            abort(404, 'Google sign-in is not enabled.');
        }

        // Socialite manages its own OAuth "state" parameter for CSRF protection;
        // do not override it. The destination panel is derived server-side from
        // the authenticated user's role on callback.
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        if (! $this->enabled()) {
            abort(404, 'Google sign-in is not enabled.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google sign-in failed.', ['error' => $e->getMessage()]);

            return redirect()->route('marketing.home')
                ->withErrors(['google' => 'Google sign-in was not completed. Please try again.']);
        }

        if (! $googleUser || blank($googleUser->getEmail())) {
            return redirect()->route('marketing.home')
                ->withErrors(['google' => 'Google did not return a valid email address.']);
        }

        $email = mb_strtolower(trim($googleUser->getEmail()));
        $user = User::withoutGlobalScopes()
            ->where(fn ($q) => $q->where('google_id', $googleUser->getId())->orWhere('email', $email))
            ->first();

        // Auto-link the Google identity to an existing account on first sign-in.
        if ($user && blank($user->google_id)) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar() ?: $user->avatar,
            ])->save();
        }

        if (! $user) {
            return redirect()->route('marketing.home')
                ->withErrors(['google' => 'No Kairo CORE account matches this Google account. Register your school or sign in with your email and password instead.']);
        }

        if ($user->school_id !== null && $user->account_status !== User::STATUS_ACTIVE) {
            return redirect()->route('marketing.home')
                ->withErrors(['google' => 'Your account is awaiting administrative approval and cannot sign in yet.']);
        }

        // Platform administrators belong to the central host, so they sign in
        // here and are done.
        if ($user->school_id === null) {
            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended('/platform');
        }

        // Tenant users: the OAuth callback ran on the central host, whose
        // session is NOT shared with tenant subdomains (host-only cookies keep
        // every tenant fully isolated). Hand the user a single-use, 60-second
        // ticket and complete the sign-in on THEIR subdomain so the session
        // (and any locale/tenant state) is created in that tenant's scope.
        //
        // Deliberately no Auth::login() here. Signing a tenant user in on the
        // central host would leave a live session on a host that resolves no
        // tenant, where tenant query scopes are not applied at all. The ticket
        // IS the hand-off.
        $subdomain = $user->school?->subdomain;

        if (blank($subdomain)) {
            return redirect()->route('marketing.home')
                ->withErrors(['google' => 'Your account is not linked to a school with a working address. Please contact your administrator.']);
        }

        $baseHost = parse_url(config('app.url'), PHP_URL_HOST);
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';

        $ticket = Str::random(64);
        Cache::put(
            self::ticketKey($ticket),
            ['user_id' => $user->id, 'school_id' => (int) $user->school_id, 'remember' => true],
            now()->addSeconds(self::TICKET_TTL_SECONDS)
        );

        return redirect()->intended(
            "{$scheme}://{$subdomain}.{$baseHost}/auth/sso/consume?ticket={$ticket}"
        );
    }

    /**
     * Lifetime of an SSO exchange ticket, in seconds.
     */
    protected const TICKET_TTL_SECONDS = 60;

    protected static function ticketKey(string $ticket): string
    {
        // Hashed so a cache listing cannot be replayed against the tenant host.
        return 'sso.ticket:'.hash('sha256', $ticket);
    }

    /**
     * Consume a single-use SSO ticket ON THE TENANT SUBDOMAIN and establish
     * the authenticated session there. The ticket exists only in the central
     * cache for one minute and is destroyed on first use.
     */
    public function consume(Request $request)
    {
        $ticket = (string) $request->query('ticket');

        $payload = $ticket !== '' ? Cache::pull(self::ticketKey($ticket)) : null;

        if (! is_array($payload) || blank($payload['user_id'] ?? null)) {
            return redirect()
                ->to(Filament::getLoginUrl())
                ->withErrors(['google' => 'This single sign-on link has expired. Please sign in again.']);
        }

        /** @var User|null $user */
        $user = User::withoutGlobalScopes()->find($payload['user_id']);

        // current_tenant is bound by the `tenant` middleware this route is
        // registered under. Read it defensively anyway: an unbound container
        // would otherwise throw and turn a refused sign-in into a 500.
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (
            ! $user
            || blank($user->school_id)
            || $user->account_status !== User::STATUS_ACTIVE
            || ! $tenant
            || (int) $user->school_id !== (int) $tenant->id
            // The ticket names the school it was minted for. Re-check it so a
            // ticket cannot be moved between subdomains even if the user has
            // since been moved to another school.
            || (int) ($payload['school_id'] ?? 0) !== (int) $tenant->id
        ) {
            return redirect()
                ->to(Filament::getLoginUrl())
                ->withErrors(['google' => 'Google sign-in could not be completed for this school.']);
        }

        Auth::login($user, (bool) ($payload['remember'] ?? true));
        $request->session()->regenerate();

        return redirect()->intended('/workspace');
    }
}
