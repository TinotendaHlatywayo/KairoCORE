<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Bulk Receipts') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($receipts as $index => $receipt)
        <div class="{{ $index < count($receipts) - 1 ? 'page-break' : '' }}">
            @include('modules.finance.receipt-pdf', $receipt)
        </div>
    @endforeach
</body>
</html>
