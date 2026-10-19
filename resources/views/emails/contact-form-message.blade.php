<x-email-layout
    :school="$school ?? null"
    :heading="__('New website enquiry')"
    :brand-name="$schoolName"
>
    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">
        <strong>{{ __('Name:') }}</strong> {{ $contact['first_name'] }} {{ $contact['last_name'] }}<br>
        <strong>{{ __('Email:') }}</strong> <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a><br>
        <strong>{{ __('Phone:') }}</strong> {{ $contact['phone'] }}
    </p>

    <p style="margin:0 0 8px 0;font-size:15px;color:#475569;"><strong>{{ __('Message:') }}</strong></p>
    <div style="white-space:pre-wrap;border-left:3px solid #10b981;padding:12px 16px;background:#f8fafc;border-radius:0 8px 8px 0;font-size:15px;line-height:1.65;color:#475569;">{{ $contact['message'] }}</div>
</x-email-layout>
