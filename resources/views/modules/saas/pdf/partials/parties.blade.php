<table width="100%">
    <tr>
        <td width="50%" style="padding-right: 18px;">
            <div class="section-title">{{ $billedLabel ?? __('Billed To') }}</div>
            <div class="party">
                <span class="party-name">{{ $billedToName ?? '' }}</span><br>
                @foreach(($billedToLines ?? []) as $line)
                    @if(filled($line)){{ $line }}<br>@endif
                @endforeach
            </div>
        </td>
        <td width="50%">
            <div class="section-title">{{ __('From') }}</div>
            <div class="party">
                <span class="party-name">{{ $cfg['business_name'] ?? '' }}</span><br>
                @if (!empty($cfg['from_line_1'])){{ $cfg['from_line_1'] }}<br>@endif
                @if (!empty($cfg['from_line_2'])){{ $cfg['from_line_2'] }}<br>@endif
                @if (!empty($cfg['from_phone'])){{ __('Phone:') }} {{ $cfg['from_phone'] }}<br>@endif
                @if (!empty($cfg['from_email'])){{ __('Email:') }} {{ $cfg['from_email'] }}@endif
            </div>
        </td>
    </tr>
</table>