<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SeedSchoolDemoDataJob;
use App\Models\User;
use App\Services\AccountActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Academics\Services\AcademicPresetService;

class ActivationController extends Controller
{
    /**
     * Validate an activation token, distinguishing between tokens that are
     * missing, already consumed, expired, or simply invalid.
     *
     * @return array{user: User|null, reason: string|null}
     */
    protected function resolveToken(string $token): array
    {
        if (blank($token)) {
            return [null, 'missing'];
        }

        $user = User::where('activation_token', $token)->first();

        if (! $user) {
            return [null, 'invalid'];
        }

        if ($user->activation_token_expires_at === null || $user->activation_token_expires_at->isPast()) {
            return [null, 'expired'];
        }

        return [$user, null];
    }

    public function show(Request $request)
    {
        [$user, $reason] = $this->resolveToken((string) $request->query('token', ''));

        if (! $user) {
            return view('auth.activate-invalid', ['reason' => $reason, 'email' => $request->query('email')]);
        }

        return view('auth.activate', ['token' => $request->query('token'), 'user' => $user]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
                'confirmed',
            ],
        ], [
            'password.regex' => 'The password must contain at least 8 characters, at least one capital letter, one number, and one special character.',
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes and underscores.',
        ]);

        [$user, $reason] = $this->resolveToken((string) $request->token);

        if (! $user) {
            $message = $reason === 'expired'
                ? 'This activation link has expired. Please request a new one below.'
                : 'This activation link is invalid or has already been used.';

            throw ValidationException::withMessages(['token' => __($message)]);
        }

        $user->forceFill([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'account_status' => User::STATUS_ACTIVE,
            'activation_token' => null,
            'activation_token_expires_at' => null,
            'activated_at' => now(),
        ])->save();

        // School-activation logic only runs for non-student users (e.g. school
        // admins activating their newly registered school). Student accounts
        // are activated silently — the school is already active.
        $isStudent = $user->isStudent();

        if ($user->school && ! $isStudent) {
            $user->school->update(['status' => 'active', 'trial_ends_at' => now()->addMonths(3)]);
            try {
                app()->instance('current_tenant', $user->school);
                app(AcademicPresetService::class)->applyPreset($user->school->region ?? 'zimbabwe');

                // Demo data is generated in the background after approval
                // (SeedSchoolDemoDataJob), so activation stays fast. This async
                // fallback guarantees the school still gets its data if it was
                // approved before that job existed, without blocking sign-in.
                if ($user->school->has_dummy_data && $user->school->seed_status !== 'seeded') {
                    SeedSchoolDemoDataJob::dispatch($user->school->id);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($user->school) {
            $baseHost = parse_url(config('app.url'), PHP_URL_HOST);
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';

            return redirect("{$scheme}://{$user->school->subdomain}.{$baseHost}/workspace/login")
                ->with('status', __('Account activated successfully! You can now sign in with your new username and password.'));
        }

        return redirect()->route('filament.app.auth.login')
            ->with('status', __('Account activated successfully! You can now sign in with your new username and password.'));
    }

    public function requestForm(Request $request)
    {
        return view('auth.activate-request', ['email' => $request->query('email')]);
    }

    /**
     * Generate a fresh, single-use activation token for an approved-but-not-yet
     * activated account and email a new link to the registered contact.
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email:rfc|max:255',
        ]);

        $user = User::where('email', mb_strtolower(trim($request->email)))->first();

        // Never reveal whether an account exists: always show the generic message
        // for unknown users, suspended schools, or applications that have not yet
        // been approved by a platform administrator (approved_at is only stamped
        // when the "Approve & Send Activation" action runs).
        if (! $user
            || ! $user->school
            || $user->school->status === 'suspended'
            || $user->account_status !== User::STATUS_PENDING
            || $user->approved_at === null) {
            return redirect()->route('account.activate.request')
                ->with('status', __('If a matching approved registration was found, a new activation link has been sent.'));
        }

        app(AccountActivationService::class)->issueAndSend($user);

        return redirect()->route('account.activate.request')
            ->with('status', __('If a matching approved registration was found, a new activation link has been sent.'));
    }
}
