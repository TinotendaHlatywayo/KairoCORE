{{-- resources/views/emails/brand.blade.php --}}
{{-- Shared branded email shell. Data keys (see brand_email_view_data()):
     logoUrl, companyName, companyAddress, companyPhone, companyEmail,
     heading, greeting, introLines[], actionUrl, actionText, outroLines[],
     footerNote, signature, accentColor --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,.08);">

                {{-- Header: logo + brand name --}}
                <tr>
                    <td align="center" style="padding:32px 40px 20px 40px;background-color:#ffffff;">
                        @if (!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ $companyName }} logo" width="140" style="display:block;margin:0 auto 12px auto;max-width:180px;height:auto;">
                        @endif
                        <div style="font-size:13px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#059669;">{{ $companyName }}</div>
                    </td>
                </tr>

                {{-- Accent bar --}}
                <tr><td style="height:4px;background-color:{{ $accentColor ?? '#10b981' }};font-size:0;line-height:0;">&nbsp;</td></tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:36px 40px 8px 40px;">
                        <h1 style="margin:0 0 18px 0;font-size:21px;line-height:1.35;color:#0f172a;font-weight:800;">{{ $heading }}</h1>

                        @if (!empty($greeting))
                            <p style="margin:0 0 14px 0;font-size:15px;color:#334155;font-weight:600;">{{ $greeting }}</p>
                        @endif

                        @foreach ((array) $introLines as $line)
                            <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{!! $line !!}</p>
                        @endforeach
                    </td>
                </tr>

                {{-- CTA button --}}
                @if (!empty($actionUrl) && !empty($actionText))
                    <tr>
                        <td align="center" style="padding:14px 40px 22px 40px;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;background-color:#059669;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 34px;border-radius:10px;">{{ $actionText }}</a>
                            <p style="margin:16px 0 0 0;font-size:12px;line-height:1.6;color:#94a3b8;">{{ __('If the button does not work, copy and paste this link into your browser:') }}<br><span style="color:#059669;word-break:break-all;">{{ $actionUrl }}</span></p>
                        </td>
                    </tr>
                @endif

                {{-- Outro lines + signature --}}
                <tr>
                    <td style="padding:6px 40px 30px 40px;">
                        @foreach ((array) $outroLines as $line)
                            <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{!! $line !!}</p>
                        @endforeach

                        @if (!empty($signature))
                            <p style="margin:22px 0 0 0;font-size:14px;line-height:1.6;color:#0f172a;font-weight:700;">{{ $signature }}</p>
                        @endif
                    </td>
                </tr>

                {{-- Footer: company details --}}
                <tr>
                    <td style="padding:24px 40px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:11px;line-height:1.7;color:#94a3b8;text-align:center;">
                            &copy; {{ date('Y') }} {{ $companyName }}
                            @if (!empty($companyAddress)) &middot; {{ $companyAddress }} @endif
                            @if (!empty($companyPhone)) &middot; {{ __('Phone') }}: {{ $companyPhone }} @endif
                            @if (!empty($companyEmail)) &middot; <a href="mailto:{{ $companyEmail }}" style="color:#059669;text-decoration:none;">{{ $companyEmail }}</a> @endif
                            <br>{{ $footerNote ?? __('This is an automated message from').' '.$companyName.'.' }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
