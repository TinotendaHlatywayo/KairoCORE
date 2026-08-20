<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Timetable - {{ $section->course->name }} {{ $section->name }}</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #ffffff;
        }
        th, td {
            border: 1.5px solid #0f172a;
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
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
            width: 140px;
            font-size: 11px;
        }
        .lesson-box {
            text-align: center;
        }
        .subject-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .details-row {
            font-size: 9px;
            color: #334155;
            margin-top: 4px;
        }
        .break-row {
            font-weight: bold;
            text-transform: uppercase;
            background: #f8fafc;
            font-size: 10px;
            letter-spacing: 2px;
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
                        <div style="font-weight: bold;">{{ $slot->name }}</div>
                        <div style="font-size: 10px; color: #475569; margin-top: 2px;">
                            {{ date('H:i', strtotime($slot->start_time)) }} - {{ date('H:i', strtotime($slot->end_time)) }}
                        </div>
                    </td>
                    @foreach($days as $day)
                        @php
                            $lesson = \Modules\Timetables\Models\TimetableLesson::where('school_id', $school->id)
                                ->where('section_id', $section->id)
                                ->where('time_slot_id', $slot->id)
                                ->where('day_of_week', $day)
                                ->first();
                        @endphp
                        
                        @if($slot->is_break)
                            <td class="break-row" colspan="1">{{ $slot->name }}</td>
                        @elseif($lesson)
                            <td>
                                <div class="lesson-box">
                                    <div class="subject-name">{{ $lesson->subject->name }}</div>
                                    <div class="details-row">Teacher: {{ $lesson->teacher->name }} | Room: {{ $lesson->classroom->name }}</div>
                                </div>
                            </td>
                        @else
                            <td style="color: #cbd5e1; font-style: italic; font-size: 10px;">{{ __('Free Slot') }}</td>
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