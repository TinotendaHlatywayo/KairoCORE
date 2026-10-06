<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.65; padding: 24px; background: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px;">
        <p style="font-size: 13px; letter-spacing: .08em; text-transform: uppercase; color: #5b4fe9; margin: 0 0 20px;">
            {{ platform_name() }}
        </p>

        <h1 style="font-size: 18px; margin: 0 0 16px;">{{ $subjectLine }}</h1>

        <div style="white-space: pre-line; font-size: 15px;">{{ $messageBody }}</div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 28px 0 16px;">
        <p style="font-size: 12px; color: #64748b; margin: 0;">
            {{ __('This message was sent to :school by the :platform team.', ['school' => $schoolName ?: __('your school'), 'platform' => platform_name()]) }}<br>
            &copy; {{ date('Y') }} {{ platform_name() }}. {{ __('All rights reserved.') }}
        </p>
    </div>
</body>
</html>