<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Timetable - {{ $section->course->name }} {{ $section->name }}</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            background: #ffffff;
            page-break-inside: avoid;
        }
        th, td {
            border: 1.5px solid #0f172a;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
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
        }
        .time-col .time-range {
            font-size: 8.5px;
            color: #475569;
        }
        .lesson-box {
            text-align: center;
        }
        .subject-name {
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .details-row {
            font-size: 8px;
            color: #334155;
            margin-top: 2px;
            line-height: 1.25;
        }
        .break-row {
            font-weight: bold;
            text-transform: uppercase;
            background: #f8fafc;
            font-size: 9px;
            letter-spacing: 2px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .free-cell {
            color: #cbd5e1;
            font-style: italic;
            font-size: 8.5px;
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
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Printable Header block -->
    <div class="print-header">
        <div class="school-name">{{ $school->name }}</div>
        <div class="school-motto">{{ $school->motto ?? 'Education for Excellence' }}</div>
        <div class="timetable-title">Official Weekly Timetable — Class: {{ $section->course->name }} {{ $section->name }}</div>
    </div>

    <!-- The Schedule Grid -->
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
                            $lesson = \Modules\Timetables\Models\TimetableLesson::where('school_id', $school->id)
                                ->where('template_id', $activeTemplate?->id)
                                ->where('section_id', $section->id)
                                ->where('time_slot_id', $slot->id)
                                ->where('day_of_week', $day)
                                ->first();
                        @endphp
                        
                        @if($slot->is_break)
                            <td class="break-row">{{ $slot->name }}</td>
                        @elseif($lesson)
                            <td>
                                <div class="lesson-box">
                                    <div class="subject-name">{{ $lesson->subject->name }}</div>
                                    <div class="details-row">Teacher: {{ $lesson->teacher->name }} | Room: {{ $lesson->classroom->name }}</div>
                                </div>
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