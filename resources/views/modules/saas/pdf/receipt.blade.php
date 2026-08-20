<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Subscription Payment Receipt: {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 20px; line-height: 1.5; font-size: 13px; }
        .receipt-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 40px; background-color: #ffffff; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .logo-text { font-size: 24px; font-weight: bold; color: #10b981; margin: 0; }
        .receipt-title { font-size: 20px; font-weight: bold; text-align: right; color: #0f172a; margin: 0; text-transform: uppercase; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .meta-table td { vertical-align: top; width: 50%; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
        .details { font-size: 13px; line-height: 1.6; color: #334155; }
        .success-banner { background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; border-radius: 4px; margin-bottom: 30px; }
        .success-text { font-weight: 600; color: #065f46; margin: 0; font-size: 14px; }
        .totals-box { border-top: 2px dashed #e2e8f0; border-bottom: 2px dashed #e2e8f0; padding: 20px 0; margin-bottom: 35px; }
        .total-amount { font-size: 28px; font-weight: bold; color: #0f172a; text-align: center; margin: 0; }
        .total-label { text-align: center; color: #64748b; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
        .footer { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 40px; }
    </style>
</head>
<body>

    <div class="receipt-card">
        <table class="header-table">
            <tr>
                <td>
                    <h1 class="logo-text">{{ __('SchoolCore ERP') }}</h1>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">{{ __('SaaS Platform Subscription Receipt') }}</div>
                </td>
                <td style="text-align: right;">
                    <h2 class="receipt-title">{{ __('Payment Receipt') }}</h2>
                    <div style="margin-top: 5px; font-size: 14px; font-weight: 600; color: #475569;"># {{ $receipt->receipt_number }}</div>
                </td>
            </tr>
        </table>

        <div class="success-banner">
            <h3 class="success-text">{{ __('Payment Confirmed & Settled') }}</h3>
            <p style="margin: 5px 0 0 0; color: #047857; font-size: 12px;">{{ __('Thank you for your payment. Your subscription status has been updated and remains active.') }}</p>
        </div>

        <div class="totals-box">
            <div class="total-label">{{ __('Amount Paid') }}</div>
            <div class="total-amount">${{ number_format($receipt->amount_paid, 2) }} {{ $receipt->currency }}</div>
        </div>

        <table class="meta-table">
            <tr>
                <td>
                    <div class="section-title">{{ __('Payer Details') }}</div>
                    <div class="details">
                        <strong>{{ $receipt->school->name }}</strong><br>
                        Tenant Domain Reference: {{ $receipt->school->subdomain }}.schoolcore.test<br>
                        School ID: #SCH-{{ str_pad($receipt->school_id, 5, '0', STR_PAD_LEFT) }}
                    </div>
                </td>
                <td style="text-align: right;">
                    <div class="section-title">{{ __('Transaction Details') }}</div>
                    <div class="details">
                        <strong>{{ __('Receipt Date:') }}</strong> {{ $receipt->issued_at->format('M d, Y H:i') }}<br>
                        <strong>{{ __('Ref / Reference ID:') }}</strong> {{ $receipt->transaction->transaction_reference }}<br>
                        <strong>{{ __('Invoice Cleared:') }}</strong> {{ $receipt->invoice->invoice_number }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            <p>{{ __('Verification Checksum:') }} <strong>{{ $receipt->verification_token }}</strong></p>
            <p>{{ __('&copy; 2026 SchoolCore ERP Software Inc. All rights reserved.') }}</p>
        </div>
    </div>

</body>
</html>