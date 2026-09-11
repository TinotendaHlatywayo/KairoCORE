<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Timetable - {{ $course->name }} ({{ $sections->pluck('name')->implode(', ') }})</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
        html, body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #ffffff;
        }
        .print-header {
            text-align: center;
            border-bottom: 3px double #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 19px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .school-motto {
            font-size: 9px;
            font-style: italic;
            color: #475569;
            margin-top: 1px;
            text-transform: uppercase;
        }
        .timetable-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 6px;
            color: #0f172a;
        }
        .stream-subtitle {
            font-size: 8.5px;
            color: #334155;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            background: #ffffff;
            page-break-inside: avoid;
        }
        th, td {
            border: 1.5px solid #0f172a;
            padding: 4px 4px;
            text-align: center;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .time-col {
            font-weight: bold;
            width: 82px;
            font-size: 10px;
            vertical-align: middle;
        }
        .time-col .time-range {
            font-size: 8.5px;
            color: #475569;
        }
        .stream-cell {
            text-align: left;
        }
        .stream-line {
            font-size: 7.5px;
            line-height: 1.35;
            padding: 1px 0;
            border-bottom: 1px dotted #e2e8f0;
        }
        .stream-line:last-child {
            border-bottom: none;
        }
        .stream-subject {
            font-weight: bold;
            text-transform: uppercase;
        }
        .break-row {
            font-weight: bold;
            text-transform: uppercase;
            background: #f8fafc;
            font-size: 9px;
            letter-spacing: 2px;
            text-align: center !important;
            vertical-align: middle !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .free-cell {
            color: #cbd5e1;
            font-style: italic;
            font-size: 8px;
            text-align: center;
        }
        .print-footer {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
        }
        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Printable Header block -->
    <div class="print-header">
        <div class="school-name">{{ $school->name }}</div>
        <div class="school-motto">{{ $school->motto ?? 'Education for Excellence' }}</div>
        <div class="timetable-title">Official Weekly Timetable — Stream: {{ $course->name }}</div>
        <div class="stream-subtitle">{{ $sections->map(fn ($s) => trim($course->name.' '.$s->name).($s->classTeacher ? ' (CT: '.$s->classTeacher->name.')' : ''))->implode('  •  ') }}</div>
    </div>

    <!-- The merged Stream Schedule Grid -->
    <table>
        <thead>
            <tr>
                <th>{{ __('Time Period') }}</th>
                <th>{{ __('Monday') }}</th>
                <th>{{ __('Tuesday') }}</th>
                <th>{{ __('Wednesday') }}</th>
                <th>{{ __('Thursday') }}</th>
                <th>{{ __('Friday') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($timeSlots as $slot)
                <tr>
                    <td class="time-col">
                        <div>{{ $slot->name }}</div>
                        <div class="time-range">
                            {{ date('H:i', strtotime($slot->start_time)) }} - {{ date('H:i', strtotime($slot->end_time)) }}
                        </div>
                    </td>
                    @foreach($days as $day)
                        @php
                            $key = $slot->id.'|'.$day;
                        @endphp

                        @if($slot->is_break)
                            <td class="break-row">{{ $slot->name }}</td>
                        @elseif(!empty($streamMatrix[$key]))
                            <td class="stream-cell">
                                @foreach($streamMatrix[$key] as $entry)
                                    <div class="stream-line">
                                        <span style="color:#475569;">{{ $entry['section_label'] }}</span>
                                        <span class="stream-subject">{{ $entry['subject'] }}</span>
                                        <span style="color:#334155;">({{ $entry['teacher_initials'] }})</span>
                                        @if(!empty($entry['room']))
                                            <span style="color:#94a3b8;">· {{ $entry['room'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @else
                            <td class="free-cell">{{ __('Free Slot') }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="print-footer">
        <div>Date Compiled: {{ date('d M Y') }}</div>
        <div>{{ __('School Stamp: ________________________') }}</div>
        <div>{{ __('Principal Signature: ________________________') }}</div>
    </div>

</body>
</html>