<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:32px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;">
        <div style="font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#059669;margin-bottom:8px;">{{ $schoolName }}</div>
        <h1 style="margin:0 0 12px;font-size:22px;">{{ $title }}</h1>
        <div style="color:#475569;line-height:1.7;">{!! $content !!}</div>
        <p style="margin-top:24px;color:#94a3b8;font-size:12px;">{{ __('This is an automated message from').' '.$schoolName.'.' }}</p>
    </div>
</body>
</html>