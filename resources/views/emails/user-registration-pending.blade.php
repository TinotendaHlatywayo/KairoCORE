<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:32px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;">
        <h1 style="margin:0 0 8px;font-size:22px;">{{ __('New account registration awaiting approval') }}</h1>
        <p style="margin:0 0 24px;color:#475569;">{{ $schoolName }}</p>

        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;background:#f8fafc;margin-bottom:24px;">
            <p style="margin:0 0 4px;"><strong>{{ __('Registered:') }}</strong> {{ $pendingUser->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</p>
            <p style="margin:0;color:#64748b;">{{ __('Status:') }} <strong>{{ __('Pending approval') }}</strong> {{ __('— the account is locked out until activated.') }}</p>
        </div>

        <p><strong>{{ __('Name:') }}</strong> {{ $pendingUser->name }}</p>
        <p><strong>{{ __('Email:') }}</strong> <a href="mailto:{{ $pendingUser->email }}">{{ $pendingUser->email }}</a></p>
        @if ($pendingUser->phone)
            <p><strong>{{ __('Phone:') }}</strong> {{ $pendingUser->phone }}</p>
        @endif
        <p><strong>{{ __('Requested role:') }}</strong> {{ $pendingUser->requestedRoleLabel() ?? 'Generic' }}</p>

        <p style="margin-top:24px;font-size:13px;color:#64748b;">
            Please review and approve or reject this registration in the
            SchoolCore workspace (Accounts &amp; Users) so the member can sign in.
        </p>

        <p style="margin-top:28px;color:#0f172a;font-weight:600;">{{ __('Regards,') }} {{ $schoolName }}</p>

        <hr style="border:none;border-top:1px solid #e2e8f0;margin:16px 0;">
        <p style="margin:0;font-size:12px;color:#94a3b8;">&copy; {{ date('Y') }} SchoolCore. {{ __('All rights reserved.') }}</p>
    </div>
</body>
</html>
