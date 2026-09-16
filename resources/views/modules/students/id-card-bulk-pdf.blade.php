<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('ID Cards Print Session') }}</title>
    <style>
        @page {
            margin: 0;
            size: 480px 300px;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .page-break { page-break-after: always; }
        .a4-table { width: 100%; border-collapse: collapse; }
        .a4-row { width: 100%; }
        .a4-cell { vertical-align: top; box-sizing: border-box; text-align: center; }

        /* PVC output: one CR80 card fills the whole page at exact CR80 dimensions. */
        .pvc-page {
            position: relative;
            width: 480px;
            height: 300px;
            max-height: 300px;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            overflow: hidden;
            page-break-inside: avoid;
            page-break-after: auto;
        }
        .pvc-page + .pvc-page {
            page-break-after: auto;
        }
        @if(($layout ?? 'pvc') !== 'a4')
            /* Anchor the card to the page canvas so DomPDF lays it out as exactly one page — out-of-flow, no extra page height from overflowing inner content. */
            .id-card {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
            }
        @endif
        @if(($layout ?? 'pvc') === 'a4')
            @page {
                size: A4;
            }
            .a4-cell {
                padding: 10px;
            }
            /* Fit the fixed CR80 px canvas into the A4 cell. */
            .id-card { transform: scale(0.96); transform-origin: top center; }
        @endif
    </style>
    @include('components.id-card-styles')
</head>
<body style="margin: 0; padding: 0;">@php
        $a4GridConfig = $a4_grid ?? '2x4';
        $cardsPerPage = match ($a4GridConfig) {
            '2x5' => 10,
            '3x3' => 9,
            '3x4' => 12,
            default => 8,
        };
        $columnsPerRow = match ($a4GridConfig) {
            '3x3', '3x4' => 3,
            default => 2,
        };
    @endphp

    @if(($layout ?? 'pvc') === 'a4')
        @php $chunks = $students->chunk($cardsPerPage); @endphp

        @foreach($chunks as $chunkIndex => $pageChunk)
            <div class="{{ $chunkIndex < count($chunks) - 1 ? 'page-break' : '' }}">
                <table class="a4-table">
                    @foreach($pageChunk->chunk($columnsPerRow) as $rowChunk)
                        <tr class="a4-row">
                            @foreach($rowChunk as $student)
                                <td class="a4-cell" style="width: {{ 100 / $columnsPerRow }}%;">
                                    @include('components.id-card-render', [
                                        'student' => $student,
                                        'template' => $selectedTemplate ?? null,
                                        'school' => $school,
                                        'cropMarks' => $crop_marks ?? false,
                                    ])
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach

    @else
        @php $chunks = $students->chunk(1); @endphp

        @foreach($chunks as $chunkIndex => $pageChunk)
            <div class="pvc-page {{ $chunkIndex < count($chunks) - 1 ? 'page-break' : '' }}">
                @include('components.id-card-render', [
                    'student' => $pageChunk->first(),
                    'template' => $selectedTemplate ?? null,
                    'school' => $school,
                    'cropMarks' => $crop_marks ?? false,
                ])
            </div>
        @endforeach
    @endif

</body>
</html>