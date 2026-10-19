{{--
    Modern shared email shell used by every branded email.

    Props:
      school      — School model (tenant emails). Null = platform identity.
      title       — <title> text (falls back to heading).
      heading     — optional H1 rendered above the slot content.
      logoUrl     — explicit logo override (otherwise resolved from branding).
      brandName   — explicit brand-name override.
      accent      — accent colour (defaults to the KairoCORE green).
      footerNote  — one-line note above the footer details.

    Footer company details always come from email_branding($school), so a
    school's own address/phone/email win whenever set, with the platform's
    values as fallback. Logo resolution: school email logo → workspace
    School Logo → platform-uploaded school logo → KairoCORE logo.
--}}
@props([
    'school' => null,
    'title' => null,
    'heading' => null,
    'logoUrl' => null,
    'brandName' => null,
    'accent' => null,
    'footerNote' => null,
])
@php
    $school = $school instanceof \App\Models\School ? $school : null;
    $brand = email_branding($school);
    $logoUrl = $logoUrl ?: $brand['logo_url'];
    $brandName = $brandName ?: $brand['company_name'];
    $accent = $accent ?: '#059669';
    $pageTitle = $title ?: $heading ?: $brandName;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $pageTitle }}</title>
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
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" width="140" style="display:block;margin:0 auto 12px auto;max-width:180px;height:auto;">
                        @endif
                        <div style="font-size:13px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:{{ $accent }};">{{ $brandName }}</div>
                    </td>
                </tr>

                {{-- Accent bar --}}
                <tr><td style="height:4px;background-color:{{ $accent }};font-size:0;line-height:0;">&nbsp;</td></tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:36px 40px 8px 40px;">
                        @if (!empty($heading))
                            <h1 style="margin:0 0 18px 0;font-size:21px;line-height:1.35;color:#0f172a;font-weight:800;">{{ $heading }}</h1>
                        @endif

                        {{ $slot }}
                    </td>
                </tr>

                {{-- Footer: brand details --}}
                <tr>
                    <td style="padding:24px 40px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                        @if (!empty($footerNote))
                            <p style="margin:0 0 6px 0;font-size:11px;line-height:1.7;color:#94a3b8;text-align:center;">{{ $footerNote }}</p>
                        @endif
                        <p style="margin:0;font-size:11px;line-height:1.7;color:#94a3b8;text-align:center;">
                            &copy; {{ date('Y') }} {{ $brandName }}
                            @if (!empty($brand['company_address'])) &middot; {{ $brand['company_address'] }} @endif
                            @if (!empty($brand['company_phone'])) &middot; {{ __('Phone') }}: {{ $brand['company_phone'] }} @endif
                            @if (!empty($brand['company_email'])) &middot; <a href="mailto:{{ $brand['company_email'] }}" style="color:{{ $accent }};text-decoration:none;">{{ $brand['company_email'] }}</a> @endif
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
