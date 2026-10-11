@php
    $symbol = $totals['symbol'] ?? '$';
    $currency = $totals['currency'] ?? null;
    $discount = (float) ($totals['discount'] ?? 0);
    $totalLabel = $totals['totalLabel'] ?? __('Total Due');
@endphp
<table width="100%" style="margin-top: 14px;">
    <tr>
        <td width="58%">&nbsp;</td>
        <td width="42%">
            <table class="totals">
                @if(isset($totals['subtotal']))
                    <tr>
                        <td>{{ $totals['subtotalLabel'] ?? __('Subtotal') }}</td>
                        <td class="value">{{ $symbol }}{{ number_format((float) $totals['subtotal'], 2) }}</td>
                    </tr>
                @endif
                @foreach(($totals['extra'] ?? []) as $extra)
                    <tr>
                        <td>{{ $extra['label'] ?? '' }}</td>
                        <td class="value">{{ $extra['value'] ?? '' }}</td>
                    </tr>
                @endforeach
                @if($discount > 0)
                    <tr>
                        <td>{{ __('Discount') }}</td>
                        <td class="value">-{{ $symbol }}{{ number_format($discount, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td>{{ $totalLabel }}</td>
                    <td class="value">{{ $symbol }}{{ number_format((float) ($totals['total'] ?? 0), 2) }}@if($currency) {{ $currency }}@endif</td>
                </tr>
            </table>
        </td>
    </tr>
</table>