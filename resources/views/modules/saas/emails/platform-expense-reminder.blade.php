<x-email-layout :title="$expense->title" :heading="__('Expense Reminder')">
    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.65;color:#475569;">
        {{ __('The recurring platform expense ":title" :timing.', ['title' => $expense->title, 'timing' => $timing]) }}
    </p>

    <table style="width:100%;border-collapse:collapse;font-size:14px;margin:0 0 24px 0;" role="presentation">
        <tbody>
            <tr>
                <td style="padding:8px 0;color:#64748b;width:40%;">{{ __('Description') }}</td>
                <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $expense->title }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">{{ __('Amount') }}</td>
                <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">
                    {{ $expense->currency }} {{ number_format((float) $expense->amount, 2) }}
                </td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#64748b;">{{ __('Due date') }}</td>
                <td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a;">{{ $dueDate->format('d M Y') }}</td>
            </tr>
            @if ($expense->category)
                <tr>
                    <td style="padding:8px 0;color:#64748b;">{{ __('Category') }}</td>
                    <td style="padding:8px 0;text-align:right;color:#475569;">{{ $expense->category }}</td>
                </tr>
            @endif
            @if ($expense->vendor)
                <tr>
                    <td style="padding:8px 0;color:#64748b;">{{ __('Vendor') }}</td>
                    <td style="padding:8px 0;text-align:right;color:#475569;">{{ $expense->vendor }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding:8px 0;color:#64748b;">{{ __('Recurrence') }}</td>
                <td style="padding:8px 0;text-align:right;color:#475569;">{{ $expense->recurrenceLabel() }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
        {{ __('This is an automated reminder from :name finance.', ['name' => platform_name()]) }}
    </p>
</x-email-layout>
