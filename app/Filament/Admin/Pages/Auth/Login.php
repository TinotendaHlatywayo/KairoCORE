<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Services\LoginSecurityService;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.admin.pages.auth.login';

    protected static string $layout = 'filament.auth.login-layout';

    public ?string $notice = null;

    public function mount(): void
    {
        parent::mount();

        // Automatically default remember to true so login never fails due to session cookie drops
        $this->form->fill([
            'email' => '',
            'password' => '',
            'remember' => true,
        ]);

        $this->notice = match (request()->query('notice')) {
            'school_user' => __('You were signed in as a school user. Please sign in below with your platform administrator credentials.'),
            default => null,
        };
    }

    /**
     * Sign in with a generic failure message so we never leak whether the
     * email or the password was the incorrect part.
     */
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $email = $data['email'] ?? $data['username'] ?? '';
        $ip = request()->ip();

        // 3-Tiered login security check
        LoginSecurityService::ensureNotRateLimited($email, $ip);

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            LoginSecurityService::hit($email, $ip);
            $remaining = LoginSecurityService::getRemainingAttempts($email);

            $msg = 'The email or password you entered is incorrect.';
            if ($remaining < 3) {
                $msg = "The email or password you entered is incorrect. You have {$remaining} attempts remaining before your account is temporarily locked.";
            }

            throw ValidationException::withMessages([
                'data.email' => $msg,
            ]);
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();
            LoginSecurityService::hit($email, $ip);
            $remaining = LoginSecurityService::getRemainingAttempts($email);

            $msg = 'The email or password you entered is incorrect.';
            if ($remaining < 3) {
                $msg = "The email or password you entered is incorrect. You have {$remaining} attempts remaining before your account is temporarily locked.";
            }

            throw ValidationException::withMessages([
                'data.email' => $msg,
            ]);
        }

        session()->regenerate();
        LoginSecurityService::clear($email, $ip);

        return app(LoginResponse::class);
    }
}
