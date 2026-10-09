<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $expense->title }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.65; padding: 24px; background: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px;">
        <p style="font-size: 13px; letter-spacing: .08em; text-transform: uppercase; color: #5b4fe9; margin: 0 0 20px;">
            {{ platform_name() }} {{ __('Expense Reminder') }}
        </p>

        <p style="font-size: 15px; margin: 0 0 16px;">
            {{ __('The recurring platform expense ":title" :timing.', ['title' => $expense->title, 'timing' => $timing]) }}
        </p>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin: 0 0 24px;">
            <tbody>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 40%;">{{ __('Description') }}</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ $expense->title }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">{{ __('Amount') }}</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold;">
                        {{ $expense->currency }} {{ number_format((float) $expense->amount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">{{ __('Due date') }}</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ $dueDate->format('d M Y') }}</td>
                </tr>
                @if ($expense->category)
                    <tr>
                        <td style="padding: 8px 0; color: #64748b;">{{ __('Category') }}</td>
                        <td style="padding: 8px 0; text-align: right;">{{ $expense->category }}</td>
                    </tr>
                @endif
                @if ($expense->vendor)
                    <tr>
                        <td style="padding: 8px 0; color: #64748b;">{{ __('Vendor') }}</td>
                        <td style="padding: 8px 0; text-align: right;">{{ $expense->vendor }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">{{ __('Recurrence') }}</td>
                    <td style="padding: 8px 0; text-align: right;">{{ $expense->recurrenceLabel() }}</td>
                </tr>
            </tbody>
        </table>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 28px 0 16px;">
        <p style="font-size: 12px; color: #64748b; margin: 0;">
            {{ __('This is an automated reminder from :name finance.', ['name' => platform_name()]) }}<br>
            &copy; {{ date('Y') }} {{ platform_name() }}. {{ __('All rights reserved.') }}
        </p>
    </div>
</body>
</html>
