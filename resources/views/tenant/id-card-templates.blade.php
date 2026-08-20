<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Card Compiler - {{ $school->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&family=Inter:wght@400;600;700;800;900&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #0f172a;
            color: #f1f5f9;
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }

        /* -----------------------------------------
           PRINT STYLESHEET (CR80 Standard Bleed)
           ----------------------------------------- */
        @media print {
            body { background: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .print-container {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .id-card-render {
                page-break-inside: avoid;
                page-break-after: always;
                box-shadow: none !important;
                border: none !important;
                margin: 0 auto 20px auto !important;
            }
        }

        /* Layout Grid Controls */
        .zoom-100 { transform: scale(1.0); }
        .zoom-90 { transform: scale(0.9); }
        .zoom-80 { transform: scale(0.8); }

        .id-card-render {
            width: 325px;
            height: 485px;
            border-radius: 16px;
            position: relative;
            background: #ffffff;
            color: #1e293b;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: inline-block;
            box-sizing: border-box;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }

        .card-inner {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Universal Field Styles */
        .u-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            margin: 0 auto;
        }
        .u-barcode {
            font-family: 'Libre Barcode 39', monospace;
            font-size: 34px;
            line-height: 1;
            margin-top: 2px;
        }

        /* ---------------------------------------------------------
           10 PREMIUM COMMERICAL STYLES (Vertical/Portrait Bases)
           --------------------------------------------------------- */

        /* STYLE 1: MODERN EMERALD */
        .style-emerald .h-block { background: linear-gradient(135deg, #047857 0%, #064e3b 100%); color: white; padding: 25px 15px 45px 15px; }
        .style-emerald .avatar-wrap { margin-top: -45px; }
        .style-emerald .student-name { font-size: 18px; font-weight: 800; color: #1e293b; margin-top: 10px; }
        .style-emerald .tag { color: #047857; font-weight: bold; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }

        /* STYLE 2: GREEN ACADEMIC */
        .style-academic .h-block { background: #15803d; color: white; padding: 20px 10px; border-bottom: 4px solid #f59e0b; }
        .style-academic .avatar-wrap { margin-top: 25px; }
        .style-academic .avatar-wrap img { border-radius: 8px; border-color: #f59e0b; }
        .style-academic .student-name { font-size: 18px; font-weight: 700; color: #1e293b; margin-top: 10px; }
        .style-academic .tag { color: #15803d; font-weight: bold; font-size: 12px; }

        /* STYLE 3: MINIMAL WHITE */
        .style-minimal { background: #fafafa; border: 1px solid #e2e8f0; }
        .style-minimal .h-block { background: #ffffff; color: #0f172a; padding: 30px 15px 10px 15px; }
        .style-minimal .avatar-wrap { margin-top: 10px; }
        .style-minimal .avatar-wrap img { border-radius: 12px; border: 3px solid #f1f5f9; box-shadow: none; }
        .style-minimal .student-name { font-size: 19px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
        .style-minimal .tag { color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 11px; }

        /* STYLE 4: LUXURY DARK */
        .style-dark { background: #0f172a; color: #f1f5f9; border-color: #334155; }
        .style-dark .h-block { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #38bdf8; padding: 25px 15px; border-bottom: 2px solid #38bdf8; }
        .style-dark .avatar-wrap { margin-top: 25px; }
        .style-dark .avatar-wrap img { border: 3px solid #38bdf8; box-shadow: 0 0 15px rgba(56,189,248,0.2); }
        .style-dark .student-name { font-size: 18px; font-weight: 800; color: #ffffff; margin-top: 15px; }
        .style-dark .tag { color: #38bdf8; font-weight: bold; font-size: 11px; letter-spacing: 2px; }

        /* STYLE 5: ZIMBABWE HERITAGE */
        .style-heritage { border: 4px solid #16a34a; background: #fffdf5; }
        .style-heritage .h-block { background: #be123c; color: white; padding: 20px 15px; border-bottom: 4px solid #f59e0b; }
        .style-heritage .avatar-wrap { margin-top: 20px; }
        .style-heritage .avatar-wrap img { border: 4px solid #16a34a; }
        .style-heritage .student-name { font-size: 18px; font-weight: 900; color: #1e293b; margin-top: 10px; }
        .style-heritage .tag { color: #be123c; font-weight: bold; font-size: 12px; text-transform: uppercase; }

        /* STYLE 6: RED PROFESSIONAL */
        .style-red .h-block { background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%); color: white; padding: 25px 15px 45px 15px; }
        .style-red .avatar-wrap { margin-top: -45px; }
        .style-red .student-name { font-size: 18px; font-weight: 800; color: #1e293b; }
        .style-red .tag { color: #dc2626; font-weight: bold; text-transform: uppercase; font-size: 11px; }

        /* STYLE 7: GRADIENT GLASSMORPHISM */
        .style-glass { background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); }
        .style-glass .h-block { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(10px); padding: 25px 15px; border-bottom: 1px solid rgba(255,255,255,0.5); }
        .style-glass .avatar-wrap { margin-top: 25px; }
        .style-glass .avatar-wrap img { border: 4px solid rgba(255,255,255,0.8); }
        .style-glass .student-name { font-size: 18px; font-weight: 800; color: #0369a1; }
        .style-glass .tag { color: #0284c7; font-weight: bold; text-transform: uppercase; font-size: 11px; }

        /* STYLE 8: NAVY EXECUTIVE */
        .style-navy .h-block { background: #1e3a8a; color: white; padding: 25px 15px 45px 15px; }
        .style-navy .avatar-wrap { margin-top: -45px; }
        .style-navy .student-name { font-size: 18px; font-weight: 800; color: #1e293b; }
        .style-navy .tag { color: #1e3a8a; font-weight: bold; text-transform: uppercase; font-size: 11px; }

        /* STYLE 9: MODERN GEOMETRIC */
        .style-geometric { background: #fafafa; }
        .style-geometric .h-block { background: #4f46e5; color: white; padding: 30px 15px 45px 15px; border-bottom-right-radius: 40px; }
        .style-geometric .avatar-wrap { margin-top: -45px; }
        .style-geometric .avatar-wrap img { border-radius: 16px; border: 4px solid #ffffff; }
        .style-geometric .student-name { font-size: 18px; font-weight: 800; color: #1e293b; }
        .style-geometric .tag { color: #4f46e5; font-weight: bold; text-transform: uppercase; font-size: 11px; }

        /* STYLE 10: PREMIUM GOLD */
        .style-gold { border: 4px solid #d97706; background: #fffbeb; }
        .style-gold .h-block { background: #111827; color: #fbbf24; padding: 25px 15px; border-bottom: 2px solid #d97706; }
        .style-gold .avatar-wrap { margin-top: 25px; }
        .style-gold .avatar-wrap img { border: 3px solid #fbbf24; }
        .style-gold .student-name { font-size: 18px; font-weight: bold; color: #111827; margin-top: 15px; font-family: 'Playfair Display', serif; }
        .style-gold .tag { color: #b45309; font-weight: bold; text-transform: uppercase; font-size: 11px; }


        /* Metadata detail styling */
        .details-box {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            padding: 10px 20px;
            text-align: left;
        }
        .style-dark .details-box { border-top: 1px solid #334155; }
        .d-item { font-size: 11px; }
        .d-label { color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        .d-value { color: #1e293b; font-weight: 600; }
        .style-dark .d-value { color: #f1f5f9; }

        .f-barcode {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 10px 0;
            width: 100%;
        }
        .style-dark .f-barcode { background: #1e293b; border-top: 1px solid #334155; }
    </style>
</head>
<body>

    <!-- Platform compilation control deck (Hidden during print execution) -->
    <div class="no-print container-fluid py-3 border-bottom border-secondary" style="background: #1e293b; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;">
        <div class="row align-items-center justify-content-between">
            <div class="col-md-4">
                <h5 class="m-0 fw-bold text-white">{{ __('Student ID Cards Production Deck') }}</h5>
                <p class="m-0 text-secondary small">{{ __('Total queue:') }} <strong>{{ count($students) }} students</strong></p>
            </div>
            <div class="col-md-6 d-flex gap-2 justify-content-end">
                <select onchange="switchTemplate(this.value)" class="form-select bg-dark text-white border-secondary" style="width: auto;">
                    <option value="style-emerald">{{ __('Style 1: Modern Emerald') }}</option>
                    <option value="style-academic">{{ __('Style 2: Green Academic') }}</option>
                    <option value="style-minimal">{{ __('Style 3: Minimalist White') }}</option>
                    <option value="style-dark">{{ __('Style 4: Luxury Dark') }}</option>
                    <option value="style-heritage">{{ __('Style 5: Zimbabwe Heritage') }}</option>
                    <option value="style-red">{{ __('Style 6: Red Professional') }}</option>
                    <option value="style-glass">{{ __('Style 7: Gradient Glass') }}</option>
                    <option value="style-navy">{{ __('Style 8: Navy Executive') }}</option>
                    <option value="style-geometric">{{ __('Style 9: Geometric Design') }}</option>
                    <option value="style-gold">{{ __('Style 10: Premium Gold') }}</option>
                </select>
                <button onclick="window.print()" class="btn btn-success fw-bold px-4">{{ __('Execute Print Grid') }}</button>
            </div>
        </div>
    </div>

    <!-- Compile Render Containers -->
    <div class="print-container" style="margin-top: 90px; text-align: center; width: 100%;">
        @foreach($students as $student)
            <div class="id-card-render style-emerald m-3" id="card_{{ $student->id }}">
                <div class="card-inner">
                    <!-- Top header banner block -->
                    <div class="h-block">
                        <div style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $school->name }}
                        </div>
                        <div style="font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 1px; opacity: 0.85;">
                            {{ $school->motto ?? 'Education for Excellence' }}
                        </div>
                    </div>

                    <!-- Photo wrapper -->
                    <div class="avatar-wrap">
                        @php
                            $resolvedPhoto = resolve_public_asset_path($student->photo_path ?? null);
                            $fallbackPhoto = ($student->gender === 'female') ? 'images/no_profile_female.jpg' : 'images/no_profile_male.png';
                            $avatarSrc = $resolvedPhoto ? asset($resolvedPhoto) : asset($fallbackPhoto);
                        @endphp
                        <img class="u-avatar" src="{{ $avatarSrc }}" alt="Avatar">
                    </div>

                    <!-- Student Names and Levels -->
                    <div>
                        <div class="student-name">{{ $student->first_name }} {{ $student->last_name }}</div>
                        <div class="tag">
                            {{ $student->currentEnrollment ? "{$student->currentEnrollment->course->name} {$student->currentEnrollment->section->name}" : 'Unassigned' }}
                        </div>
                    </div>

                    <!-- Detail fields list -->
                    <div class="details-box">
                        <div class="d-item">
                            <div class="d-label">{{ __('Admission No.') }}</div>
                            <div class="d-value">{{ $student->admission_number }}</div>
                        </div>
                        <div class="d-item">
                            <div class="d-label">{{ __('National ID') }}</div>
                            <div class="d-value">{{ $student->national_id ?? 'N/A' }}</div>
                        </div>
                        <div class="d-item">
                            <div class="d-label">{{ __('Date of Birth') }}</div>
                            <div class="d-value">{{ $student->date_of_birth->format('d M Y') }}</div>
                        </div>
                        <div class="d-item">
                            <div class="d-label">{{ __('Expiry Date') }}</div>
                            <div class="d-value text-danger font-bold">{{ $student->resolved_card_expiry?->format('d M Y') ?? 'N/A' }}</div>
                        </div>
                        <div class="d-item">
                            <div class="d-label">{{ __('Boarding Status') }}</div>
                            <div class="d-value">{{ $student->boarding_status === 'boarder' ? 'Boarder' : 'Day Scholar' }}</div>
                        </div>
                        <div class="d-item">
                            <div class="d-label">{{ __('House') }}</div>
                            <div class="d-value">{{ $student->house ?? 'General' }}</div>
                        </div>
                    </div>

                    <!-- Barcode wrapper -->
                    <div class="f-barcode">
                        <div class="u-barcode">*{{ $student->admission_number }}*</div>
                        <span style="font-size: 8px; color: #94a3b8; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">{{ __('Powered by Tinway Technologies') }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function switchTemplate(styleClass) {
            const cards = document.querySelectorAll('.id-card-render');
            cards.forEach(card => {
                card.className = "id-card-render " + styleClass + " m-3";
            });
        }
    </script>
</body>
</html>