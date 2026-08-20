<!DOCTYPE html>
<html>
<head>
    <title>{{ __('Payment Receipt Confirmed') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; padding: 20px;">
    <h2>{{ __('Thank You For Your Payment') }}</h2>
    <p>{{ __('Dear Administrator,') }}</p>
    <p>{{ __('We are pleased to inform you that your platform subscription payment has been successfully processed.') }}</p>
    <p><strong>{{ __('Receipt Reference Code:') }}</strong> {{ $receipt->receipt_number }}<br>
       <strong>{{ __('Amount Paid:') }}</strong> ${{ number_format($receipt->amount_paid, 2) }} {{ $receipt->currency }}<br>
       <strong>{{ __('Settled On:') }}</strong> {{ $receipt->issued_at->format('M d, Y H:i') }}</p>
    <p>{{ __('We have compiled and attached your official PDF receipt to this email for your accounting records.') }}</p>
    <p>If you have any billing inquiries, please contact our support team at billing@schoolcore.test.</p>
    <p>{{ __('Best regards,') }}<br>{{ __('SchoolCore ERP SaaS Team') }}</p>
</body>
</html>