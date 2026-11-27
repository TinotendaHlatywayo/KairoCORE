<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\DemoBusyException;
use App\Http\Controllers\Controller;
use App\Security\DemoPersona;
use App\Services\DemoAccountService;
use App\Services\DemoSandboxService;
use App\Support\DemoSandbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DemoLoginController extends Controller
{
    public function show(): View
    {
        return view('marketing.demo-login');
    }

    public function login(Request $request, DemoAccountService $demo, DemoSandboxService $sandboxes): RedirectResponse
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

        if (config('demo.auto_provision', true)) {
            try {
                $demo->ensureAccount();
            } catch (Throwable $e) {
                report($e);

                throw ValidationException::withMessages([
                    'email' => __('The demo environment is not ready yet. Please try again shortly.'),
                ]);
            }
        }

        try {
            if ($sandboxes->hasReadyPool()) {
                $sandbox = $sandboxes->claim(false);

                return redirect()->away(
                    $demo->entryUrl($sandbox['school'], $demo->issueTicket($sandbox['user']))
                );
            }

            $pending = $sandboxes->prepare();
            $request->session()->put('demo_pending_session', $pending['session_id']);

            return redirect()->route('demo.preparing');
        } catch (DemoBusyException $e) {
            throw ValidationException::withMessages([
                'email' => __('The demo is busy right now. Please try again shortly.'),
            ]);
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'email' => __('The demo environment is not ready yet. Please try again shortly.'),
            ]);
        }
    }

    public function showPreparing(): View|RedirectResponse
    {
        if (! session('demo_pending_session')) {
            return redirect()->route('demo.login');
        }

        return view('marketing.demo-preparing');
    }

    public function preparingStatus(Request $request, DemoAccountService $demo, DemoSandboxService $sandboxes): JsonResponse
    {
        $sessionId = (string) $request->session()->get('demo_pending_session', '');
        $pending = $sessionId !== '' ? $sandboxes->pending($sessionId) : null;

        if (! $pending) {
            $request->session()->forget('demo_pending_session');

            return response()->json([
                'status' => 'expired',
                'url' => route('demo.login'),
            ], 410);
        }

        if ($pending['status'] === 'ready') {
            $request->session()->forget('demo_pending_session');
            $user = $pending['user'];

            return response()->json([
                'status' => 'ready',
                'url' => $demo->entryUrl($pending['school'], $demo->issueTicket($user)),
            ]);
        }

        if ($pending['status'] === 'failed') {
            $request->session()->forget('demo_pending_session');

            return response()->json([
                'status' => 'failed',
                'url' => route('demo.login'),
            ]);
        }

        return response()->json(['status' => 'preparing']);
    }

    public function consume(Request $request, DemoAccountService $demo): RedirectResponse
    {
        $user = $demo->redeemTicket((string) $request->query('ticket', ''));

        if (! $user || ! DemoPersona::isDemoUser($user)) {
            return redirect()->to(DemoSandbox::centralUrl('demo-login'))
                ->with('error', __('This demo link has expired. Please start a new demo.'));
        }

        $demo->signIn($user);

        return redirect()->intended('/workspace');
    }
}
