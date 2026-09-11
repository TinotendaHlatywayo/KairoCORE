<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $order->order_number }} — Purchase Order</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta-table td { padding: 3px 6px; vertical-align: top; font-size: 10px; }
        .meta-label { width: 190px; color: #64748b; font-weight: bold; }
        .meta-value { color: #0f172a; font-weight: bold; }
        .supplier-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 16px; }
        .supplier-label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 3px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .items-table th {
            background-color: {{ $primaryColor }};
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            text-align: left;
            padding: 7px;
            border: 1px solid #e2e8f0;
        }
        .items-table td { padding: 7px; border: 1px solid #cbd5e1; font-size: 10px; }
        .items-table tr:nth-child(even) { background-color: #f8fafc; }
        .num { text-align: right; }
        .total-row td { font-weight: bold; background-color: #eef2ff; }
        .grand-total { font-size: 14px; color: {{ $primaryColor }}; }
        .sign-line { border-top: 1px solid #334155; margin-top: 44px; }
        .sign-role { font-size: 9px; color: #64748b; }
        .footer { border-top: 1px solid #cbd5e1; padding-top: 10px; margin-top: 30px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <x-school-pdf-header :school="$school" :title="__('Purchase Order')" :subtitle="$order->order_number" :primary-color="$primaryColor" />

    @if ($order->supplier)
        <div class="supplier-box">
            <div class="supplier-label">{{ __('Supply From') }}</div>
            <div style="font-weight:bold; font-size:12px; color:#0f172a;">{{ $order->supplier->name }}</div>
            @if ($order->supplier->physical_address || $order->supplier->phone || $order->supplier->email)
                <div style="font-size:9px; color:#475569; margin-top:3px;">
                    {{ collect([$order->supplier->physical_address, $order->supplier->phone, $order->supplier->email])->filter()->implode('  |  ') }}
                </div>
            @endif
        </div>
    @endif

    <table class="meta-table">
        <tr>
            <td class="meta-label">Purchase Order Number:</td>
            <td class="meta-value">{{ $order->order_number }}</td>
            <td class="meta-label">Order Date:</td>
            <td>{{ $order->order_date?->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Expected Delivery:</td>
            <td>{{ $order->expected_delivery_date?->format('d M Y') }}</td>
            <td class="meta-label">Status:</td>
            <td>{{ ucfirst($order->status) }}</td>
        </tr>
    </table>

    @if ($order->request)
        <div style="font-size:9px; color:#64748b; margin-bottom:10px;">
            Reference Requisition: <span style="font-weight:bold; color:#0f172a;">{{ $order->request->request_number }}</span>
            @if ($order->request->requester)
                ({{ __('requested by') }} {{ $order->request->requester->name }})
            @endif
        </div>
    @endif

    @php
        $grandTotal = 0;
    @endphp
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:34px;">#</th>
                <th>{{ __('Item / Description') }}</th>
                <th style="width:70px;" class="num">{{ __('Quantity') }}</th>
                <th style="width:90px;" class="num">{{ __('Unit Cost') }}</th>
                <th style="width:100px;" class="num">{{ __('Total Cost') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $index => $item)
                @php
                    $lineTotal = (float) $item->quantity_ordered * (float) $item->unit_cost;
                    $grandTotal += $lineTotal;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->inventoryItem?->name ?? $item->inventoryItem?->name }}
                        @if (! $item->inventoryItem)
                            <span style="color:#94a3b8;">({{ __('unlinked item') }})</span>
                        @endif
                    </td>
                    <td class="num">{{ $item->quantity_ordered }}</td>
                    <td class="num">${{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td class="num">${{ number_format($lineTotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#94a3b8;">{{ __('No order lines.') }}</td></tr>
            @endforelse
        </tbody>
        <tr class="total-row">
            <td colspan="4" style="text-align:right;">{{ __('Total Order Value') }}</td>
            <td class="num grand-total">${{ number_format($grandTotal, 2) }}</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse: collapse; margin-top:30px;">
        <tr>
            <td style="width:50%; padding-right:10px; vertical-align:top;">
                <div class="sign-line"></div>
                <div class="sign-role">{{ __('Authorized Signature — Procurement Officer') }}</div>
            </td>
            <td style="width:50%; padding-left:10px; vertical-align:top;">
                <div class="sign-line"></div>
                <div class="sign-role">{{ __('Signature & Stump — Head of School') }}</div>
            </td>
        </tr>
    </table>

    <table class="footer">
        <tr>
            <td>Generated by Kairo CORE • {{ config('app.name') }} • {{ now()->format('Y-m-d H:i') }}</td>
        </tr>
    </table>
</body>
</html>