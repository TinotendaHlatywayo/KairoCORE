<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Account Suspended') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card text-center p-5 shadow-sm" style="max-width: 500px;">
        <h1 class="text-danger mb-3">{{ __('Access Suspended') }}</h1>
        <p class="text-muted">{{ __('The system access for') }} <strong>{{ $school->name }}</strong> has been temporarily suspended. Please contact your institution's administrator or support billing services.</p>
    </div>
</body>
</html>