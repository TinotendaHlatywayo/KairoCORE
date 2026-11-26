<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Security\DemoPersona;
use App\Services\DemoAccountService;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DemoLoginController extends Controller
{
    public function show(): \Illuminate\Contracts\View\View
    {
        return view('marketing.demo-login');
    }

    public function login(Request $request, DemoAccountService $demo): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        if (
            $email !== mb_strtolower((string) config('demo.email'))
            || ! hash_equals((string) config('demo.password'), $data['password'])
        ) {
            throw ValidationException::withMessages([
                'email' => __('Those demo credentials are not correct.'),
            ]);
        }

        $account = null;

        if (config('demo.auto_provision', true)) {
            try {
                $account = $demo->ensureAccount();
            } catch (Throwable $e) {
                report($e);

                throw ValidationException::withMessages([
                    'email' => __('The demo environment is not ready yet. Please try again shortly.'),
                ]);
            }
        }

        $user = $demo->findByCredentials($email, $data['password']);

        if (! $user || ($account && $user->id !== $account->id) || ! DemoPersona::isDemoUser($user)) {
            throw ValidationException::withMessages([
                'email' => __('Those demo credentials are not correct.'),
            ]);
        }

        return redirect()->away(
            $demo->entryUrl($user->school, $demo->issueTicket($user))
        );
    }

    public function consume(Request $request, DemoAccountService $demo): \Illuminate\Http\RedirectResponse
    {
        $user = $demo->redeemTicket((string) $request->query('ticket', ''));

        if (! $user) {
            return redirect()->to(Filament::getLoginUrl())
                ->with('error', __('This demo link has expired. Please sign in again.'));
        }

        $demo->signIn($user);

        return redirect()->intended('/workspace');
    }
}
