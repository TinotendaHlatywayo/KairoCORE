<x-email-layout
    :title="$schoolName ?: __('Billing Notification')"
    :heading="$schoolName ?: __('Billing Notification')"
>
    <div style="white-space:pre-line;font-size:15px;line-height:1.65;color:#475569;">{{ $body }}</div>

    <p style="margin:24px 0 0 0;font-size:14px;color:#0f172a;font-weight:700;">
        {{ __('Regards,') }}<br>
        {{ platform_name() }} {{ __('Billing') }}
    </p>
</x-email-layout>
