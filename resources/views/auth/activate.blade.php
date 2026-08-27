<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Activate Your Account - Kairo CORE') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #password-feedback .check-item {
            font-size: 0.8rem;
            margin-bottom: 0.15rem;
        }
        #password-feedback .check-item span { font-weight: bold; }
        #password-feedback .check-item.text-success { color: #198754 !important; }
        #password-feedback .check-item.text-danger { color: #dc3545 !important; }
        input.is-invalid { border-color: #dc3545 !important; }
    </style>
</head>
<body class="bg-light d-flex align-items-center py-5" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0 p-4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-primary">{{ __('Activate Account') }}</h3>
                        <p class="text-muted small">{{ __('Welcome, ' . $user->name . '. Set your username and secure password to complete activation.') }}</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.activate.submit') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">{{ __('Email Address (Fixed)') }}</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled readonly>
                            <div class="form-text small">{{ __('Your email address is permanent and cannot be changed.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">{{ __('Username') }}</label>
                            <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', explode('@', $user->email)[0]) }}" maxlength="50" autocomplete="username" required>
                            <div class="form-text small">{{ __('Letters, numbers, dashes and underscores only (at least 3 characters).') }}</div>
                            <div id="username-feedback" class="small mt-1 d-none text-danger"></div>
                            @error('username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">{{ __('New Password') }}</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Min. 8 chars, 1 capital, 1 number, 1 special" autocomplete="new-password" required>
                            <div id="password-feedback" class="mt-2">
                                <div id="pw-char" class="check-item text-danger"><span id="pw-char-icon">✕</span> {{ __('8 Characters') }}</div>
                                <div id="pw-special" class="check-item text-danger"><span id="pw-special-icon">✕</span> {{ __('At least 1 special character') }}</div>
                                <div id="pw-upper" class="check-item text-danger"><span id="pw-upper-icon">✕</span> {{ __('At least 1 upper case') }}</div>
                                <div id="pw-number" class="check-item text-danger"><span id="pw-number-icon">✕</span> {{ __('At least 1 number') }}</div>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">{{ __('Confirm Password') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control @error('password') is-invalid @enderror" placeholder="Re-enter password" autocomplete="new-password" required>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background-color: #1e3a8a; border-color: #1e3a8a;">
                            {{ __('Activate Account & Proceed') }}
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="{{ route('account.activate.request', ['email' => $user->email]) }}" class="small">{{ __('Link expired or not working? Request a new one.') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var pw = document.getElementById('password');
            var cf = document.getElementById('password_confirmation');
            if (!pw) return;

            var criteria = [
                { id: 'pw-char', icon: 'pw-char-icon', ok: function (v) { return v.length >= 8; } },
                { id: 'pw-special', icon: 'pw-special-icon', ok: function (v) { return /[\W_]/.test(v); } },
                { id: 'pw-upper', icon: 'pw-upper-icon', ok: function (v) { return /[A-Z]/.test(v); } },
                { id: 'pw-number', icon: 'pw-number-icon', ok: function (v) { return /\d/.test(v); } }
            ];

            function checkPassword() {
                var value = pw.value;
                var allPass = value.length > 0;
                criteria.forEach(function (c) {
                    var pass = c.ok(value);
                    var row = document.getElementById(c.id);
                    var icon = document.getElementById(c.icon);
                    if (!pass) allPass = false;
                    if (row) {
                        row.classList.toggle('text-success', pass);
                        row.classList.toggle('text-danger', !pass);
                    }
                    if (icon) icon.textContent = pass ? '✓' : '✕';
                });

                pw.classList.toggle('is-invalid', !allPass);
                pw.classList.toggle('is-valid', allPass);
                if (cf) checkConfirm();
            }

            function checkConfirm() {
                var matches = cf.value.length > 0 && cf.value === pw.value;
                cf.classList.toggle('is-invalid', !matches);
                cf.classList.toggle('is-valid', matches);
            }

            pw.addEventListener('input', checkPassword);
            if (cf) cf.addEventListener('input', checkConfirm);

            var un = document.getElementById('username');
            var unFb = document.getElementById('username-feedback');
            if (un && unFb) {
                var usernameAllowed = /^[a-zA-Z0-9_-]+$/;
                function checkUsername() {
                    var value = un.value;
                    var bad = [];
                    var seen = {};
                    for (var i = 0; i < value.length; i++) {
                        var ch = value.charAt(i);
                        if (!usernameAllowed.test(ch) && !seen[ch]) { seen[ch] = true; bad.push(ch); }
                    }
                    if (value === '') {
                        unFb.textContent = 'Username is required.';
                        unFb.classList.remove('d-none');
                        un.classList.add('is-invalid');
                        un.classList.remove('is-valid');
                    } else if (value.length < 3) {
                        unFb.textContent = 'Username must be at least 3 characters.';
                        unFb.classList.remove('d-none');
                        un.classList.add('is-invalid');
                        un.classList.remove('is-valid');
                    } else if (bad.length > 0) {
                        unFb.textContent = 'Invalid character' + (bad.length > 1 ? 's' : '') + ': ' + bad.join(' ') + '. Only letters, numbers, dashes and underscores are allowed.';
                        unFb.classList.remove('d-none');
                        un.classList.add('is-invalid');
                        un.classList.remove('is-valid');
                    } else {
                        unFb.classList.add('d-none');
                        un.classList.remove('is-invalid');
                        un.classList.add('is-valid');
                    }
                }
                un.addEventListener('blur', checkUsername);
                un.addEventListener('input', function () { if (unFb.textContent) checkUsername(); });
            }
        })();
    </script>
</body>
</html>
