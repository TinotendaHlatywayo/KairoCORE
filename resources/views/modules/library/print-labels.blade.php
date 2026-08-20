<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Print Code39 Labels') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap');
        
        @page {
            size: A4 portrait;
            margin: 15mm 10mm;
        }
        @media print {
            body {
                background: none;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            .grid-sheet {
                box-shadow: none !important;
                border: none !important;
            }
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #e5e7eb;
            margin: 0;
            padding: 20px;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-btn {
            background: #2563eb;
            color: #fff;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        .grid-sheet {
            background: #fff;
            width: 190mm;
            min-height: 260mm;
            margin: 0 auto;
            padding: 5mm;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-gap: 5mm;
            align-content: start;
        }
        .label-card {
            border: 1px dotted #ccc;
            height: 38mm;
            padding: 3mm;
            box-sizing: border-box;
            text-align: center;
            font-size: 10px;
            overflow: hidden;
            position: relative;
        }
        .title-text {
            font-weight: 700;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .author-text {
            color: #4b5563;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .barcode-rendering {
            font-family: 'Libre Barcode 39', cursive;
            font-size: 32px;
            line-height: 1;
            margin: 4px 0;
            display: block;
        }
        .readable-barcode {
            font-size: 8px;
            letter-spacing: 2px;
            display: block;
            margin-bottom: 4px;
        }
        .location-badge {
            font-size: 8px;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="print-btn" onclick="window.print()">{{ __('Execute Document Print') }}</button>
</div>

<div class="grid-sheet">
    @foreach($copies as $copy)
        <div class="label-card">
            <div class="title-text">{{ $copy->book->title }}</div>
            <div class="author-text">BY: {{ $copy->book->author->name }}</div>
            <span class="barcode-rendering">*{{ $copy->barcode }}*</span>
            <span class="readable-barcode">{{ $copy->barcode }}</span>
            <span class="location-badge">SHELF {{ $copy->shelf ?? 'N/A' }} / {{ $copy->rack ?? 'N/A' }}</span>
        </div>
    @endforeach
</div>

</body>
</html>