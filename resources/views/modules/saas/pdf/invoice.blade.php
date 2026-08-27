<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Subscription Invoice: {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 20px; line-height: 1.5; font-size: 13px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .logo-text { font-size: 26px; font-weight: bold; color: #4f46e5; margin: 0; letter-spacing: -0.025em; }
        .invoice-title { font-size: 20px; font-weight: bold; text-align: right; color: #0f172a; margin: 0; text-transform: uppercase; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .meta-table td { vertical-align: top; width: 50%; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; tracking: 0.05em; color: #64748b; margin-bottom: 8px; }
        .bill-details { font-size: 13px; line-height: 1.6; color: #334155; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 30px; }
        .items-table th { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; text-align: left; padding: 12px; font-size: 12px; }
        .items-table td { border-bottom: 1px solid #e2e8f0; padding: 12px; font-size: 13px; color: #334155; }
        .summary-table { width: 40%; margin-left: auto; border-collapse: collapse; margin-bottom: 40px; }
        .summary-table td { padding: 8px 12px; font-size: 13px; }
        .summary-label { color: #64748b; text-align: right; }
        .summary-value { font-weight: 600; text-align: right; color: #0f172a; }
        .summary-total { font-size: 16px; font-weight: bold; color: #4f46e5; border-top: 2px solid #e2e8f0; }
        .footer { text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 50px; font-size: 11px; color: #94a3b8; }
        .badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: bold; border-radius: 9999px; text-transform: uppercase; }
        .badge-unpaid { background-color: #fef2f2; color: #991b1b; }
        .badge-paid { background-color: #f0fdf4; color: #166534; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <h1 class="logo-text">{{ __('Kairo CORE') }}</h1>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">{{ __('Enterprise Software Infrastructure') }}</div>
            </td>
            <td style="text-align: right;">
                <h2 class="invoice-title">{{ __('Invoice') }}</h2>
                <div style="margin-top: 5px; font-size: 14px; font-weight: 600; color: #475569;"># {{ $invoice->invoice_number }}</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <div class="section-title">{{ __('Invoice To') }}</div>
                <div class="bill-details">
                    <strong>{{ $invoice->school->name }}</strong><br>
                    Tenant Subdomain: {{ $invoice->school->subdomain }}.schoolcore.test<br>
                    Registered ID Reference: #SCH-{{ str_pad($invoice->school_id, 5, '0', STR_PAD_LEFT) }}
                </div>
            </td>
            <td style="text-align: right;">
                <div class="section-title">{{ __('Invoice Details') }}</div>
                <div class="bill-details">
                    <strong>{{ __('Issue Date:') }}</strong> {{ $invoice->issue_date->format('M d, Y') }}<br>
                    <strong>{{ __('Due Date:') }}</strong> {{ $invoice->due_date->format('M d, Y') }}<br>
                    <strong>{{ __('Status:') }}</strong> 
                    <span class="badge {{ $invoice->status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                        {{ $invoice->status }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 60%;">{{ __('Description') }}</th>
                <th style="width: 10%; text-align: center;">{{ __('Qty') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('Rate') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-label">{{ __('Subtotal') }}</td>
            <td class="summary-value">${{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">{{ __('Discount') }}</td>
            <td class="summary-value">${{ number_format($invoice->discount, 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label summary-total">{{ __('Total Due') }}</td>
            <td class="summary-value summary-total" style="color: #4f46e5;">${{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
        </tr>
    </table>

    <div style="margin-top: 40px; background-color: #f8fafc; border-left: 4px solid #4f46e5; padding: 16px; border-radius: 4px;">
        <div style="font-weight: 700; font-size: 12px; color: #1e293b; text-transform: uppercase; margin-bottom: 6px;">{{ __('Payment Information & Notes') }}</div>
        <p style="margin: 0; font-size: 12px; color: #475569; line-height: 1.6;">{{ $invoice->payment_instructions ?? 'Please process standard bank deposit payment execution plans linked inside your dashboard interfaces.' }}</p>
    </div>

    <div class="footer">
        <p>{{ __('This is a system-generated document. Dynamic security check sum:') }} <strong>{{ $invoice->integrity_hash }}</strong></p>
        <p>{{ __('&copy; 2026 Kairo CORE Software Inc. All rights reserved.') }}</p>
    </div>

</body>
</html>