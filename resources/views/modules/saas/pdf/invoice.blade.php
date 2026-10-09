<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Subscription Invoice: {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222222; margin: 0; padding: 28px; line-height: 1.5; font-size: 12px; }
        .brand { font-size: 24px; font-weight: bold; color: #1F2E43; margin: 0; letter-spacing: 1px; }
        .brand-sub { font-size: 10px; color: #666666; text-transform: uppercase; letter-spacing: 2px; margin-top: 3px; }
        .doc-title { font-size: 26px; font-weight: bold; color: #EF5F4D; margin: 0; text-transform: uppercase; letter-spacing: 2px; text-align: right; }
        .doc-meta { font-size: 11px; color: #444444; text-align: right; margin-top: 6px; line-height: 1.7; }
        .rule { height: 3px; background: #1F2E43; margin: 16px 0 22px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #1F2E43; margin-bottom: 6px; }
        .party { font-size: 12px; line-height: 1.7; color: #444444; }
        .party strong { color: #222222; }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout > tbody > tr > td { vertical-align: top; width: 50%; padding: 0 10px; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0 8px; }
        table.items th { background: #1F2E43; color: #ffffff; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; text-align: left; padding: 10px 12px; }
        table.items td { border-bottom: 1px solid #E2E8F0; padding: 11px 12px; font-size: 12px; color: #333333; }
        table.items tr:nth-child(even) td { background: #F6F7F9; }
        table.totals { width: 45%; margin-left: auto; border-collapse: collapse; margin-top: 8px; }
        table.totals td { padding: 7px 12px; font-size: 12px; }
        table.totals td.label { color: #666666; text-align: right; }
        table.totals td.value { font-weight: bold; text-align: right; color: #222222; }
        table.totals tr.total td { border-top: 2px solid #1F2E43; font-size: 15px; }
        table.totals tr.total td.label { color: #1F2E43; font-weight: bold; }
        table.totals tr.total td.value { color: #EF5F4D; font-size: 16px; }
        .note-box { background: #F3F5F8; border-left: 4px solid #EF5F4D; padding: 12px 16px; margin: 22px 0; font-size: 11px; color: #444444; }
        .note-box .note-title { font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 10px; color: #1F2E43; margin-bottom: 5px; }
        table.pay { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.pay td { padding: 5px 10px; font-size: 11px; color: #444444; border-bottom: 1px solid #E2E8F0; }
        table.pay td.method { font-weight: bold; color: #1F2E43; width: 45%; }
        .footer { border-top: 1px solid #E2E8F0; margin-top: 28px; padding-top: 14px; font-size: 10px; color: #666666; text-align: center; line-height: 1.7; }
        .footer .hash { color: #999999; font-size: 9px; word-break: break-all; }
    </style>
</head>
<body>
    @php
        $brand = email_branding();
        $school = $invoice->school;
    @endphp

    <table class="layout">
        <tr>
            <td style="padding-left: 0;">
                <p class="brand">{{ strtoupper($brand['company_name']) }}</p>
                <div class="brand-sub">{{ __('Enterprise Software Infrastructure') }}</div>
            </td>
            <td style="text-align: right; padding-right: 0;">
                <h1 class="doc-title">{{ __('Invoice') }}</h1>
                <div class="doc-meta">
                    <strong>{{ __('Invoice No:') }}</strong> {{ $invoice->invoice_number }}<br>
                    <strong>{{ __('Date:') }}</strong> {{ $invoice->issue_date->format('d M Y') }}<br>
                    <strong>{{ __('Due Date:') }}</strong> {{ $invoice->due_date->format('d M Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="layout">
        <tr>
            <td style="padding-left: 0;">
                <div class="section-title">{{ __('Billed To') }}</div>
                <div class="party">
                    <strong>{{ $school->name }}</strong><br>
                    @if ($school->physical_address){{ $school->physical_address }}<br>@endif
                    @if ($school->phone_number){{ __('Phone:') }} {{ $school->phone_number }}<br>@endif
                    @if ($school->email_address){{ __('Email:') }} {{ $school->email_address }}@endif
                </div>
            </td>
            <td style="padding-right: 0;">
                <div class="section-title">{{ __('From') }}</div>
                <div class="party">
                    <strong>{{ $brand['company_name'] }}</strong><br>
                    @if ($brand['company_address']){{ $brand['company_address'] }}<br>@endif
                    @if ($brand['company_phone']){{ __('Phone:') }} {{ $brand['company_phone'] }}<br>@endif
                    @if ($brand['company_email']){{ __('Email:') }} {{ $brand['company_email'] }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 60%;">{{ __('Description') }}</th>
                <th style="width: 10%; text-align: center;">{{ __('Qty') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('Unit Price') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="color: #666666;">{{ __('Subscription charges for :plan', ['plan' => $invoice->subscription?->plan?->name ?? __('your plan')]) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">{{ __('Subtotal') }}</td>
            <td class="value">${{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Discount') }}</td>
            <td class="value">${{ number_format($invoice->discount, 2) }}</td>
        </tr>
        <tr class="total">
            <td class="label">{{ __('Total Due') }}</td>
            <td class="value">${{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
        </tr>
    </table>

    <div class="note-box">
        <div class="note-title">{{ __('Payment Details') }}</div>
        {{ $invoice->payment_instructions ?: __('Please use the invoice number as your payment reference.') }}
        <table class="pay">
            <tr>
                <td class="method">{{ __('EcoCash') }}</td>
                <td>0785556855</td>
            </tr>
            <tr>
                <td class="method">{{ __('Bank (CABS USD Account)') }}</td>
                <td>1149411511</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>{{ __('Thank you for your business! Please use the invoice number as your payment reference.') }}</p>
        <p>{{ __('All international invoices can be settled via multi-currency or cross-border payment rails.') }}</p>
        <p class="hash">{{ __('Security checksum:') }} {{ $invoice->integrity_hash }}</p>
        <p>&copy; {{ date('Y') }} {{ $brand['company_name'] }}. {{ __('All rights reserved.') }}</p>
    </div>
</body>
</html>
