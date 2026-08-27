<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Registration Pending Approval - Kairo CORE') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card text-center p-5 shadow-sm" style="max-width: 550px; border: none; border-radius: 8px;">
        <div class="mb-4">
            <!-- Warning/Clock icon -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-warning" style="width: 64px; height: 64px; margin: 0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h2 class="text-dark fw-bold mb-3">{{ __('Application Under Review') }}</h2>
        <p class="text-muted fs-5">{{ __('Thank you for registering') }} <strong>{{ $school->name }}</strong>{{ __('.') }}</p>
        <p class="text-muted mb-4">{{ __('Your institutional portal is currently awaiting verification and approval from our administration team. You will be notified via email once your subdomain goes active.') }}</p>
        <div class="border-top pt-3">
            <p class="small text-secondary mb-0">{{ __('Need assistance? Please contact our system support desk.') }}</p>
        </div>
    </div>
</body>
</html>