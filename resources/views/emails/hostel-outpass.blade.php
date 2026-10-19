<x-email-layout
    :school="$school ?? null"
    :heading="__('Boarding Exit OTP Authorization Request')"
>
    @foreach ($lines as $line)
        <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{{ $line }}</p>
    @endforeach

    @if (!empty($code))
        <div style="text-align:center;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px;margin:6px 0 16px 0;">
            <div style="font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#64748b;margin-bottom:8px;">{{ __('Verification Code') }}</div>
            <div style="font-size:30px;font-weight:800;letter-spacing:.32em;color:#059669;font-family:'Courier New',monospace;">{{ $code }}</div>
        </div>
    @endif

    @if (!empty($closingLine))
        <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{{ $closingLine }}</p>
    @endif

    @if (!empty($message->salutation))
        <p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;font-weight:700;">{{ $message->salutation }}</p>
    @endif
</x-email-layout>
