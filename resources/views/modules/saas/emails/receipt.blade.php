<x-email-layout :title="__('Payment Receipt Confirmed')" :heading="__('Thank You For Your Payment')">
    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">{{ __('Dear Administrator,') }}</p>

    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.65;color:#475569;">
        {{ __('We are pleased to inform you that your platform subscription payment has been successfully processed.') }}
    </p>

    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin:0 0 16px 0;font-size:15px;line-height:1.8;color:#475569;">
        <strong>{{ __('Receipt Reference Code:') }}</strong> {{ $receipt->receipt_number }}<br>
        <strong>{{ __('Amount Paid:') }}</strong> ${{ number_format($receipt->amount_paid, 2) }} {{ $receipt->currency }}<br>
        <strong>{{ __('Settled On:') }}</strong> {{ $receipt->issued_at->format('M d, Y H:i') }}
    </div>

    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">
        {{ __('We have compiled and attached your official PDF receipt to this email for your accounting records.') }}
    </p>

    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.65;color:#475569;">
        {{ __('If you have any billing inquiries, please contact our support team at :email.', ['email' => config('mail.platform.support')]) }}
    </p>

    <p style="margin:0;font-size:14px;color:#0f172a;font-weight:700;">
        {{ __('Best regards,') }}<br>
        {{ __('The :name Team', ['name' => platform_name()]) }}
    </p>
</x-email-layout>
