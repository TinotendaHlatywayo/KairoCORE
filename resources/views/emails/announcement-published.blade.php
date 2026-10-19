<x-email-layout
    :school="$school ?? null"
    :heading="$title"
    :brand-name="$schoolName"
>
    <div style="font-size:15px;line-height:1.7;color:#475569;">{!! $content !!}</div>

    <p style="margin-top:24px;font-size:12px;color:#94a3b8;">{{ __('This is an automated message from').' '.$schoolName.'.' }}</p>
</x-email-layout>
