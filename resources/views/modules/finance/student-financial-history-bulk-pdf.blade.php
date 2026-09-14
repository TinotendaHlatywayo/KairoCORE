<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Bulk Financial Histories') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($histories as $index => $history)
        <div class="{{ $index < count($histories) - 1 ? 'page-break' : '' }}">
            @include('modules.finance.student-financial-history-pdf', $history)
        </div>
    @endforeach
</body>
</html>