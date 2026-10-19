<x-email-layout :title="$subjectLine" :heading="$subjectLine">
    <div style="white-space:pre-line;font-size:15px;line-height:1.65;color:#475569;">{{ $messageBody }}</div>

    <p style="margin:24px 0 0 0;font-size:13px;line-height:1.6;color:#64748b;">
        {{ __('This message was sent to :school by the :platform team.', ['school' => $schoolName ?: __('your school'), 'platform' => platform_name()]) }}
    </p>
</x-email-layout>
