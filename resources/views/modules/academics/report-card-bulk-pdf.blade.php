<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Bulk Report Cards') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
        
        .header { text-align: center; border-bottom: 3px double #1e3a8a; padding-bottom: 8px; margin-bottom: 15px; }
        .school-name { font-size: 20px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; margin-bottom: 2px; }
        .school-motto { font-style: italic; color: #4b5563; font-size: 10px; margin-bottom: 5px; }
        .title { font-size: 13px; font-weight: bold; letter-spacing: 1px; color: #111827; }
        
        .metadata-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .metadata-table td { padding: 4px 8px; border: 1px solid #e5e7eb; }
        .label { font-weight: bold; color: #374151; width: 15%; }
        
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .results-table th { background: #1e3a8a; color: white; padding: 6px 8px; font-size: 10px; font-weight: bold; border: 1px solid #1e3a8a; text-transform: uppercase; }
        .results-table td { padding: 6px 8px; border: 1px solid #e5e7eb; text-align: center; }
        
        .competency-container { border: 1px solid #e5e7eb; background: #f9fafb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .competency-title { font-weight: bold; color: #1e3a8a; margin-bottom: 6px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
        .competency-grid { width: 100%; }
        .competency-grid td { width: 50%; padding: 3px 0; font-size: 10px; }
        
        .remarks-container { border: 1px solid #e5e7eb; padding: 8px; margin-bottom: 15px; border-radius: 4px; }
        .remarks-title { font-weight: bold; color: #374151; margin-bottom: 3px; }
        
        .footer-container { margin-top: 30px; width: 100%; height: 80px; }
        .signatures { float: left; width: 70%; margin-top: 20px; }
        .signature-line { display: inline-block; width: 45%; margin-right: 5%; border-top: 1px solid #9ca3af; text-align: center; padding-top: 3px; font-size: 10px; color: #4b5563; }
        .qr-block { float: right; width: 25%; text-align: right; }
        .qr-image { width: 70px; height: 70px; border: 1px solid #ddd; padding: 2px; }
    </style>
</head>
<body>

    @foreach($reports as $index => $data)
        <div class="{{ $index < count($reports) - 1 ? 'page-break' : '' }}">
            
            <!-- 1. School Header -->
            <div class="header">
                <div class="school-name">{{ $school->name }}</div>
                @if($school->motto)
                    <div class="school-motto">"{{ $school->motto }}"</div>
                @endif
                <div class="title">{{ __('OFFICIAL HERITAGE-BASED ACADEMIC REPORT') }}</div>
            </div>

            <!-- 2. Student Metadata -->
            <table class="metadata-table">
                <tr>
                    <td class="label">{{ __('Student:') }}</td>
                    <td>{{ $data['student']->full_name }}</td>
                    <td class="label">{{ __('Admission No:') }}</td>
                    <td>{{ $data['student']->admission_number }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Class/Form:') }}</td>
                    <td>{{ $data['report']->section?->full_name }}</td>
                    <td class="label">{{ __('Academic Term:') }}</td>
                    <td>{{ ucwords(strtolower($data['term']->name)) }} ({{ $data['year']->name }})</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Overall Score:') }}</td>
                    <td style="font-weight: bold; color: #1e3a8a;">{{ $data['report']->overall_score }} / 10.00</td>
                    <td class="label">{{ __('HOD Review:') }}</td>
                    <td style="font-weight: bold; text-transform: uppercase;">{{ $data['report']->status }}</td>
                </tr>
            </table>

            <!-- 3. Grades -->
            <table class="results-table">
                <thead>
                    <tr>
                        <th style="text-align: left; width: 40%;">{{ __('Subject Name') }}</th>
                        <th style="width: 15%;">Exam (80%)</th>
                        <th style="width: 15%;">Project (20%)</th>
                        <th style="width: 15%;">{{ __('Total Mark') }}</th>
                        <th style="width: 15%;">{{ __('Grade') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['results'] as $res)
                        <tr>
                            <td style="text-align: left; font-weight: bold; color: #374151;">{{ $res['subject'] }}</td>
                            <td>{{ $res['exam_score'] }}</td>
                            <td>{{ $res['sbp_score'] }}</td>
                            <td style="font-weight: bold; color: #1e3a8a;">{{ $res['total'] }}%</td>
                            <td style="font-weight: bold;">{{ $res['grade'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #9ca3af; padding: 15px;">{{ __('No academic examination scores recorded for this term.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 4. Unhu/Ubuntu Core Competencies -->
            @if(is_array($data['report']->unhu_competencies))
            <div class="competency-container">
                <div class="competency-title">{{ __('Unhu/Ubuntu Heritage-Based Competencies') }}</div>
                <table class="competency-grid">
                    <tr>
                        <td><strong>Respect (Kuremekedza):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['respect'] ?? 'good')) }}</td>
                        <td><strong>Integrity (Kuvimbika):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['honesty'] ?? 'good')) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Responsibility (Kuzvidavirira):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['responsibility'] ?? 'good')) }}</td>
                        <td><strong>Discipline (Kurangwa / Kuzvibata):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['discipline'] ?? 'good')) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Patriotism (Kuda Nyika):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['patriotism'] ?? 'good')) }}</td>
                        <td><strong>Cooperation (Kushandira Pamwe):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['cooperation'] ?? 'good')) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Leadership (Hutungamiri):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['leadership'] ?? 'good')) }}</td>
                        <td><strong>Critical Thinking (Kufunga Zvakadzama):</strong> {{ ucfirst(str_replace('_', ' ', $data['report']->unhu_competencies['critical_thinking'] ?? 'good')) }}</td>
                    </tr>
                </table>
            </div>
            @endif

            <!-- 5. Remarks -->
            @if($data['report']->teacher_comment)
                <div class="remarks-container">
                    <div class="remarks-title"><strong>Class Teacher's Remarks:</strong></div>
                    <div style="font-style: italic; color: #4b5563;">"{{ $data['report']->teacher_comment }}"</div>
                </div>
            @endif

            @if($data['report']->headmaster_comment)
                <div class="remarks-container">
                    <div class="remarks-title"><strong>Headmaster's Remarks:</strong></div>
                    <div style="font-style: italic; color: #4b5563;">"{{ $data['report']->headmaster_comment }}"</div>
                </div>
            @endif

            <!-- 6. Footer signatures -->
            <div class="footer-container">
                <div class="signatures">
                    <div class="signature-line">{{ __('Class Teacher Signature') }}</div>
                    <div class="signature-line">{{ __('Headmaster / Principal Stamp') }}</div>
                </div>
                <div class="qr-block">
                    <img class="qr-image" src="https://api.qrserver.com/v1/create-qr-code/?size=150x100&data={{ route('report.verify', ['hash' => $data['report']->integrity_hash]) }}">
                    <div style="font-size: 7px; color: #6b7280; margin-top: 3px; text-align: center;">{{ __('Scan to Verify') }}</div>
                </div>
            </div>

        </div>
    @endforeach

</body>
</html>