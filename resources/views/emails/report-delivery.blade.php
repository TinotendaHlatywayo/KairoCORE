<x-email-layout :title="__('Scheduled Report Delivery')" :heading="__('Your scheduled report is ready')">
    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">
        {{ __('Greetings,') }}
    </p>

    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">
        {{ __('Please find attached the scheduled enterprise report ":name" generated on :date.', [
            'name' => $reportName,
            'date' => $generatedAt,
        ]) }}
    </p>

    <p style="margin:0;font-size:14px;color:#0f172a;font-weight:700;">
        {{ __('Best Regards,') }}<br>
        {{ platform_name() }} {{ __('Automated Distribution Service') }}
    </p>
</x-email-layout>
