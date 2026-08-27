<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ $school->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: 700; color: #15803d; }
        .hero-banner { background: #f0fdf4; border-bottom: 1px solid #dcfce7; }
    </style>
</head>
<body class="bg-light d-flex flex-column vh-100">

    <!-- Tenant Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a class="navbar-brand text-success" href="{{ route('tenant.home') }}">{{ $school->name }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#tenantNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="tenantNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-4">
                    <li class="nav-item"><a class="nav-link text-secondary fw-semibold" href="{{ route('tenant.home') }}">{{ __('Home') }}</a></li>
                    <li class="nav-item"><a class="nav-link text-secondary fw-semibold" href="{{ route('tenant.about') }}">{{ __('About') }}</a></li>
                    <li class="nav-item"><a class="nav-link text-secondary fw-semibold" href="{{ route('tenant.contact') }}">{{ __('Contact') }}</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="{{ route('cms-render', ['slug' => 'apply-online']) }}" class="btn btn-outline-success fw-semibold">{{ __('Apply Online') }}</a>
                    <a href="{{ route('login') }}" class="btn btn-success fw-semibold px-4" target="_blank" rel="noopener noreferrer">{{ __('Portal Login') }}</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-4 mt-auto">
        <div class="container text-center">
            <p class="text-muted small mb-0">&copy; {{ date('Y') }} {{ $school->name }}. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>