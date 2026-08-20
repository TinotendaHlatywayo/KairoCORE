<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:32px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;">
        <h1 style="margin:0 0 8px;font-size:22px;">{{ __('New website enquiry') }}</h1>
        <p style="margin:0 0 24px;color:#475569;">{{ $schoolName }}</p>
        <p><strong>{{ __('Name:') }}</strong> {{ $contact['first_name'] }} {{ $contact['last_name'] }}</p>
        <p><strong>{{ __('Email:') }}</strong> <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p>
        <p><strong>{{ __('Phone:') }}</strong> {{ $contact['phone'] }}</p>
        <p><strong>{{ __('Message:') }}</strong></p>
        <div style="white-space:pre-wrap;border-left:3px solid #4f46e5;padding:12px 16px;background:#f8fafc;">{{ $contact['message'] }}</div>
    </div>
</body>
</html>
