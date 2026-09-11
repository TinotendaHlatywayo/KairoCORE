<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Academic Report Cards Print Run') }}</title>
    <style>
        @page {
            size: a4;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            line-height: 1.15;
            font-size: 8.5px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        *, *:before, *:after { box-sizing: border-box; }

        .report-card-page {
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* THEMES */
        .theme-classic_line .school-header { border-bottom: 3px double #1e3a8a; padding-bottom: 6px; text-align: center; }
        .theme-classic_line th { background-color: var(--table-header-bg, #f1f5f9); color: var(--header-color, #1e3a8a); border: 1px solid #1e3a8a; }
        .theme-classic_line td { border: 1px solid #cbd5e1; }

        .theme-modern_grid .school-header { background: var(--header-color, #3b82f6); color: white; padding: 12px; border-radius: 8px; text-align: center; }
        .theme-modern_grid th { background-color: var(--header-color, #3b82f6); color: white; border: 1px solid #93c5fd; }
        .theme-modern_grid td { border: 1px solid #e2e8f0; }

        .theme-elegant_editorial { font-family: 'Times New Roman', Georgia, serif !important; }
        .theme-elegant_editorial .school-header { border-bottom: 2px solid #7f1d1d; text-align: center; }
        .theme-elegant_editorial th { background-color: #7f1d1d; color: white; border: 1px solid #7f1d1d; }
        .theme-elegant_editorial td { border: 1px solid #f1f5f9; }

        .theme-minimal_compact .school-header { text-align: left; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .theme-minimal_compact th { background-color: var(--table-header-bg, #fafafa); color: #334155; border-bottom: 2px solid #e2e8f0; border-top: 1px solid #e2e8f0; }
        .theme-minimal_compact td { border-bottom: 1px solid #f1f5f9; }

        .theme-royal_crest { border: 2px solid #fbbf24; padding: 5px; }
        .theme-royal_crest .school-header { background: var(--header-color, #1e3a8a); color: #fbbf24; padding: 10px; text-align: center; }
        .theme-royal_crest th { background-color: #1e3a8a; color: white; border: 1px solid #fbbf24; }
        .theme-royal_crest td { border: 1px solid #fef3c7; }

        .watermark-container { position: absolute; top: 35%; left: 15%; width: 70%; opacity: 0.04; z-index: -1000; }
        .watermark-img { width: 100%; height: auto; }
        
        .school-name { font-weight: bold; text-transform: uppercase; margin: 0; }
        .school-motto { font-size: 8px; font-style: italic; margin-top: 2px; text-transform: uppercase; }

        .metadata-table { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 8px; }
        .metadata-table td { padding: 3px 6px; border: 1px solid #e2e8f0; font-size: 9px; }
        .label { font-weight: bold; background-color: #f8fafc; width: 16%; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { font-size: 9px; font-weight: bold; text-transform: uppercase; padding: 4px; text-align: center; }
        td { font-size: 9px; padding: 4px; vertical-align: middle; text-align: center; }

        .academic-table { table-layout: fixed; word-wrap: break-word; }
        .academic-table th, .academic-table td { overflow-wrap: break-word; word-wrap: break-word; }

        .remarks-container { border: 1px solid #e2e8f0; padding: 6px; margin-bottom: 8px; border-radius: 4px; }
        .remarks-title { font-weight: bold; color: #334155; margin-bottom: 2px; font-size: 9px; text-transform: uppercase; }
        .manual-entry-line { font-family: monospace; font-size: 10px; border-bottom: 1px dotted #94a3b8; padding-bottom: 2px; }

        .footer-container { margin-top: 10px; width: 100%; }
        .signature-line { display: inline-block; width: 45%; box-sizing: border-box; border-top: 1px solid #94a3b8; text-align: center; padding-top: 4px; font-size: 9px; color: #64748b; }
        .signature-spacer { display: inline-block; width: 10%; }
        .qr-image { width: 48px; height: 48px; border: 1px solid #e2e8f0; padding: 2px; }
        .qr-verify-text { font-size: 6px; color: #94a3b8; margin-top: 2px; }

        .security-warning { text-align: center; font-weight: bold; color: #b91c1c; font-size: 8px; margin-top: 8px; letter-spacing: 0.5px; text-transform: uppercase; }
    </style>
</head>
<body>

    @foreach($reportsCompiled as $index => $data)
        @php
            $report = $data['report'];
            $student = $data['student'];
            $term = $data['term'];
            $year = $data['year'];
            $course = $data['course'];
            $level = $data['level'];
            $compiledSubjects = $data['compiledSubjects'];
            $competencies = $data['competencies'];
            $unhuCompiled = $data['unhuCompiled'];
            $overallUnhuPercentage = $data['overallUnhuPercentage'];
            $achievements = $data['achievements'];
            
            $classRank = $data['classRank'];
            $classTotal = $data['classTotal'];
            $streamRank = $data['streamRank'];
            $streamTotal = $data['streamTotal'];
            $levelRank = $data['levelRank'];
            $levelTotal = $data['levelTotal'];

            $logoBase64 = $data['logoBase64'];
            $photoBase64 = $data['photoBase64'];
            $qrCodeBase64 = $data['qrCodeBase64'];
            $template = $data['template'];

            $marginV = $template->layout_config['page_margin_v'] ?? 12;
            $marginH = $template->layout_config['page_margin_h'] ?? 15;
            $cellPadding = $template->layout_config['table_padding'] ?? 5;
            $borderW = $template->layout_config['page_border_width'] ?? 0;
            $borderC = $template->layout_config['page_border_color'] ?? '#fbbf24';
            $lineSpacing = $template->layout_config['line_spacing'] ?? 1.2;

            $accentColor = $template->layout_config['header_color'] ?? '#1e3a8a';
            $successColor = '#16a34a';
            $dangerColor = '#b91c1c';
            if (($template->design_theme ?? 'classic_line') === 'minimal_compact') {
                $accentColor = '#111827';
                $successColor = '#111827';
                $dangerColor = '#111827';
            }

            $cfg = $template->layout_config;
            $showClassAvg = $cfg['show_class_average'] ?? true;
            $showStreamAvg = $cfg['show_stream_average'] ?? true;
            $showSubjectRank = $cfg['show_subject_position'] ?? true;
            $showOverallMark = $cfg['show_overall_subject_mark'] ?? true;
            $showGrade = $cfg['show_grade'] ?? true;
            $showClassPos = $cfg['show_class_position'] ?? true;
            $showStreamPos = $cfg['show_stream_position'] ?? true;
            $rankingScope = $cfg['ranking_scope'] ?? 'class';
            $showGradingKeys = $cfg['show_grading_keys'] ?? true;

            $showQrVerification = $cfg['show_qr_verification'] ?? true;
            $showClassRemarks = $cfg['show_class_teacher_remarks'] ?? true;
            $showClassSignature = $cfg['show_class_teacher_signature'] ?? true;
            $showPrincipalRemarks = $cfg['show_principal_remarks'] ?? true;
            $showHeadmasterStamp = $cfg['show_headmaster_stamp'] ?? true;
            $showSubjectRemarks = $cfg['show_subject_teacher_remarks'] ?? false;

            $isLandscape = ($cfg['page_orientation'] ?? 'landscape') === 'landscape';
            $acadFontPx = $isLandscape ? 8 : 6.25;

            // Card boxes are full-page fixed-size A4 (box-sizing: border-box)
            // with the template margins applied as inner padding. With an
            // @page margin of 0 the card exactly fills the printable area so
            // nothing ever bleeds off the right or bottom edge, regardless of
            // portrait/landscape orientation.
            $pageWidthMm = $isLandscape ? 297 : 210;
            $pageHeightMm = $isLandscape ? 210 : 297;

            $includedAssessmentIds = $cfg['included_assessments'] ?? [];
            $assessmentTypes = \Modules\Academics\Models\AssessmentType::whereIn('id', $includedAssessmentIds)->get();

            $emptyColspan = 2 + count($assessmentTypes)
                + (int) $showOverallMark + (int) $showGrade
                + (int) $showClassAvg + (int) $showStreamAvg
                + (int) $showSubjectRank + (int) $showSubjectRemarks + 1;
        @endphp

        <div class="report-card-page theme-{{ $template->design_theme }}" 
             style="page-break-after: {{ $index < count($reportsCompiled) - 1 ? 'always' : 'auto' }}; 
                    font-family: {{ $cfg['font_family'] ?? 'Helvetica, sans-serif' }}; 
                    color: {{ $cfg['body_text_color'] ?? '#1e293b' }};
                    line-height: {{ $lineSpacing }};
                    box-sizing: border-box;
                    width: {{ $pageWidthMm }}mm;
                    height: {{ $pageHeightMm }}mm;
                    padding: {{ $marginV }}mm {{ $marginH }}mm;
                    margin: 0;
                    border: {{ $borderW }}px solid {{ $borderC }};
                    --header-color: {{ $accentColor }};
                    --table-header-bg: {{ $cfg['table_header_bg'] ?? '#f1f5f9' }};">
            
            <style scoped>
                .theme-{{ $template->design_theme }} td, 
                .theme-{{ $template->design_theme }} th {
                    padding: {{ $cellPadding }}px !important;
                }
                .academic-table th,
                .academic-table td {
                    font-size: {{ $acadFontPx }}px !important;
                    padding: {{ $cellPadding }}px !important;
                }
            </style>

            <!-- Watermark -->
            @if(!empty($logoBase64))
                <div class="watermark-container">
                    <img class="watermark-img" src="{{ $logoBase64 }}">
                </div>
            @endif

            <!-- Institutional Header (No Emojis, Actual School Logo / Fallback) -->
            <div class="school-header">
                <table style="width: 100%; border: none; margin-bottom: 0;">
                    <tr style="border: none;">
                        @if(($cfg['show_school_logo'] ?? true) && !empty($logoBase64))
                            <td style="width: 70px; text-align: left; border: none; padding: 0;">
                                <img src="{{ $logoBase64 }}" style="width: {{ $cfg['logo_width'] ?? 55 }}px; height: auto; object-fit: contain;">
                            </td>
                        @endif
                        <td style="text-align: center; border: none; padding: 0;">
                            <div class="school-name" style="font-size: {{ $cfg['header_font_size'] ?? 18 }}px; color: {{ $accentColor }}">{{ $school->name }}</div>
                            @if(($cfg['show_school_motto'] ?? true) && $school->motto)
                                <div class="school-motto">"{{ $school->motto }}"</div>
                            @endif
                            <div style="font-size: 8px; color: #64748b; margin-top: 2px;">
                                @if(($cfg['show_address'] ?? true) && $school->physical_address) Address: {{ $school->physical_address }} | @endif
                                @if(($cfg['show_phone'] ?? true) && $school->phone_number) Tel: {{ $school->phone_number }} @endif
                                @if(($cfg['show_email'] ?? true) && $school->email_address) | Email: {{ $school->email_address }} @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Student Metadata & Photo Grid (ID-card style) -->
            <table class="metadata-table">
                <tr>
                    @if(($cfg['show_student_photo'] ?? true) && !empty($photoBase64))
                        <td rowspan="5" style="width: 64px; text-align: center; padding: 2px;">
                            <img src="{{ $photoBase64 }}" style="width: 52px; height: 60px; border-radius: 4px; object-fit: cover; border: 1px solid #e2e8f0;">
                        </td>
                    @endif
                    <td class="label">{{ __('Student Name:') }}</td>
                    <td><strong>{{ $student?->full_name ?? 'Deleted Student' }}</strong></td>
                    <td class="label">{{ __('Student ID:') }}</td>
                    <td style="font-family: monospace;">{{ $student?->student_id_number ?: $student?->admission_number ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Admission No:') }}</td>
                    <td style="font-family: monospace;">{{ $student?->admission_number ?? 'N/A' }}</td>
                    <td class="label">{{ __('Class / Form:') }}</td>
                    <td>{{ $report->section?->full_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Date of Birth:') }}</td>
                    <td>{{ $student?->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/A' }}</td>
                    <td class="label">{{ __('Gender:') }}</td>
                    <td>{{ $student?->gender ? ucfirst($student->gender) : 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('National ID:') }}</td>
                    <td style="font-family: monospace;">{{ $student?->national_id ?: 'N/A' }}</td>
                    <td class="label">{{ __('Boarding:') }}</td>
                    <td>{{ $student?->boarding_status ? ucwords(str_replace('_', ' ', $student->boarding_status)) : 'Day Scholar' }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Academic Period:') }}</td>
                    <td>{{ ucwords(strtolower($term->name)) }} ({{ $year->name }})</td>
                    <td class="label">{{ __('Rankings & Standing:') }}</td>
                    <td style="font-weight: bold; color: {{ $accentColor }};">
                        @php
                            $rankingParts = [];
                            if ($showClassPos) {
                                $cOrd = match($classRank) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
                                $rankingParts[] = 'Position: ' . ($classRank ? $classRank . $cOrd . ' of ' . $classTotal : 'N/A');
                            }
                            if ($showStreamPos) {
                                $sOrd = match($streamRank) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
                                $rankingParts[] = 'Stream Position: ' . ($streamRank ? $streamRank . $sOrd . ' of ' . $streamTotal : 'N/A');
                            }
                        @endphp
                        {{ count($rankingParts) > 0 ? implode(' | ', $rankingParts) : 'Overall Score: ' . $report->overall_score }}
                    </td>
                </tr>
            </table>

            <!-- ACADEMIC PERFORMANCE TABLE -->
            <table class="academic-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">{{ __('Code') }}</th>
                        <th style="text-align: left; width: 20%;">{{ __('Subject Name') }}</th>
                        @foreach($assessmentTypes as $assType)
                            <th>{{ $assType->name }}</th>
                        @endforeach
                        @if($showOverallMark) <th>{{ __('Overall Mark') }}</th> @endif
                        @if($showGrade) <th>{{ __('Grade') }}</th> @endif
                        @if($showClassAvg) <th>{{ __('Class Avg') }}</th> @endif
                        @if($showStreamAvg) <th>{{ __('Stream Avg') }}</th> @endif
                        @if($showSubjectRank) <th>{{ __('Rank') }}</th> @endif
                        @if($showSubjectRemarks) <th style="text-align: left;">{{ __('Subject Remark') }}</th> @endif
                        <th style="width: 6%;">{{ __('Init') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($compiledSubjects as $subjectData)
                        <tr>
                            <td style="font-family: monospace; font-weight: bold;">{{ $subjectData['subject_code'] }}</td>
                            <td style="text-align: left; font-weight: bold;">{{ $subjectData['subject_name'] }}</td>
                            @foreach($assessmentTypes as $assType)
                                <td>{{ $subjectData['assessment_marks'][$assType->id] ?? '-' }}</td>
                            @endforeach
                            @if($showOverallMark)
                                <td style="font-weight: bold; color: {{ $accentColor }};">
                                    {{ !is_null($subjectData['final_mark']) ? $subjectData['final_mark'] . '%' : '-' }}
                                </td>
                            @endif
                            @if($showGrade)
                                <td style="font-weight: bold;">{{ $subjectData['grade'] }}</td>
                            @endif
                            @if($showClassAvg)
                                <td style="color: #64748b;">{{ !is_null($subjectData['class_avg']) ? round($subjectData['class_avg'], 1).'%' : '-' }}</td>
                            @endif
                            @if($showStreamAvg)
                                <td style="color: #64748b;">{{ !is_null($subjectData['stream_avg']) ? round($subjectData['stream_avg'], 1).'%' : '-' }}</td>
                            @endif
                            @if($showSubjectRank)
                                <td>
                                    @php $subOrd = match($subjectData['subject_rank']) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' }; @endphp
                                    {{ $subjectData['subject_rank'] ? $subjectData['subject_rank'] . $subOrd : '-' }}
                                </td>
                            @endif
                            @if($showSubjectRemarks)
                                <td style="text-align: left; font-style: italic; font-size: {{ $isLandscape ? 7 : 5.5 }}px; color: #334155;">
                                    {{ $subjectData['subject_remark'] ?? '-' }}
                                </td>
                            @endif
                            <td>{{ $subjectData['initials'] ?? 'TR' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $emptyColspan }}">{{ __('No academic scores recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Primary/ECD Competencies Table -->
            @if(($level === 'primary' || $level === 'ecd') && ($cfg['show_ubuntu_competencies'] ?? true) && count($competencies) > 0)
                <div style="font-weight: bold; font-size: 9px; margin-bottom: 4px; text-transform: uppercase; color: {{ $accentColor }};">{{ __('Practical Skills & Competencies') }}</div>
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left; width: 40%;">{{ __('Skill / Competency Area') }}</th>
                            <th style="width: 20%;">Score (Out of 10)</th>
                            <th style="text-align: left; width: 40%;">{{ __('Descriptive Progress Remark') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($competencies as $comp)
                            <tr>
                                <td style="text-align: left; font-weight: bold;">{{ $comp->skill_area }}</td>
                                <td style="font-weight: bold; color: {{ $successColor }};">{{ $comp->score }} / 10.0</td>
                                <td style="text-align: left; font-style: italic;">{{ $comp->remark }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <!-- Outstanding Achievements -->
            @if(($cfg['show_outstanding_achievements'] ?? true) && count($achievements) > 0)
                <div style="font-weight: bold; font-size: 9px; margin-bottom: 4px; text-transform: uppercase; color: {{ $accentColor }};">{{ __('Outstanding Achievements') }}</div>
                <div class="remarks-container" style="color: {{ $successColor }}; font-style: italic; font-size: 8px; line-height: 1.3;">
                    @foreach($achievements as $ach)
                        ★ {{ $ach }}<br/>
                    @endforeach
                </div>
            @endif

            <!-- Unhu / Ubuntu Heritage Competencies -->
            @if(($cfg['show_ubuntu_competencies'] ?? true) && count($unhuCompiled) > 0)
                <div style="font-weight: bold; font-size: 9px; margin-bottom: 4px; text-transform: uppercase; color: {{ $accentColor }};">{{ __('Unhu / Ubuntu Heritage Competencies') }}</div>
                
                @if(($cfg['show_ubuntu_percentage'] ?? true) && !is_null($overallUnhuPercentage))
                    <div style="font-size: 8px; font-weight: bold; margin-bottom: 4px; color: {{ $accentColor }};">
                        Overall Unhu Rating Average: {{ $overallUnhuPercentage }}%
                    </div>
                @endif

                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left; width: 50%;">{{ __('Civic Core Competency') }}</th>
                            <th style="width: 50%;">{{ __('Ubuntu Rating Level') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unhuCompiled as $unhu)
                            <tr>
                                <td style="text-align: left; font-weight: bold;">{{ $unhu['trait'] }}</td>
                                <td><span style="font-weight: bold;">{{ $unhu['rating'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <!-- Comments Block -->
            @if($showClassRemarks)
                <div class="remarks-container">
                    <div class="remarks-title">Class Teacher's Remark:</div>
                    <div class="manual-entry-line">"{{ $report->teacher_comment ?? 'A very consistent and hardworking student.' }}"</div>
                </div>
            @endif

            @if($showPrincipalRemarks)
                <div class="remarks-container">
                    <div class="remarks-title">Principal's Remark:</div>
                    <div class="manual-entry-line">"{{ $report->headmaster_comment ?? 'Excellent results. Keep up the high standard.' }}"</div>
                </div>
            @endif

            <!-- Next Term Schedule & Fees -->
            @if($cfg['show_next_term_fees'] ?? true)
                @php
                    $nextTermBegins = $cfg['next_term_begins'] ? date('d-M-Y', strtotime($cfg['next_term_begins'])) : date('d-M-Y', strtotime('+1 month'));
                    $nextTermEnds = $cfg['next_term_ends'] ? date('d-M-Y', strtotime($cfg['next_term_ends'])) : date('d-M-Y', strtotime('+4 months'));
                    $nextTermFees = $cfg['next_term_fees'] ?? '$800.00 USD';
                    $requirementsText = $cfg['requirements'] ?? '1 Ream of Paper, 4 Rolls of Toilet Paper';
                    $specialAnnouncements = $cfg['special_announcements'] ?? '';
                @endphp
                <table style="width: 100%; border-collapse: collapse; margin-top: 6px;">
                    <tr>
                        <td style="width: 50%; padding: 5px; border: 1px solid #cbd5e1; text-align: left; line-height: 1.3; font-size: 8px;">
                            <strong>{{ __('Next Term Schedule:') }}</strong><br/>
                            Term Begins: {{ $nextTermBegins }}<br/>
                            Term Ends: {{ $nextTermEnds }}
                        </td>
                        <td style="width: 50%; padding: 5px; border: 1px solid #cbd5e1; text-align: left; line-height: 1.3; font-size: 8px;">
                            <strong>{{ __('Next Term Fees Due:') }}</strong><br/>
                            Base Tuition: {{ $nextTermFees }}<br/>
                            Requirements: {{ $requirementsText }}
                        </td>
                    </tr>
                    @if(!empty($specialAnnouncements))
                        <tr>
                            <td colspan="2" style="padding: 5px; border: 1px solid #cbd5e1; text-align: left; line-height: 1.3; font-size: 8px; color: {{ $dangerColor }};">
                                <strong>{{ __('Special Announcements:') }}</strong><br/>
                                {{ $specialAnnouncements }}
                            </td>
                        </tr>
                    @endif
                </table>
            @endif

            <!-- Signatures & QR Code -->
            <div class="footer-container">
                <table style="width: 100%; border: none; margin-bottom: 0;">
                    <tr style="border: none;">
                        <td style="width: 62%; border: none; vertical-align: bottom; text-align: left;">
                            @if($showClassSignature)
                                <span class="signature-line">{{ __('Class Teacher Signature') }}</span>
                            @endif
                            @if($showClassSignature && $showHeadmasterStamp)
                                <span class="signature-spacer"></span>
                            @endif
                            @if($showHeadmasterStamp)
                                <span class="signature-line">{{ __('Headmaster / Principal Stamp') }}</span>
                            @endif
                        </td>
                        <td style="width: 38%; border: none; text-align: right; vertical-align: bottom;">
                            @if($showQrVerification)
                                <div style="display: inline-block; text-align: center; max-width: 150px;">
                                    @if(!empty($qrCodeBase64))
                                        <img class="qr-image" src="{{ $qrCodeBase64 }}">
                                    @else
                                        <img class="qr-image" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=jpg&data={{ urlencode(route('report.verify', ['hash' => $report->integrity_hash, 'tenant' => $school?->subdomain])) }}">
                                    @endif
                                    <div class="qr-verify-text">{{ __('Scan to Verify') }}</div>
                                    <div class="qr-verify-text" style="word-wrap: break-word; overflow-wrap: break-word; font-size: 5px; line-height: 1.3;">
                                        {{ route('report.verify', ['hash' => $report->integrity_hash, 'tenant' => $school?->subdomain]) }}
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Grading Scales Key in Footer -->
            @if($showGradingKeys)
                @php
                    $gradingScale = (isset($school->gradingScale) && !empty($school->gradingScale)) ? $school->gradingScale : [
                        'A+' => '90-100', 'A' => '80-89', 'B' => '70-79',
                        'C' => '60-69', 'D' => '50-59', 'F' => '0-49',
                    ];
                @endphp
                <div style="margin-top: 8px; padding-top: 4px; border-top: 1px dashed {{ $accentColor }}; font-size: 7px; color: {{ $cfg['body_text_color'] ?? '#1e293b' }};">
                    <strong style="color: {{ $accentColor }};">{{ __('Grading Scale Key') }}:</strong>
                    @foreach($gradingScale as $grade => $range)
                        <span style="display:inline-block; margin-right:10px;"><strong style="color: {{ $accentColor }};">{{ $grade }}</strong> {{ $range }}</span>
                    @endforeach
                </div>
            @endif

            <!-- Security Stamp Warning -->
            <div class="security-warning">
                {{ __('⚠️ This report card is invalid without a valid school seal or official stamp ⚠️') }}
            </div>

        </div>
    @endforeach

</body>
</html>
