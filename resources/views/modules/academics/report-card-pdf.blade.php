<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Academic Report Cards Print Run') }}</title>
    <style>
        @page {
            size: a4 portrait;
        }
        body {
            margin: 0;
            padding: 0;
            line-height: 1.2;
            font-size: 10px;
        }
        *, *:before, *:after { box-sizing: border-box; }

        /* GENERAL CONTAINERS */
        .report-card-page {
            position: relative;
            width: 100%;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        /* =========================================================================
           5 PRINTABLE INTERFACE THEMES (Scoped to report page containers)
           ========================================================================= */
        
        /* 1. CLASSIC LINE (Traditional school layouts with dark blue rules) */
        .theme-classic_line .school-header { border-bottom: 3px double #1e3a8a; padding-bottom: 6px; text-align: center; }
        .theme-classic_line th { 
            background-color: var(--table-header-bg, #f1f5f9); 
            color: var(--header-color, #1e3a8a); 
            border: 1px solid #1e3a8a; 
        }
        .theme-classic_line td { border: 1px solid #cbd5e1; }

        /* 2. MODERN GRID (Vibrant headers and bordered metadata blocks) */
        .theme-modern_grid .school-header { 
            background: var(--header-color, #3b82f6); 
            color: white; 
            padding: 12px; 
            border-radius: 8px; 
            text-align: center; 
        }
        .theme-modern_grid th { 
            background-color: var(--header-color, #3b82f6); 
            color: white; 
            border: 1px solid #93c5fd; 
        }
        .theme-modern_grid td { border: 1px solid #e2e8f0; }

        /* 3. ELEGANT EDITORIAL (Prestigious serif typography accents) */
        .theme-elegant_editorial { font-family: 'Times New Roman', Georgia, serif !important; }
        .theme-elegant_editorial .school-header { border-bottom: 2px solid #7f1d1d; text-align: center; }
        .theme-elegant_editorial th { background-color: #7f1d1d; color: white; border: 1px solid #7f1d1d; }
        .theme-elegant_editorial td { border: 1px solid #f1f5f9; }

        /* 4. MINIMALIST COMPACT (Hairline thin lines, high-density space) */
        .theme-minimal_compact .school-header { text-align: left; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .theme-minimal_compact th { 
            background-color: var(--table-header-bg, #fafafa); 
            color: #334155; 
            border-bottom: 2px solid #e2e8f0; 
            border-top: 1px solid #e2e8f0; 
        }
        .theme-minimal_compact td { border-bottom: 1px solid #f1f5f9; }

        /* 5. ROYAL CREST (Gold-foil highlights and deep navy columns) */
        .theme-royal_crest { border: 2px solid #fbbf24; padding: 5px; }
        .theme-royal_crest .school-header { 
            background: var(--header-color, #1e3a8a); 
            color: #fbbf24; 
            padding: 10px; 
            text-align: center; 
        }
        .theme-royal_crest th { background-color: #1e3a8a; color: white; border: 1px solid #fbbf24; }
        .theme-royal_crest td { border: 1px solid #fef3c7; }

        .watermark-container { position: absolute; top: 35%; left: 15%; width: 70%; opacity: 0.04; z-index: -1000; }
        .watermark-img { width: 100%; height: auto; }
        
        .school-name { font-weight: bold; text-transform: uppercase; margin: 0; }
        .school-motto { font-size: 8px; font-style: italic; margin-top: 2px; text-transform: uppercase; }

        .metadata-table { width: 100%; border-collapse: collapse; margin-top: 12px; margin-bottom: 12px; }
        .metadata-table td { padding: 4px 6px; border: 1px solid #e2e8f0; font-size: 10px; }
        .label { font-weight: bold; background-color: #f8fafc; width: 18%; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { font-size: 9px; font-weight: bold; text-transform: uppercase; padding: 5px; text-align: center; }
        td { font-size: 9px; padding: 5px; vertical-align: middle; text-align: center; }

        .remarks-container { border: 1px solid #e2e8f0; padding: 8px; margin-bottom: 10px; border-radius: 4px; }
        .remarks-title { font-weight: bold; color: #334155; margin-bottom: 3px; font-size: 9px; text-transform: uppercase; }
        .manual-entry-line { font-family: monospace; font-size: 10px; border-bottom: 1px dotted #94a3b8; padding-bottom: 2px; }

        .footer-container { margin-top: 15px; width: 100%; height: 70px; }
        .signatures { float: left; width: 65%; margin-top: 15px; }
        .signature-line { display: inline-block; width: 45%; margin-right: 5%; border-top: 1px solid #94a3b8; text-align: center; padding-top: 3px; font-size: 9px; color: #64748b; }
        .qr-block { float: right; width: 25%; text-align: right; }
        .qr-image { width: 55px; height: 50px; border: 1px solid #e2e8f0; padding: 2px; }
        
        .security-warning { text-align: center; font-weight: bold; color: #b91c1c; font-size: 8px; margin-top: 10px; letter-spacing: 0.5px; text-transform: uppercase; }
        .theme-minimal_compact .security-warning { color: #111827; }
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

            $logoBase64 = $data['logoBase64'];
            $photoBase64 = $data['photoBase64'];
            $qrCodeBase64 = $data['qrCodeBase64'];
            $template = $data['template'];

            $marginV = $template->layout_config['page_margin_v'] ?? 12;
            $marginH = $template->layout_config['page_margin_h'] ?? 15;
            $cellPadding = $template->layout_config['table_padding'] ?? 5;
            $borderW = $template->layout_config['page_border_width'] ?? 0;
            $borderC = $template->layout_config['page_border_color'] ?? '#fbbf24';

            // Theme-aware semantic accents (minimal_compact is strictly black & white)
            $accentColor = $template->layout_config['header_color'] ?? '#1e3a8a';
            $successColor = '#16a34a';
            $dangerColor = '#b91c1c';
            if (($template->design_theme ?? 'classic_line') === 'minimal_compact') {
                $accentColor = '#111827';
                $successColor = '#111827';
                $dangerColor = '#111827';
            }

            // Active Template Columns Configuration
            $showClassAverage = $template->layout_config['show_class_average'] ?? true;
            $showStreamAverage = $template->layout_config['show_stream_average'] ?? true;
            $showSubjectRank = $template->layout_config['show_subject_position'] ?? true;
        @endphp

        <!-- Each student's report card layout wrapped inside an isolated, theme-configured, page container -->
        <div class="report-card-page theme-{{ $template->design_theme }}" 
             style="page-break-after: {{ $index < count($reportsCompiled) - 1 ? 'always' : 'avoid' }}; 
                    font-family: {{ $template->layout_config['font_family'] ?? 'Helvetica, sans-serif' }}; 
                    color: {{ $template->layout_config['body_text_color'] ?? '#1e293b' }};
                    padding: {{ $marginV }}mm {{ $marginH }}mm !important;
                    border: {{ $borderW }}px solid {{ $borderC }};
                    --header-color: {{ $template->layout_config['header_color'] ?? '#1e3a8a' }};
                    --table-header-bg: {{ $template->layout_config['table_header_bg'] ?? '#f1f5f9' }};">
            
            <style scoped>
                .theme-{{ $template->design_theme }} td, 
                .theme-{{ $template->design_theme }} th {
                    padding: {{ $cellPadding }}px !important;
                }
            </style>

            <!-- 1. Watermark -->
            @if(!empty($logoBase64))
                <div class="watermark-container">
                    <img class="watermark-img" src="{{ $logoBase64 }}">
                </div>
            @endif

            <!-- 2. Institutional Header -->
            <div class="school-header">
                <table style="width: 100%; border: none; margin-bottom: 0;">
                    <tr style="border: none;">
                        @if($template->layout_config['show_school_logo'] && !empty($logoBase64))
                            <td style="width: 60px; text-align: left; border: none; padding: 0;">
                                <img src="{{ $logoBase64 }}" style="width: 50px; height: 50px;">
                            </td>
                        @endif
                        <td style="text-align: center; border: none; padding: 0;">
                            <div class="school-name" style="font-size: {{ $template->layout_config['header_font_size'] ?? 18 }}px; color: {{ $accentColor }}">{{ $school->name }}</div>
                            @if($template->layout_config['show_school_motto'] && $school->motto)
                                <div class="school-motto">"{{ $school->motto }}"</div>
                            @endif
                            <div style="font-size: 8px; color: #64748b; margin-top: 2px;">
                                @if($template->layout_config['show_address'] && $school->physical_address) Address: {{ $school->physical_address }} | @endif
                                @if($template->layout_config['show_phone'] && $school->phone_number) Tel: {{ $school->phone_number }} @endif
                                @if($template->layout_config['show_email'] && $school->email_address) | Email: {{ $school->email_address }} @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 3. Student Metadata & Photo Grid -->
            <table class="metadata-table">
                <tr>
                    @if($template->layout_config['show_student_photo'] && !empty($photoBase64))
                        <td rowspan="3" style="width: 70px; text-align: center; padding: 2px;">
                            <img src="{{ $photoBase64 }}" style="width: 60px; height: 60px; border-radius: 4px; object-fit: cover; border: 1px solid #e2e8f0;">
                        </td>
                    @endif
                    <td class="label">{{ __('Student Name:') }}</td>
                    <td><strong>{{ $student?->full_name ?? 'Deleted Student' }}</strong></td>
                    <td class="label">{{ __('Admission No:') }}</td>
                    <td style="font-family: monospace;">{{ $student?->admission_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Class / Form:') }}</td>
                    <td>{{ $report->section?->full_name ?? 'N/A' }}</td>
                    <td class="label">{{ __('Academic Period:') }}</td>
                    <td>{{ ucwords(strtolower($term->name)) }} ({{ $year->name }})</td>
                </tr>
                <tr>
                    <td class="label">
                        @if($template->layout_config['show_class_position'] ?? true)
                            Class Rank:
                        @elseif($template->layout_config['show_stream_position'] ?? true)
                            Stream Rank:
                        @else
                            HBC Score:
                        @endif
                    </td>
                    <td style="font-weight: bold; color: {{ $accentColor }};">
                        @if($template->layout_config['show_class_position'] ?? true)
                            @php $classOrd = match($classRank) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' }; @endphp
                            {{ $classRank ? $classRank . $classOrd . ' out of ' . $classTotal : 'N/A' }}
                        @elseif($template->layout_config['show_stream_position'] ?? true)
                            @php $streamOrd = match($streamRank) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' }; @endphp
                            {{ $streamRank ? $streamRank . $streamOrd . ' out of ' . $streamTotal : 'N/A' }}
                        @else
                            {{ $report->overall_score }} / 10.00
                        @endif
                    </td>
                    <td class="label">{{ __('Student ID:') }}</td>
                    <td style="font-family: monospace;">{{ $student?->student_id_number ?? 'N/A' }}</td>
                </tr>
            </table>

            <!-- 4. ACADEMIC PERFORMANCE TABLES (CONSOLIDATED GRADINGS GRID) -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 12%;">{{ __('Code') }}</th>
                        <th style="text-align: left; width: 28%;">{{ __('Subject Name') }}</th>
                        <th style="width: 15%;">Subject Final Mark (%)</th>
                        @if($showClassAverage) <th style="width: 12%;">{{ __('Class Avg') }}</th> @endif
                        @if($showStreamAverage) <th style="width: 12%;">{{ __('Strm Avg') }}</th> @endif
                        @if($showSubjectRank) <th style="width: 10%;">{{ __('Rank') }}</th> @endif
                        <th style="width: 11%;">{{ __('Initials') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($compiledSubjects as $subjectData)
                        <tr>
                            <td style="font-family: monospace; font-weight: bold;">{{ $subjectData['subject_code'] }}</td>
                            <td style="text-align: left; font-weight: bold;">{{ $subjectData['subject_name'] }}</td>
                            <td style="font-weight: bold; color: {{ $accentColor }};">
                                {{ !is_null($subjectData['final_mark']) ? $subjectData['final_mark'] . '%' : '-' }}
                            </td>
                            @if($showClassAverage)
                                <td style="color: #64748b;">{{ !is_null($subjectData['class_avg']) ? round($subjectData['class_avg'], 1).'%' : '-' }}</td>
                            @endif
                            @if($showStreamAverage)
                                <td style="color: #64748b;">{{ !is_null($subjectData['stream_avg']) ? round($subjectData['stream_avg'], 1).'%' : '-' }}</td>
                            @endif
                            @if($showSubjectRank)
                                <td>
                                    @php $sOrd = match($subjectData['subject_rank']) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' }; @endphp
                                    {{ $subjectData['subject_rank'] ? $subjectData['subject_rank'] . $sOrd : '-' }}
                                </td>
                            @endif
                            <td>{{ $subjectData['initials'] ?? 'TR' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 4 + ($showClassAverage ? 1 : 0) + ($showStreamAverage ? 1 : 0) + ($showSubjectRank ? 1 : 0) }}">{{ __('No academic scores recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

            <!-- ECD/Primary Co-Curricular Competencies Table -->
            @if($level === 'primary' || $level === 'ecd')
                @if($template->layout_config['show_ubuntu_competencies'] && count($competencies) > 0)
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
            @endif

            <!-- 5. Outstanding Achievements Section -->
            @if(($template->layout_config['show_outstanding_achievements'] ?? true) && count($achievements) > 0)
                <div style="font-weight: bold; font-size: 9px; margin-bottom: 4px; text-transform: uppercase; color: {{ $accentColor }};">{{ __('Outstanding Achievements') }}</div>
                <div class="remarks-container" style="color: {{ $successColor }}; font-style: italic; font-size: 8px; line-height: 1.3;">
                    @foreach($achievements as $ach)
                        ★ {{ $ach }}<br/>
                    @endforeach
                </div>
            @endif

            <!-- 6. Compact Unhu/Ubuntu Skills Table -->
            @if(($template->layout_config['show_ubuntu_competencies'] ?? true) && count($unhuCompiled) > 0)
                <div style="font-weight: bold; font-size: 9px; margin-bottom: 4px; text-transform: uppercase; color: {{ $accentColor }};">{{ __('Unhu / Ubuntu Heritage Competencies') }}</div>
                
                @if(($template->layout_config['show_ubuntu_percentage'] ?? true) && !is_null($overallUnhuPercentage))
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

            <!-- 7. Comments Block -->
            <div class="remarks-container">
                <div class="remarks-title">Class Teacher's Remark:</div>
                <div class="manual-entry-line">"{{ $report->teacher_comment ?? 'A very consistent and hardworking student.' }}"</div>
            </div>

            <div class="remarks-container">
                <div class="remarks-title">Headmaster's Remark:</div>
                <div class="manual-entry-line">"{{ $report->headmaster_comment ?? 'Excellent results. Keep up the high standard.' }}"</div>
            </div>

            <!-- 8. Fees, Schedule, Requirements & Announcements Block -->
            @if($template->layout_config['show_next_term_fees'] ?? true)
                @php
                    $nextTermBegins = $template->layout_config['next_term_begins'] ? date('d-M-Y', strtotime($template->layout_config['next_term_begins'])) : date('d-M-Y', strtotime('+1 month'));
                    $nextTermEnds = $template->layout_config['next_term_ends'] ? date('d-M-Y', strtotime($template->layout_config['next_term_ends'])) : date('d-M-Y', strtotime('+4 months'));
                    $nextTermFees = $template->layout_config['next_term_fees'] ?? '$800.00 USD';
                    $requirementsText = $template->layout_config['requirements'] ?? '1 Ream of Paper, 4 Rolls of Toilet Paper';
                    $specialAnnouncements = $template->layout_config['special_announcements'] ?? '';
                @endphp
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <tr>
                        <td style="width: 50%; padding: 6px; border: 1px solid #cbd5e1; text-align: left; line-height: 1.4; font-size: 8px;">
                            <strong>{{ __('Next Term Schedule:') }}</strong><br/>
                            Term Begins: {{ $nextTermBegins }}<br/>
                            Term Ends: {{ $nextTermEnds }}
                        </td>
                        <td style="width: 50%; padding: 6px; border: 1px solid #cbd5e1; text-align: left; line-height: 1.4; font-size: 8px;">
                            <strong>{{ __('Next Term Fees Due:') }}</strong><br/>
                            Base Tuition: {{ $nextTermFees }}<br/>
                            Requirements: {{ $requirementsText }}
                        </td>
                    </tr>
                    @if(!empty($specialAnnouncements))
                        <tr>
                            <td colspan="2" style="padding: 6px; border: 1px solid #cbd5e1; text-align: left; line-height: 1.4; font-size: 8px; color: {{ $dangerColor }};">
                                <strong>{{ __('Special Announcements:') }}</strong><br/>
                                {{ $specialAnnouncements }}
                            </td>
                        </tr>
                    @endif
                </table>
            @endif

            <!-- 9. Signatures & QR Security Block -->
            <div class="footer-container">
                <div class="signatures">
                    <div class="signature-line">{{ __('Class Teacher Signature') }}</div>
                    <div class="signature-line">{{ __('Headmaster / Principal Stamp') }}</div>
                </div>
                <div class="qr-block">
                    <!-- Renders the Base64 pre-compiled offline verification QR Code stream reliably -->
                    @if(!empty($qrCodeBase64))
                        <img class="qr-image" src="{{ $qrCodeBase64 }}">
                    @else
                        <img class="qr-image" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=jpg&data={{ urlencode(route('report.verify', ['hash' => $report->integrity_hash])) }}">
                    @endif
                    <div style="font-size: 6px; color: #94a3b8; margin-top: 2px;">{{ __('Scan to Verify') }}</div>
                </div>
            </div>

            <!-- 10. Security Stamp Warning -->
            <div class="security-warning">
                {{ __('⚠️ This report card is invalid without a valid school seal or official stamp ⚠️') }}
            </div>

        </div>
    @endforeach

</body>
</html>