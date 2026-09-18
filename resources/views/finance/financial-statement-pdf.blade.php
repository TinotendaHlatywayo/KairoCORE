<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Official Financial Statement') }}</title>
    @php
        $financeTheme = finance_document_theme($data['template'] ?? null, 'statement', $data['schoolModel'] ?? null);
        $h = $financeTheme['sections']['header'];
        $t = $financeTheme['sections']['title'];
        $m = $financeTheme['sections']['metadata'];
        $tb = $financeTheme['sections']['table'];
        $f = $financeTheme['sections']['footer'];
        $structure = $financeTheme['structure'] ?? 'classic';
        $logoSize = (int) $h['logo_size'];
        $profile = document_school_profile($data['schoolModel'] ?? null, $data['config'] ?? []);
        $logoPath = finance_document_logo_path($h, $data['config'] ?? []);
        $positive = $financeTheme['success_color'];
        $negative = $financeTheme['danger_color'];
        $net = (float) $data['netCashFlow'];
    @endphp
    @include('modules.finance.partials.document-styles', [
        'financeTheme' => $financeTheme,
        'h' => $h,
        't' => $t,
        'm' => $m,
        'tb' => $tb,
        'f' => $f,
        'bodyFontSize' => 12,
    ])
</head>
<body class="style-{{ $structure }}">
<div class="doc-page">

    @include('modules.finance.partials.document-header', [
        'financeTheme' => $financeTheme,
        'h' => $h,
        't' => $t,
        'profile' => $profile,
        'logoPath' => $logoPath,
        'logoSize' => $logoSize,
        'title' => __('OFFICIAL FINANCIAL STATEMENT'),
        'refs' => [],
    ])

    <!-- Report period metadata -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td style="font-size:{{ $m['font_size'] }}px; color:{{ $m['color'] }}; line-height:1.5;">
                <strong>{{ __('Reporting Period:') }}</strong> {{ $data['startDate'] }} {{ __('to') }} {{ $data['endDate'] }}<br/>
                <strong>{{ __('Bank Account:') }}</strong> {{ $data['bankAccountName'] ?? __('All Accounts (Combined)') }}<br/>
                <strong>{{ __('Generated On:') }}</strong> {{ $data['generatedAt'] }}
            </td>
            <td style="text-align:right; font-size:{{ $m['font_size'] }}px; color:{{ $m['color'] }}; line-height:1.5;">
                <strong>{{ __('Currency:') }}</strong> {{ __('USD') }}<br/>
                <strong>{{ __('Ledger Standard:') }}</strong> {{ __('Base USD Currency') }}
            </td>
        </tr>
    </table>

    <!-- Cash flow summary -->
    @php
        $muted = $financeTheme['muted_color'] ?? '#6b7280';
        $tint = $financeTheme['green_tint'] ?? '#e0f2fe';
    @endphp
    <table class="results-table">
        <thead>
            <tr>
                <th style="text-align:left; width:46%;">{{ __('Description') }}</th>
                <th style="width:18%; text-align:right;">{{ __('Outflows (−)') }}</th>
                <th style="width:18%; text-align:right;">{{ __('Inflows (+)') }}</th>
                <th style="width:18%; text-align:right;">{{ __('Balance (USD)') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:left; font-weight:bold;">{{ __('Opening Bank Balance') }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right;"></td>
                <td style="text-align:right; font-weight:bold;">${{ number_format((float) ($data['openingBalance'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td style="text-align:left; font-weight:bold;">{{ __('Total Fees Collected (school fees in period)') }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right; color:{{ $positive }}; font-weight:bold;">+${{ number_format((float) ($data['feeRevenue'] ?? 0), 2) }}</td>
                <td style="text-align:right;"></td>
            </tr>
            <tr>
                <td style="text-align:left; padding-left:18px; color:{{ $muted }};">{{ __('School fees recorded within the period') }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right; color:{{ $positive }};">+${{ number_format((float) ($data['feeRevenue'] ?? 0), 2) }}</td>
                <td style="text-align:right;"></td>
            </tr>
            @if(count($data['revenueStreams'] ?? []))
                <tr>
                    <td style="text-align:left; font-weight:bold;">{{ __('Other Income (Revenue Streams)') }}</td>
                    <td style="text-align:right;"></td>
                    <td style="text-align:right; color:{{ $positive }}; font-weight:bold;">+${{ number_format((float) ($data['revenueStreamTotal'] ?? 0), 2) }}</td>
                    <td style="text-align:right;"></td>
                </tr>
                @foreach($data['revenueStreams'] as $stream)
                    <tr>
                        <td style="text-align:left; padding-left:18px; color:{{ $muted }};">
                            {{ $stream['name'] }} ({{ $stream['category'] }}){{ $stream['date'] ? ' — '.$stream['date'] : '' }}
                        </td>
                        <td style="text-align:right;"></td>
                        <td style="text-align:right; color:{{ $positive }};">+${{ number_format($stream['amount'], 2) }}</td>
                        <td style="text-align:right;"></td>
                    </tr>
                @endforeach
            @endif
            <tr>
                <td style="text-align:left; font-weight:bold;">{{ __('Less Refunds Issued') }}</td>
                <td style="text-align:right; color:{{ $negative }}; font-weight:bold;">-${{ number_format((float) $data['totalRefunds'], 2) }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right;"></td>
            </tr>
            @foreach($data['refundItems'] ?? [] as $refund)
                <tr>
                    <td style="text-align:left; padding-left:18px; color:{{ $muted }};">
                        {{ $refund['reference'] ?? __('Refund') }}{{ $refund['date'] ? ' — '.$refund['date'] : '' }}
                    </td>
                    <td style="text-align:right; color:{{ $negative }};">-${{ number_format($refund['amount'], 2) }}</td>
                    <td style="text-align:right;"></td>
                    <td style="text-align:right;"></td>
                </tr>
            @endforeach
            <tr>
                <td style="text-align:left; font-weight:bold;">{{ __('Total Expenses & Outflows') }}</td>
                <td style="text-align:right; color:{{ $negative }}; font-weight:bold;">-${{ number_format((float) $data['totalExpenses'], 2) }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right;"></td>
            </tr>
            @foreach($data['expenseItems'] ?? [] as $expense)
                <tr>
                    <td style="text-align:left; padding-left:18px; color:{{ $muted }};">
                        {{ $expense['name'] }} ({{ $expense['category'] }}){{ $expense['date'] ? ' — '.$expense['date'] : '' }}
                    </td>
                    <td style="text-align:right; color:{{ $negative }};">-${{ number_format($expense['amount'], 2) }}</td>
                    <td style="text-align:right;"></td>
                    <td style="text-align:right;"></td>
                </tr>
            @endforeach
            <tr>
                <td style="text-align:left; padding-left:18px; color:{{ $muted }};">
                    {{ __('Of which — Staff Salaries') }}{{ !empty($data['salariesDate']) ? ' — '.$data['salariesDate'] : '' }}
                </td>
                <td style="text-align:right; color:{{ $negative }}; font-weight:bold;">-${{ number_format((float) ($data['totalSalaries'] ?? 0), 2) }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right;"></td>
            </tr>
            <tr>
                <td style="text-align:left; font-weight:bold;">{{ __('Total Outflows (−) / Total Inflows (+)') }}</td>
                <td style="text-align:right; color:{{ $negative }}; font-weight:bold;">-${{ number_format((float) ($data['totalOutflows'] ?? 0), 2) }}</td>
                <td style="text-align:right; color:{{ $positive }}; font-weight:bold;">+${{ number_format((float) ($data['totalInflows'] ?? 0), 2) }}</td>
                <td style="text-align:right;"></td>
            </tr>
            <tr style="background: {{ $tint }};">
                <td style="text-align:left; font-weight:bold;">{{ __('Net Cash Flow Balance') }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right;"></td>
                <td style="text-align:right; font-weight:bold; color:{{ $net < 0 ? $negative : $positive }};">${{ number_format($net, 2) }}</td>
            </tr>
            <tr style="background: {{ $tint }};">
                <td style="text-align:left; font-weight:bold;">{{ __('Closing Balance') }}</td>
                <td style="text-align:right;"></td>
                <td style="text-align:right;"></td>
                <td style="text-align:right; font-weight:bold; color:{{ $net < 0 ? $negative : $positive }};">${{ number_format((float) ($data['closingBalance'] ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    @include('modules.finance.partials.document-footer', [
        'f' => $f,
        'financeTheme' => $financeTheme,
        'signatureLeft' => '',
        'signatureRight' => '',
        'qrUrl' => null,
        'fallbackFooter' => $data['config']['statement_footer'] ?? '',
    ])

</div>

<div class="powered-by">Powered by Kairo CORE</div>
</body>
</html>