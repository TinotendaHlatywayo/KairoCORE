<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $request->request_number }} — Purchase Requisition</title>
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
        .purpose-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; margin-bottom: 18px; }
        .purpose-label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 3px; }
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .sign-table td { width: 50%; padding: 0 10px; vertical-align: top; }
        .sign-line { border-top: 1px solid #334155; margin-top: 44px; }
        .sign-name { font-weight: bold; color: #0f172a; font-size: 11px; margin-top: 6px; }
        .sign-role { font-size: 9px; color: #64748b; }
        .footer { border-top: 1px solid #cbd5e1; padding-top: 10px; margin-top: 30px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <x-school-pdf-header :school="$school" :title="__('Purchase Requisition')" :subtitle="$request->request_number" :primary-color="$primaryColor" />

    <table class="meta-table">
        <tr>
            <td class="meta-label">Name of Requester:</td>
            <td class="meta-value">{{ $request->requester?->name ?? '-' }}</td>
            <td class="meta-label">Request Reference:</td>
            <td class="meta-value">{{ $request->request_number }}</td>
        </tr>
        <tr>
            <td class="meta-label">Department:</td>
            <td>{{ $departmentName }}</td>
            <td class="meta-label">Date Requested:</td>
            <td>{{ $request->created_at?->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Urgency:</td>
            <td>{{ ucfirst($request->urgency) }}</td>
            <td class="meta-label">Status:</td>
            <td>{{ ucfirst($request->status) }}</td>
        </tr>
    </table>

    <div class="purpose-box">
        <div class="purpose-label">{{ __('Purpose of the Items') }}</div>
        <div>{{ $request->purpose ?: __('Not specified.') }}</div>
    </div>

    @php
        $grandTotal = 0;
    @endphp
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:34px;">#</th>
                <th>{{ __('Item / Description') }}</th>
                <th style="width:70px;" class="num">{{ __('Quantity') }}</th>
                <th style="width:90px;" class="num">{{ __('Unit Price') }}</th>
                <th style="width:100px;" class="num">{{ __('Total Cost') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($request->items as $index => $item)
                @php
                    $lineTotal = (float) $item->quantity * (float) $item->estimated_unit_cost;
                    $grandTotal += $lineTotal;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->item_name }}
                        @if ($item->inventoryItem)
                            <span style="color:#94a3b8;"> ({{ $item->inventoryItem->name }})</span>
                        @endif
                    </td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">${{ number_format((float) $item->estimated_unit_cost, 2) }}</td>
                    <td class="num">${{ number_format($lineTotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#94a3b8;">{{ __('No items on this requisition.') }}</td></tr>
            @endforelse
        </tbody>
        <tr class="total-row">
            <td colspan="4" style="text-align:right;">{{ __('Total Requisition Value') }}</td>
            <td class="num grand-total">${{ number_format($grandTotal, 2) }}</td>
        </tr>
    </table>

    <table class="sign-table">
        <tr>
            <td>
                @if ($request->requester_signature && $request->date_signed)
                    <div class="sign-name">{{ $request->requester_signature }}</div>
                    <div class="sign-role">{{ __('Requested by') }}</div>
                    <div style="font-size:8px; color:#94a3b8; margin-top:2px;">{{ __('Signed on') }} {{ $request->date_signed->format('d M Y') }}</div>
                @else
                    <div class="sign-line"></div>
                    <div class="sign-role">{{ __('Signature — Requester (Name & Surname)') }}</div>
                @endif
            </td>
            <td>
                @if ($request->officer_signature && $request->date_signed)
                    <div class="sign-name">{{ $request->officer_signature }}</div>
                    <div class="sign-role">{{ __('Procurement Officer') }}</div>
                @else
                    <div class="sign-line"></div>
                    <div class="sign-role">{{ __('Signature — Procurement Officer (Name & Surname)') }}</div>
                @endif
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