<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $brand = email_branding();
        $school = $invoice->school;

        $logoUri = document_logo_data_uri();
        $watermarkUri = document_watermark_data_uri();

        $discount = (float) ($invoice->discount ?? 0);
        $calloutBody = $invoice->payment_instructions ?: ($cfg['callout_body'] ?? null);

        // Outstanding balance from the school's other unpaid invoices. Shown as
        // its own line and folded into the grand total (presentation only).
        $outstanding = (float) ($outstanding ?? 0);
        $grandTotal = (float) $invoice->total + $outstanding;

        // Line items: "Title [details]" -> bold title + grey details underneath.
        $itemRows = collect($invoice->items)->map(function ($item) {
            $raw = trim((string) $item->description);
            $lines = preg_split('/\r\n|\r|\n/', $raw, 2);
            $title = $lines[0];
            $detail = $lines[1] ?? '';
            if ($detail === '' && preg_match('/^(.*?)\s*\[(.+)\]\s*$/s', $title, $m)) {
                $title = trim($m[1]);
                $detail = trim($m[2]);
            }

            return [
                'title' => $title,
                'detail' => $detail,
                'qty' => $item->quantity,
                'unit' => '$'.number_format((float) $item->unit_price, 2),
                'amount' => '$'.number_format((float) $item->total, 2),
            ];
        })->all();

        $emptyMessage = __('Subscription charges for :plan', ['plan' => $invoice->subscription?->plan?->name ?? __('your plan')]);


    @endphp
    <meta charset="UTF-8">
    <title>SaaS Subscription Invoice: {{ $invoice->invoice_number }}</title>
    @include('modules.saas.pdf.partials.styles', ['cfg' => $cfg])
</head>
<body>
    @include('modules.saas.pdf.partials.watermark', ['cfg' => $cfg, 'watermarkUri' => $watermarkUri])

    @include('modules.saas.pdf.partials.header', [
        'cfg' => $cfg,
        'logoUri' => $logoUri,
        'documentTitle' => __('Invoice'),
        'documentMeta' => [
            __('Invoice No:') => $invoice->invoice_number,
            __('Date:') => document_date($invoice->issue_date),
            __('Due Date:') => document_date($invoice->due_date),
        ],
    ])

    @include('modules.saas.pdf.partials.parties', [
        'cfg' => $cfg,
        'billedToName' => $school->name ?? '',
        'billedToLines' => array_filter([
            $school->physical_address ?? null,
            !empty($school->phone_number) ? __('Phone:').' '.$school->phone_number : null,
            !empty($school->email_address) ? __('Email:').' '.$school->email_address : null,
        ]),
    ])

    @include('modules.saas.pdf.partials.items', [
        'cfg' => $cfg,
        'itemRows' => $itemRows,
        'emptyMessage' => $emptyMessage,
    ])

    @include('modules.saas.pdf.partials.totals', [
        'cfg' => $cfg,
        'totals' => [
            'subtotal' => $invoice->subtotal,
            'extra' => $outstanding > 0 ? [[
                'label' => __('Outstanding (previous invoices)'),
                'value' => '$'.number_format($outstanding, 2),
            ]] : [],
            'discount' => $discount,
            'total' => $grandTotal,
            'totalLabel' => __('Total Due'),
        ],
    ])

    {{-- Callout box --}}
    @if(!empty($cfg['callout_title']) || !empty($calloutBody))
        <div class="note-box">
            @if(!empty($cfg['callout_title']))<div class="note-title">{{ $cfg['callout_title'] }}</div>@endif
            {!! nl2br(e($calloutBody)) !!}
        </div>
    @endif

    @php
        $platformBilling = \Modules\SaaS\Models\PlatformBillingSetting::current();
        $bankDetails = [
            'bank_name' => $platformBilling->bank_name ?? '',
            'bank_account_name' => $platformBilling->bank_account_name ?? '',
            'bank_account_number' => $platformBilling->bank_account_number ?? '',
            'bank_branch_code' => $platformBilling->bank_branch_code ?? '',
            'bank_swift_code' => $platformBilling->bank_swift_code ?? '',
        ];
    @endphp
    @include('modules.saas.pdf.partials.payments', ['cfg' => $cfg, 'bankDetails' => $bankDetails])



    @include('modules.saas.pdf.partials.footer', [
        'cfg' => $cfg,
        'checksumLabel' => __('Security checksum:'),
        'checksumValue' => $invoice->integrity_hash,
    ])
</body>
</html>
