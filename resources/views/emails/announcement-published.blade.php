<x-mail.brand>
    <div style="font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
        <h2 style="color: #1f2e43; margin: 0 0 12px;">{{ $title }}</h2>

        <div style="color: #374151; line-height: 1.7;">{!! $content !!}</div>

        <p style="margin-top: 24px; color: #6b7280; font-size: 13px;">
            Sent by {{ $schoolName }}
        </p>
    </div>
</x-mail.brand>