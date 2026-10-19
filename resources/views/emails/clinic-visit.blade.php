<x-email-layout
    :school="$school ?? null"
    :heading="__('Student Health & Wellness Update')"
>
    @foreach ($lines as $line)
        <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{{ $line }}</p>
    @endforeach

    @if (!empty($message->salutation))
        <p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;font-weight:700;">{{ $message->salutation }}</p>
    @endif
</x-email-layout>
