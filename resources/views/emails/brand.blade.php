{{-- resources/views/emails/brand.blade.php --}}
{{-- Shared branded email shell. Data keys (see brand_email_view_data()):
     school, logoUrl, companyName, companyAddress, companyPhone, companyEmail,
     heading, greeting, introLines[], actionUrl, actionText, outroLines[],
     footerNote, signature, accentColor --}}
<x-email-layout
    :school="$school ?? null"
    :title="$heading"
    :heading="$heading"
    :logo-url="$logoUrl"
    :brand-name="$companyName"
    :accent="$accentColor ?? null"
    :footer-note="$footerNote ?? null"
>
    @if (!empty($greeting))
        <p style="margin:0 0 14px 0;font-size:15px;color:#334155;font-weight:600;">{{ $greeting }}</p>
    @endif

    @foreach ((array) $introLines as $line)
        <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{!! $line !!}</p>
    @endforeach

    {{-- CTA button --}}
    @if (!empty($actionUrl) && !empty($actionText))
        <div style="text-align:center;padding:14px 0 22px 0;">
            <a href="{{ $actionUrl }}" style="display:inline-block;background-color:{{ $accentColor ?? '#059669' }};color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 34px;border-radius:10px;">{{ $actionText }}</a>
            <p style="margin:16px 0 0 0;font-size:12px;line-height:1.6;color:#94a3b8;">{{ __('If the button does not work, copy and paste this link into your browser:') }}<br><span style="color:#059669;word-break:break-all;">{{ $actionUrl }}</span></p>
        </div>
    @endif

    {{-- Outro lines + signature --}}
    @foreach ((array) $outroLines as $line)
        <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{!! $line !!}</p>
    @endforeach

    @if (!empty($signature))
        <p style="margin:22px 0 0 0;font-size:14px;line-height:1.6;color:#0f172a;font-weight:700;">{{ $signature }}</p>
    @endif
</x-email-layout>
