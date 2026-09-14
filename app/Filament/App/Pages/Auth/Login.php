<?php

namespace App\Filament\App\Pages\Auth;

use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\LoginSecurityService;
use App\Services\UserRegistrationService;
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

    public bool $regAgreeTerms = false;

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
     * A student or staff member may only register when BOTH their identifier
     * (student registration number / staff ID) AND their email address match
     * the details already on file in the school roster. The person's full name
     * on file is used automatically as their account name and username.
     * Accounts are created locked and activated through the emailed link.
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
            'regName' => ['required_if:regRole,administrator', 'nullable', 'string', 'min:2', 'max:100'],
            'regIdentifier' => ['required_unless:regRole,administrator', 'nullable', 'string', 'max:100'],
            'regEmail' => ['required', 'email:rfc', 'max:255'],
            'regPhone' => ['nullable', 'string', 'max:60'],
            'regRole' => ['required', Rule::in(array_keys(User::REGISTRATION_ROLES))],
            'regAgreeTerms' => ['accepted'],
        ], [
            'regName.required' => __('Please enter your full name.'),
            'regName.min' => __('Full name must be at least 2 characters.'),
            'regName.max' => __('Full name must not exceed 100 characters.'),
            'regIdentifier.required' => __('Please enter your student registration number or staff ID.'),
            'regEmail.required' => __('Please enter your email address.'),
            'regEmail.email' => __('The email address is not valid. It should be in the form name@gmail.com.'),
            'regEmail.max' => __('Email address must not exceed 255 characters.'),
            'regAgreeTerms.accepted' => __('You must read and agree to the Platform Terms of Service and School Terms of Conditions before registering.'),
        ]);

        $email = mb_strtolower(trim($this->regEmail));
        $identifier = trim($this->regIdentifier);

        // ─── Roster lookup: identifier AND email must both match the record ───
        $matchedStudent = null;
        $matchedEmployee = null;

        if ($this->regRole === 'student') {
            $matchedStudent = Student::withoutTenantScope()
                ->where('school_id', $school->id)
                ->where(fn ($q) => $q->where('student_id_number', $identifier)->orWhere('admission_number', $identifier))
                ->first();

            if (! $matchedStudent) {
                throw ValidationException::withMessages([
                    'regIdentifier' => __('No student was found with the registration number you entered. Check it against your school ID card and try again.'),
                ]);
            }

            $this->assertRosterEmailMatches($matchedStudent->email, $email);
        } elseif (in_array($this->regRole, ['teaching_staff', 'non_teaching_staff'])) {
            $matchedEmployee = Employee::withoutTenantScope()
                ->where('school_id', $school->id)
                ->where('employee_number', $identifier)
                ->first();

            if (! $matchedEmployee) {
                throw ValidationException::withMessages([
                    'regIdentifier' => __('No staff member was found with the staff ID you entered. Check it against your staff card and try again.'),
                ]);
            }

            $this->assertRosterEmailMatches($matchedEmployee->email, $email);
        }

        // ─── Duplicate detection ───────────────────────────────────────────────
        // Once the roster record is identified, its linked account is the
        // authoritative signal. A floating user row with the same email (but a
        // different roster record) also prevents a second account on one email.
        $roster = $matchedStudent ?? $matchedEmployee;

        $existingUser = null;

        if ($roster && $roster->user_id) {
            $existingUser = User::withTrashed()->find($roster->user_id);
        }

        if (! $existingUser) {
            $existingUser = User::withoutTrashed()
                ->where('school_id', $school->id)
                ->where('email', $email)
                ->first();
        }

        if ($existingUser && ! $existingUser->trashed()) {
            throw ValidationException::withMessages([
                'regEmail' => __('You already have an account. Please sign in below.'),
            ]);
        }

        // Name and username come from the roster record when it exists. Full
        // names may repeat across students — the unique identity is the
        // auto-assigned student / staff ID, not the username.
        $fullName = $matchedStudent
            ? $matchedStudent->full_name
            : ($matchedEmployee
                ? trim("{$matchedEmployee->first_name} {$matchedEmployee->last_name}")
                : trim($this->regName));

        $username = $roster ? $fullName : ($this->regRole === 'administrator' ? $email : $identifier);

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
                'name' => $fullName,
                'username' => $username,
                'phone' => $this->regPhone,
                'requested_role' => $this->regRole,
                'account_status' => User::STATUS_PENDING,
                'password' => Hash::make(Str::random(64)),
            ]);
        } else {
            // Create pending user account with random temporary password
            $user = User::create([
                'school_id' => $school->id,
                'name' => $fullName,
                'email' => $email,
                'username' => $username,
                'phone' => $this->regPhone,
                'password' => Hash::make(Str::random(64)),
                'account_status' => User::STATUS_PENDING,
                'requested_role' => $this->regRole,
            ]);
        }

        // Link the matched roster record to the newly created user account
        // so that the student / staff portal can resolve the profile.
        if ($roster && ! $roster->user_id) {
            $roster->update(['user_id' => $user->id]);
        }

        // Automatically trigger activation email
        try {
            app(AccountActivationService::class)->issueAndSend($user);
        } catch (\Throwable $e) {
            report($e);
        }

        // Notify users.approve permission holders about the pending account.
        // Whether they also receive an EMAIL is the school's choice in
        // System Settings -> Notifications (email_on_user_registration).
        try {
            app(UserRegistrationService::class)->notifyApprovers($school, $user);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->regSubmittedName = $fullName;
        $this->reset('regName', 'regIdentifier', 'regEmail', 'regPhone');
        $this->regRole = 'student';
        $this->regSubmitted = true;
    }

    /**
     * The typed email must exactly match the email on the school roster for
     * the identified student / staff member.
     */
    protected function assertRosterEmailMatches(?string $rosterEmail, string $submittedEmail): void
    {
        $rosterEmail = mb_strtolower(trim((string) $rosterEmail));

        if ($rosterEmail === '') {
            throw ValidationException::withMessages([
                'regEmail' => __('No email address is on file for you. Contact the school office to add one before registering.'),
            ]);
        }

        if ($rosterEmail !== $submittedEmail) {
            throw ValidationException::withMessages([
                'regEmail' => __('The email you entered does not match the email on file for your account. Check and try again.'),
            ]);
        }
    }
}
