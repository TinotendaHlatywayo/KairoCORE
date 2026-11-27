<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Security\DemoPersona;
use App\Services\DemoSandboxService;
use App\Support\DemoSandbox;
use App\Support\DemoSessionGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class EnsureDemoSandboxAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->routeIs('demo.consume')) {
            return $next($request);
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (! $tenant instanceof School || ! $tenant->is_demo_sandbox) {
            return $next($request);
        }

        $user = auth()->user();
        $sessionId = (int) $request->session()->get('demo_sandbox_id', 0);
        $authorized = filter_var($request->session()->get('is_demo_session'), FILTER_VALIDATE_BOOLEAN)
            && $sessionId === (int) $tenant->id
            && $user !== null
            && DemoSandbox::isSandboxEmail($user->email);

        $expired = ($tenant->hard_expires_at?->isPast() ?? false)
            || ($tenant->expires_at?->isPast() ?? false);

        if (! $authorized || $expired) {
            Auth::guard('web')->logout();
            DemoPersona::clear();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(DemoSandbox::centralUrl('demo/ended'));
        }

        DemoSessionGuard::begin((int) $tenant->id);

        app(DemoSandboxService::class)->touch($tenant);
        $this->assertUploadLimits($request);
        $this->scopeUploads($tenant);

        return $next($request);
    }

    public function terminate(Request $request, mixed $response): void
    {
        DemoSessionGuard::end();
    }

    private function assertUploadLimits(Request $request): void
    {
        $files = $this->flattenFiles($request->allFiles());

        if (count($files) > (int) config('demo.sandbox.upload_max_files', 20)) {
            abort(413, __('Too many files uploaded in this demo session.'));
        }

        $limit = (int) config('demo.sandbox.upload_cap_mb', 10) * 1024 * 1024;

        foreach ($files as $file) {
            if ($file->getSize() > $limit) {
                abort(413, __('Uploaded files must be smaller than :size MB in this demo session.', [
                    'size' => (int) config('demo.sandbox.upload_cap_mb', 10),
                ]));
            }
        }
    }

    /** @return array<int, UploadedFile> */
    private function flattenFiles(array $files): array
    {
        $flattened = [];

        foreach ($files as $file) {
            if (is_array($file)) {
                $flattened = array_merge($flattened, $this->flattenFiles($file));

                continue;
            }

            if ($file instanceof UploadedFile && $file->isValid()) {
                $flattened[] = $file;
            }
        }

        return $flattened;
    }

    private function scopeUploads(School $tenant): void
    {
        $relative = 'demo-sandboxes/'.$tenant->id;
        $root = storage_path('app/public/'.$relative);

        if (! is_dir($root)) {
            mkdir($root, 0755, true);
        }

        config([
            'filesystems.disks.public.root' => $root,
            'filesystems.disks.public.url' => '/storage/'.$relative,
        ]);

        app('filesystem')->forgetDisk('public');
    }
}
