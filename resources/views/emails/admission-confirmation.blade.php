<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .email-wrapper { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .email-card { background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .email-header { background-color: #1e3a8a; color: #ffffff; padding: 28px 32px; }
        .email-header h1 { margin: 0; font-size: 22px; }
        .email-header p { margin: 6px 0 0; font-size: 14px; opacity: 0.9; }
        .email-body { padding: 32px; color: #374151; font-size: 15px; line-height: 1.7; }
        .email-body pre { white-space: pre-wrap; font-family: inherit; margin: 0; }
        .identity-box { background-color: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .identity-box table { width: 100%; border-collapse: collapse; }
        .identity-box td { padding: 8px 0; font-size: 14px; }
        .identity-box .label { color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; width: 45%; }
        .identity-box .value { color: #1e3a8a; font-weight: 700; font-family: "Courier New", monospace; font-size: 16px; }
        .email-footer { padding: 20px 32px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-card">
            <div class="email-header">
                <h1>{{ $schoolName }}</h1>
                <p>{{ __('Official Admission Confirmation') }}</p>
            </div>
            <div class="email-body">
                <pre>{!! nl2br(e($emailBody)) !!}</pre>

                <div class="identity-box">
                    <table>
                        <tr>
                            <td class="label">{{ __('Student ID Number') }}</td>
                            <td class="value">{{ $student->student_id_number }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ __('Admission Number') }}</td>
                            <td class="value">{{ $student->admission_number }}</td>
                        </tr>
                    </table>
                </div>

                <p style="margin-top: 16px; color: #6b7280; font-size: 13px;">
                    {{ __('Please keep this information for your records. For any queries regarding this admission,
                    contact the admissions office.') }}
                </p>

                @if(!empty($activationUrl))
                    <div style="text-align: center; margin: 28px 0 8px;">
                        <a href="{{ $activationUrl }}"
                           style="display: inline-block; background-color: #1e3a8a; color: #ffffff; text-decoration: none;
                                  padding: 14px 36px; border-radius: 8px; font-size: 16px; font-weight: 600;">
                            {{ __('Activate My Account') }}
                        </a>
                        <p style="margin-top: 12px; color: #6b7280; font-size: 13px;">
                            {{ __('Use the button above to set your password and activate your student portal account. The link is valid for :hours hours.', ['hours' => config('auth.activation_token_ttl_hours', 48)]) }}
                        </p>
                    </div>
                @endif
            </div>
            <div class="email-footer">
                &copy; {{ date('Y') }} {{ $schoolName }} &middot; Powered by Kairo CORE
            </div>
        </div>
    </div>
</body>
</html>
