@php
    $channels = array_values(array_filter((array) ($cfg['payment_channels'] ?? []), fn ($c) => filled($c['method'] ?? null) || filled($c['number'] ?? null)));
    $bank = $bankDetails ?? [];
    $hasBank = collect($bank)->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp
@if(!empty($channels) || $hasBank)
    <p class="pay-title">{{ $paymentTitle ?? $cfg['callout_title'] ?? __('Payment Details') }}</p>
    <table class="pay">
        @foreach($channels as $channel)
            <tr>
                <td class="method">{{ $channel['method'] ?? '' }}</td>
                <td>{{ $channel['number'] ?? '' }}</td>
            </tr>
        @endforeach
        @if($hasBank)
            <tr>
                <td class="method">{{ $bank['label'] ?? __('Bank Deposit') }}</td>
                <td>
                    @php
                        $bankLine = collect([
                            $bank['bank_name'] ?? null,
                            $bank['bank_account_name'] ?? null,
                            $bank['bank_account_number'] ?? null,
                            !empty($bank['bank_branch_code']) ? __('Branch').': '.$bank['bank_branch_code'] : null,
                            !empty($bank['bank_swift_code']) ? __('SWIFT').': '.$bank['bank_swift_code'] : null,
                        ])->filter()->implode(' · ');
                    @endphp
                    {{ $bankLine }}
                </td>
            </tr>
        @endif
    </table>
@endif