<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Timetable - {{ $course->name }} ({{ $sections->pluck('name')->implode(', ') }})</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 30px;
            background: #ffffff;
        }
        .print-header {
            text-align: center;
            border-bottom: 3px double #0f172a;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .school-motto {
            font-size: 11px;
            font-style: italic;
            color: #475569;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .timetable-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 15px;
            color: #0f172a;
        }
        .stream-subtitle {
            font-size: 10px;
            color: #334155;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #ffffff;
        }
        th, td {
            border: 1.5px solid #0f172a;
            padding: 8px 6px;
            text-align: center;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .time-col {
            font-weight: bold;
            width: 130px;
            font-size: 11px;
            vertical-align: middle;
        }
        .stream-cell {
            text-align: left;
        }
        .stream-line {
            font-size: 9px;
            line-height: 1.5;
            padding: 1px 0;
        }
        .stream-subject {
            font-weight: bold;
            text-transform: uppercase;
        }
        .break-row {
            font-weight: bold;
            text-transform: uppercase;
            background: #f8fafc;
            font-size: 10px;
            letter-spacing: 2px;
            text-align: center !important;
            vertical-align: middle !important;
        }
        .free-cell {
            color: #cbd5e1;
            font-style: italic;
            font-size: 9px;
            text-align: center;
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
                        <div style="font-weight: bold;">{{ $slot->name }}</div>
                        <div style="font-size: 10px; color: #475569; margin-top: 2px;">
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

    <div style="margin-top: 50px; display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; border-top: 1px dashed #cbd5e1; padding-top: 20px;">
        <div>Date Compiled: {{ date('d M Y') }}</div>
        <div>{{ __('School Stamp: ________________________') }}</div>
        <div>{{ __('Principal Signature: ________________________') }}</div>
    </div>

</body>
</html>