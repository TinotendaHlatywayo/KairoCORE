<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Bulk Statements') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($statements as $index => $statement)
        <div class="{{ $index < count($statements) - 1 ? 'page-break' : '' }}">
            @include('modules.finance.statement-pdf', $statement)
        </div>
    @endforeach
</body>
</html>
