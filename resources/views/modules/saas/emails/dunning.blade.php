<!DOCTYPE html>
<html>
<head>
    <title>{{ __('Subscription Notification') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; padding: 20px;">
    <h2>{{ __('Subscription Update Notification') }}</h2>
    <p>Dear Administrator of {{ $schoolName }},</p>

    @if($type === 'upcoming_5_days')
        <p>{{ __('This is an automated reminder that your Kairo CORE subscription renewal is due in') }} <strong>{{ __('5 days') }}</strong> (on {{ $nextDate }}).</p>
        <p>{{ __('The billing amount of') }} <strong>${{ $amountDue }}</strong> {{ __('will be processed using your active payment method, or you can complete a manual bank transfer.') }}</p>
    @elseif($type === 'upcoming_2_days')
        <p>{{ __('This is an urgent reminder that your subscription renewal payment of') }} <strong>${{ $amountDue }}</strong> {{ __('is due in') }} <strong>{{ __('2 days') }}</strong>{{ __('.') }}</p>
    @elseif($type === 'due_today')
        <p>{{ __('Your subscription renewal payment of') }} <strong>${{ $amountDue }}</strong> {{ __('is due today. Please ensure your payment details are up-to-date to avoid any disruption to your services.') }}</p>
    @else
        <p><strong>{{ __('URGENT:') }}</strong> {{ __('Your subscription payment is overdue. Your grace period is currently active, but your account access will be suspended within the next 48 hours unless payment is verified.') }}</p>
    @endif

    <p>Please log in to your tenant dashboard workspace under "Overview & Billing" to review invoices or upload a proof of payment.</p>
    <p>{{ __('Best regards,') }}<br>{{ __('Kairo CORE SaaS Platform Services') }}</p>
</body>
</html>