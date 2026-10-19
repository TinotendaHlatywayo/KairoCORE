<x-email-layout :title="__('New contact message')" :heading="__('Kairo CORE Contact: :name', ['name' => $contactName])">
    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">
        <strong>{{ __('Name:') }}</strong> {{ $contactName }}<br>
        <strong>{{ __('Email:') }}</strong> <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
    </p>

    <p style="margin:0 0 8px 0;font-size:15px;color:#475569;"><strong>{{ __('Message:') }}</strong></p>
    <div style="white-space:pre-wrap;border-left:3px solid #10b981;padding:12px 16px;background:#f8fafc;border-radius:0 8px 8px 0;font-size:15px;line-height:1.65;color:#475569;">{{ $messageText }}</div>
</x-email-layout>
