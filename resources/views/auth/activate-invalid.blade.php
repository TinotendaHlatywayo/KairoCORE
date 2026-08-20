<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Invalid Activation Link - SchoolCore') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center py-5" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 text-center">
                <div class="card shadow-sm border-0 p-4">
                    @if (($reason ?? '') === 'expired')
                        <div class="mb-3 text-warning fs-1">⏰</div>
                        <h3 class="fw-bold text-dark mb-2">{{ __('Link Expired') }}</h3>
                        <p class="text-muted small mb-4">{{ __('This activation link has expired (links are valid for 48 hours). Request a new link using the registered contact email address below.') }}</p>
                    @else
                        <div class="mb-3 text-danger fs-1">⚠️</div>
                        <h3 class="fw-bold text-dark mb-2">{{ __('Invalid or Used Link') }}</h3>
                        <p class="text-muted small mb-4">{{ __('This activation link is invalid or has already been used. If you already activated your account, please sign in directly. Otherwise request a new link below.') }}</p>
                    @endif

                    <form method="POST" action="{{ route('account.activate.resend') }}" class="text-start">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">{{ __('Registered Contact Email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $email ?? '') }}" placeholder="admin@your-school.edu" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background-color: #1e3a8a; border-color: #1e3a8a;">
                            {{ __('Send New Activation Link') }}
                        </button>
                    </form>

                    <div class="mt-3 d-flex justify-content-center gap-3">
                        <a href="{{ route('marketing.home') }}" class="small">{{ __('Return to Home') }}</a>
                        @if (isset($email) && $email)
                            <span class="text-muted">&middot;</span>
                            <a href="{{ route('filament.app.auth.login') }}" class="small">{{ __('Sign In') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
