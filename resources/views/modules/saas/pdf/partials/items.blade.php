<table class="items">
    <thead>
        <tr>
            <th width="52%">{{ __('Description') }}</th>
            <th width="10%" style="text-align: center;">{{ __('Qty') }}</th>
            <th width="19%" style="text-align: right;">{{ __('Unit Price') }}</th>
            <th width="19%" style="text-align: right;">{{ __('Amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($itemRows as $row)
            <tr>
                <td>
                    <div class="item-title">{{ $row['title'] ?? '' }}</div>
                    @if(!empty($row['detail']))<div class="item-desc">{!! nl2br(e($row['detail'])) !!}</div>@endif
                </td>
                <td style="text-align: center;">{{ $row['qty'] ?? '' }}</td>
                <td style="text-align: right;">{{ $row['unit'] ?? '' }}</td>
                <td style="text-align: right; font-weight: bold; color: {{ $cfg['dark_color'] ?? '#1F2E43' }};">{{ $row['amount'] ?? '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="color: #666666;">{{ $emptyMessage ?? '' }}</td>
            </tr>
        @endforelse
    </tbody>
</table>