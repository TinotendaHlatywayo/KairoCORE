<?php

namespace App\Filament\App\Pages\Auth;

use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\LoginSecurityService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportRedirects\Redirector;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;

class Login extends BaseLogin
{
    protected static string $view = 'filament.app.pages.auth.login';

    protected static string $layout = 'filament.auth.login-layout';

    // ── Inline "Create Account" registration state ────────────────────────
    public string $regName = '';

    public string $regIdentifier = '';

    public string $regEmail = '';

    public string $regPhone = '';

    public string $regRole = 'student';

    public bool $regSubmitted = false;

    public string $regSubmittedName = '';

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => '',
            'password' => '',
            'remember' => false,
        ]);
    }

    /**
     * Sign the user in. Credential failures always surface a generic message
     * ("wrong email or password") rather than revealing which part was wrong.
     * On success we flash a time-of-day greeting for the dashboard banner.
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

        // Cross-panel routing: a valid account that cannot enter THIS panel
        // (e.g. a student landing on the staff workspace login) is allowed
        // through and redirected to whichever registered panel it CAN access.
        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            $accessible = collect(Filament::getPanels())
                ->first(fn ($panel) => $user->canAccessPanel($panel));

            if (! $accessible) {
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
        }

        session()->regenerate();
        LoginSecurityService::clear($email, $ip);

        if ($user) {
            session()->flash('login_greeting', [
                'message' => match ((int) now()->format('G')) {
                    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 => 'Good morning',
                    12, 13, 14, 15, 16 => 'Good afternoon',
                    default => 'Good evening',
                },
                'name' => $user->name,
            ]);
        }

        return new class($this) implements LoginResponse
        {
            public function __construct(private Login $login) {}

            public function toResponse($request): RedirectResponse|Redirector
            {
                return $this->login->resolveLoginRedirect();
            }
        };
    }

    /**
     * Send the freshly-authenticated user to the panel they are allowed into.
     * Students always land in the student portal; staff stay in the workspace;
     * platform admins go to the admin panel.
     */
    public function resolveLoginRedirect(): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if ($user instanceof User) {
            $panel = collect(Filament::getPanels())
                ->first(fn ($panel) => $user->canAccessPanel($panel));

            if ($panel) {
                return redirect()->to($panel->getUrl());
            }
        }

        return redirect()->intended(Filament::getUrl());
    }

    public function getRegistrationRoleOptions(): array
    {
        return User::REGISTRATION_ROLES;
    }

    /**
     * Create a PENDING user account for the current school.
     *
     * Accounts are never created active: an administrator with the
     * "users.approve" permission must review and activate the account before
     * it can sign in. New registrations are invalidated after a throttled
     * number of attempts per IP address.
     */
    public function registerAccount(): void
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        // Honeypot: real humans never fill this hidden field. Bots are silently
        // dropped without any error so they can't learn the trap is here.
        if (filled(request()->input('website'))) {
            return;
        }

        $school = current_tenant();
        if (! $school) {
            throw ValidationException::withMessages([
                'regEmail' => 'Self-registration is not available on this platform.',
            ]);
        }

        $this->validate([
            'regName' => ['required', 'string', 'min:2', 'max:100'],
            'regIdentifier' => ['required_if:regRole,student,teaching_staff,non_teaching_staff', 'nullable', 'string', 'max:100'],
            'regEmail' => ['required', 'email:rfc', 'max:255'],
            'regPhone' => ['nullable', 'string', 'max:60'],
            'regRole' => ['required', Rule::in(array_keys(User::REGISTRATION_ROLES))],
        ], [
            'regName.required' => __('Please enter your full name.'),
            'regName.min' => __('Full name must be at least 2 characters.'),
            'regName.max' => __('Full name must not exceed 100 characters.'),
            'regIdentifier.required_if' => __('Please enter your student registration number or staff ID.'),
            'regEmail.required' => __('Please enter your email address.'),
            'regEmail.email' => __('The email address is not valid. It should be in the form name@gmail.com.'),
            'regEmail.max' => __('Email address must not exceed 255 characters.'),
        ]);

        $email = mb_strtolower(trim($this->regEmail));
        $identifier = trim($this->regIdentifier);

        // Duplicate detection: block when an active account already uses this
        // email or username for the current school. Administrators register
        // without a school identifier, so the username match is skipped for them.
        $existingUser = User::withoutTrashed()
            ->where('school_id', $school->id)
            ->where(function ($q) use ($email, $identifier) {
                $q->where('email', $email);
                if ($identifier !== '') {
                    $q->orWhere('username', $identifier);
                }
            })
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'regEmail' => __('User already has an account. Please sign in below.'),
            ]);
        }

        // Verify identifier against student or employee records
        $matchedStudent = null;

        if ($this->regRole === 'student') {
            $matchedStudent = Student::where('school_id', $school->id)
                ->where(fn ($q) => $q->where('student_id_number', $identifier)->orWhere('admission_number', $identifier))
                ->first();

            if (! $matchedStudent) {
                throw ValidationException::withMessages([
                    'regIdentifier' => __('Student record not found with this registration number. Please check and try again.'),
                ]);
            }
        } elseif (in_array($this->regRole, ['teaching_staff', 'non_teaching_staff'])) {
            $employee = Employee::where('school_id', $school->id)
                ->where(fn ($q) => $q->where('employee_number', $identifier)->orWhere('email', $email))
                ->first();

            if (! $employee) {
                throw ValidationException::withMessages([
                    'regIdentifier' => __('Staff member record not found with this staff identifier or email. Please check and try again.'),
                ]);
            }
        }

        // A soft-deleted (removed) account still occupies the unique
        // (school_id, email) index, so creating a fresh row would fail with an
        // integrity-constraint violation. If a removed account owns this email,
        // reactivate it as a new pending registration instead.
        $removedUser = User::onlyTrashed()
            ->where('school_id', $school->id)
            ->where('email', $email)
            ->first();

        $user = $removedUser;

        if ($removedUser) {
            $removedUser->restore();
            $removedUser->update([
                'name' => $this->regName,
                'username' => $identifier,
                'phone' => $this->regPhone,
                'requested_role' => $this->regRole,
                'account_status' => User::STATUS_PENDING,
                'password' => Hash::make(Str::random(64)),
            ]);
        } else {
            // Create pending user account with random temporary password
            $user = User::create([
                'school_id' => $school->id,
                'name' => $this->regName,
                'email' => $email,
                'username' => $identifier,
                'phone' => $this->regPhone,
                'password' => Hash::make(Str::random(64)),
                'account_status' => User::STATUS_PENDING,
                'requested_role' => $this->regRole,
            ]);
        }

        // Link the matched student record to the newly created user account
        // so that the student portal can resolve the profile on first login.
        if ($matchedStudent && ! $matchedStudent->user_id) {
            $matchedStudent->update(['user_id' => $user->id]);
        }

        // Automatically trigger activation email
        try {
            app(AccountActivationService::class)->issueAndSend($user);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->regSubmittedName = $this->regName;
        $this->reset('regName', 'regIdentifier', 'regEmail', 'regPhone');
        $this->regRole = 'student';
        $this->regSubmitted = true;
    }
}
