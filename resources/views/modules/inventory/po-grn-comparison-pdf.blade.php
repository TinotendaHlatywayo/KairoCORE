<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $po->order_number }} — GRN Comparison</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta-table td { padding: 3px 6px; vertical-align: top; font-size: 9px; }
        .meta-label { width: 150px; color: #64748b; font-weight: bold; }
        .meta-value { color: #0f172a; font-weight: bold; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            background-color: {{ $primaryColor }};
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center;
            padding: 7px;
            border: 1px solid #e2e8f0;
        }
        .data-table th.left, .data-table td.left { text-align: left; }
        .data-table td { padding: 7px; border: 1px solid #cbd5e1; font-size: 10px; text-align: center; }
        .data-table tr:nth-child(even) { background-color: #f8fafc; }
        .total-row td { font-weight: bold; background-color: #eef2ff; }
        .state-label { font-size: 9px; font-weight: bold; }
        .footer { border-top: 1px solid #cbd5e1; padding-top: 10px; margin-top: 24px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <x-school-pdf-header :school="$school" :title="__('GRN vs PO Comparison')" :subtitle="$po->order_number" :primary-color="$primaryColor" />

    <table class="meta-table">
        <tr>
            <td class="meta-label">Supplier:</td>
            <td class="meta-value">{{ $po->supplier?->name ?? '-' }}</td>
            <td class="meta-label">Order Date:</td>
            <td>{{ $po->order_date?->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status:</td>
            <td>{{ ucfirst($po->status) }}</td>
            <td class="meta-label">Total (LPO):</td>
            <td>${{ number_format((float) $po->total_amount, 2) }}</td>
        </tr>
    </table>

    @php
        $lines = [];
        foreach ($po->items as $poItem) {
            $received = 0;
            $rejected = 0;
            $grnCount = 0;

            foreach ($po->grns as $grn) {
                foreach ($grn->items as $grnItem) {
                    if ((int) $grnItem->inventory_item_id === (int) $poItem->inventory_item_id) {
                        $received += (int) $grnItem->quantity_accepted;
                        $rejected += (int) $grnItem->quantity_rejected;
                        $grnCount++;
                    }
                }
            }

            $ordered = (int) $poItem->quantity_ordered;
            $outstanding = max(0, $ordered - $received);

            if ($ordered === 0) {
                $stateLabel = 'Pending';
            } elseif ($received >= $ordered) {
                $stateLabel = 'Complete';
            } elseif ($received > 0) {
                $stateLabel = 'Partial';
            } else {
                $stateLabel = 'Not Received';
            }

            $lines[] = [
                'item' => $poItem->inventoryItem?->name ?? 'Unlinked item',
                'ordered' => $ordered,
                'received' => $received,
                'rejected' => $rejected,
                'outstanding' => $outstanding,
                'grn_count' => $grnCount,
                'state' => $stateLabel,
            ];
        }
    @endphp

    <table class="data-table">
        <thead>
            <tr>
                <th class="left" style="width:30%;">Item</th>
                <th>Ordered</th>
                <th>Received</th>
                <th>Rejected</th>
                <th>Outstanding</th>
                <th>GRN Records</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lines as $line)
                <tr>
                    <td class="left">{{ $line['item'] }}</td>
                    <td>{{ $line['ordered'] }}</td>
                    <td>{{ $line['received'] }}</td>
                    <td>{{ $line['rejected'] }}</td>
                    <td>{{ $line['outstanding'] }}</td>
                    <td>{{ $line['grn_count'] }}</td>
                    <td class="state-label" style="color:{{ $primaryColor }};">{{ $line['state'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; color:#94a3b8;">No ordered items.</td></tr>
            @endforelse
        </tbody>
        <tr class="total-row">
            <td class="left">Totals</td>
            <td>{{ array_sum(array_column($lines, 'ordered')) }}</td>
            <td>{{ array_sum(array_column($lines, 'received')) }}</td>
            <td>{{ array_sum(array_column($lines, 'rejected')) }}</td>
            <td>{{ array_sum(array_column($lines, 'outstanding')) }}</td>
            <td>{{ array_sum(array_column($lines, 'grn_count')) }}</td>
            <td></td>
        </tr>
    </table>

    <table style="width:100%; border-collapse: collapse; margin-top:26px;">
        <tr>
            <td style="width:50%; padding-right:10px; vertical-align:top;">
                <div style="border-top:1px solid #334155; margin-top:44px;"></div>
                <div style="font-size:9px; color:#64748b;">Received By (Goods Inwards Officer)</div>
            </td>
            <td style="width:50%; padding-left:10px; vertical-align:top;">
                <div style="border-top:1px solid #334155; margin-top:44px;"></div>
                <div style="font-size:9px; color:#64748b;">Authorized Signature — Stores / Procurement</div>
            </td>
        </tr>
    </table>

    <table class="footer">
        <tr><td>Generated by Kairo CORE • {{ config('app.name') }} • {{ now()->format('Y-m-d H:i') }}</td></tr>
    </table>
</body>
</html>