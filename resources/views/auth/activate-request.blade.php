<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Request New Activation Link - Kairo CORE') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center py-5" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0 p-4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-dark">{{ __('Request a New Activation Link') }}</h3>
                        <p class="text-muted small">{{ __('Enter the email address you used when registering your school. If your registration was approved, a fresh activation link will be emailed to you.') }}</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success small">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.activate.resend') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">{{ __('Registered Contact Email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $email ?? '') }}" placeholder="admin@your-school.edu" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background-color: #1e3a8a; border-color: #1e3a8a;">
                            {{ __('Send New Activation Link') }}
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="{{ route('marketing.home') }}" class="small">{{ __('Return to Home') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
