<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                ->withErrors(['google' => 'No SchoolCore account matches this Google account. Register your school or sign in with your email and password instead.']);
        }

        if ($user->school_id !== null && $user->account_status !== User::STATUS_ACTIVE) {
            return redirect()->route('marketing.home')
                ->withErrors(['google' => 'Your account is awaiting administrative approval and cannot sign in yet.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->school_id === null) {
            return redirect()->intended('/platform');
        }

        $subdomain = $user->school?->subdomain;
        $baseHost = parse_url(config('app.url'), PHP_URL_HOST);
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';

        return redirect()->intended("{$scheme}://{$subdomain}.{$baseHost}/workspace");
    }
}
