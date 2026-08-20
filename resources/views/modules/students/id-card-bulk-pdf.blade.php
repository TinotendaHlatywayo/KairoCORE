<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('ID Cards Print Session') }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #ffffff; line-height: 0; font-size: 0; }
        .page-break { page-break-after: always; }
        .a4-table { width: 100%; border-collapse: collapse; }
        .a4-row { width: 100%; }
        .a4-cell { vertical-align: top; box-sizing: border-box; }

        /* BASE CANVAS */
        .id-card {
            position: relative;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            background-size: cover;
            background-position: center;
            box-sizing: border-box;
            
            /* Typography & Padding overrides */
            font-family: var(--card-font-family, 'Helvetica', sans-serif) !important;
            padding: 0 !important; /* Zero padding to align coordinates exactly with live preview */
        }
        .portrait { width: 300px; height: 480px; margin: 0 auto; }
        .landscape { width: 480px; height: 300px; margin: 0 auto; }
        .crop-marks { border: 2px dashed #94a3b8 !important; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.08; width: 70%; pointer-events: none; }
        .absolute-element { position: absolute; }
        .student-photo { border-radius: 8px; object-fit: cover; width: 100%; height: 100%; }
        .barcode-container { text-align: center; font-size: 8px; font-family: monospace; }
        .barcode-svg svg { width: 100%; height: 100%; }
        .powered-seal { position: absolute; bottom: 4px; left: 0; width: 100%; text-align: center; font-size: 6px; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; }

        .pvc-page {
            position: relative;
            width: 100% !important;
            height: 100% !important;
            box-sizing: border-box;
        }

        /* 
         * EDGE-TO-EDGE FULL BLEED LAYOUT ENGINE
         * Sets absolute spacing coordinate boundaries to resolve DomPDF width sizing errors 
         */
        @if(($layout ?? 'pvc') === 'pvc')
            @page {
                margin: 0;
                size: auto;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .pvc-page {
                width: 100% !important;
                height: 100% !important;
                box-sizing: border-box;
                margin: 0 !important;
                overflow: hidden;
            }
            .id-card {
                position: absolute !important;
                top: var(--card-margin-v) !important;
                bottom: var(--card-margin-v) !important;
                left: var(--card-margin-h) !important;
                right: var(--card-margin-h) !important;
                border-radius: 0 !important; /* Full bleed edge */
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
            }
            .portrait {
                width: auto !important;
                height: auto !important;
            }
            .landscape {
                width: auto !important;
                height: auto !important;
            }
        @else
            /* A4 grid layouts */
            .a4-cell {
                padding: var(--card-margin-v) var(--card-margin-h) !important; /* Spacing padding */
            }
            .id-card {
                margin: 0 !important; /* Overrides default margin inside grid cells */
            }
        @endif

        /* =========================================================================
           THEME ENGINE STYLES (10 Premium Pre-designed Academic layouts)
           ========================================================================= */
        
        /* 1. CLASSIC */
        .theme-classic { border: var(--card-border-width) double var(--card-border-color) !important; background-color: var(--canvas-bg-color); }
        .theme-classic .card-header { background-color: var(--header-bg-color); color: var(--header-text-color); text-align: center; padding: 8px; }

        /* 2. MODERN GLASSMORPHIC */
        .theme-modern { 
            border: var(--card-border-width) solid rgba(255,255,255,0.8) !important; 
            background: linear-gradient(135deg, var(--canvas-bg-color) 0%, rgba(224, 231, 255, 0.9) 60%, rgba(245, 243, 255, 0.8) 100%) !important; 
        }
        .theme-modern .card-header { background: rgba(99, 102, 241, 0.15); color: #4f46e5; text-align: center; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.5); font-weight: 800; }

        /* 3. CORPORATE MINIMAL */
        .theme-corporate { border: var(--card-border-width) solid var(--card-border-color) !important; background-color: var(--canvas-bg-color); }
        .theme-corporate .card-header { background: #0f172a; color: #f1f5f9; text-align: center; padding: 8px; border-bottom: 3px solid var(--card-border-color); }

        /* 4. MINIMALIST ZEN */
        .theme-minimalist { border: var(--card-border-width) dashed var(--card-border-color) !important; background-color: var(--canvas-bg-color); }
        .theme-minimalist .card-header { background: transparent; color: var(--card-border-color); text-align: center; padding: 8px; border-bottom: 1px solid #f1f5f9; }

        /* 5. PREMIUM ROYAL GOLD */
        .theme-premium { border: var(--card-border-width) solid var(--card-border-color) !important; background-color: var(--canvas-bg-color); }
        .theme-premium .card-header { background: #0f172a; color: #fbbf24; text-align: center; padding: 10px; border-bottom: 2px solid #fbbf24; }

        /* 6. STATE INSTITUTIONAL */
        .theme-government { border: var(--card-border-width) solid var(--card-border-color) !important; background-color: var(--canvas-bg-color); }
        .theme-government .card-header { background: #065f46; color: white; text-align: center; padding: 8px; }

        /* 7. PLAYFUL KIDS */
        .theme-playful { border: var(--card-border-width) solid var(--card-border-color) !important; background: linear-gradient(135deg, var(--canvas-bg-color) 0%, #fff7ed 100%); }
        .theme-playful .card-header { background-color: #f43f5e; color: white; text-align: center; padding: 8px; border-radius: 0 0 16px 16px; }

        /* 8. COLLEGIATE VARSITY */
        .theme-collegiate { border: var(--card-border-width) solid var(--card-border-color) !important; background-color: var(--canvas-bg-color); }
        .theme-collegiate .card-header { background-color: #7f1d1d; color: white; text-align: center; padding: 8px; text-shadow: 2px 2px 0px rgba(0,0,0,0.1); }

        /* 9. CYBER TECH GRID */
        .theme-tech { border: var(--card-border-width) solid var(--card-border-color) !important; background-color: var(--canvas-bg-color); color: var(--card-border-color); }
        .theme-tech .card-header { background-color: #161b22; color: var(--card-border-color); text-align: center; padding: 8px; border-bottom: 2px solid var(--card-border-color); }

        /* 10. VINTAGE RETRO ACADEMY */
        .theme-vintage { border: var(--card-border-width) solid var(--card-border-color) !important; background-color: var(--canvas-bg-color); }
        .theme-vintage .card-header { background-color: #451a03; color: #fef3c7; text-align: center; padding: 8px; border-bottom: 2px double var(--card-border-color); }

        /* Positioning & Customizable Typography Helpers */
        .photo-position { left: var(--photo-x); top: var(--photo-y); width: var(--photo-width); height: var(--photo-height); }
        
        .name-position { 
            left: var(--name-x); top: var(--name-y); width: 80%; text-align: center; 
            font-size: var(--name-font-size); color: var(--name-color); 
            font-family: var(--name-font-family) !important;
        }
        
        .class-position { 
            left: var(--class-x); top: var(--class-y); width: 80%; text-align: center; 
            font-size: var(--class-font-size); color: var(--class-color); 
            font-family: var(--class-font-family) !important;
        }
        
        .qr-position { left: var(--qr-x); top: var(--qr-y); }
        .qr-img { width: var(--qr-size); height: var(--qr-size); border: 1px solid #cbd5e1; padding: 1px; background: white; }
        .barcode-position { left: var(--barcode-x); top: var(--barcode-y); width: var(--barcode-width); }
        .barcode-svg { height: var(--barcode-height); }
    </style>
</head>
<body>

    @php
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
                                @php
                                    $template = $selectedTemplate ?? \App\Http\Controllers\StudentCardPrintController::resolveTemplateForStudent($student, $school->id);
                                    $activeTheme = $template->layout_config['design_theme'] ?? 'classic';
                                    
                                    $nameFont = $template->layout_config['name_font_family'] ?? 'sans-serif';
                                    $classFont = $template->layout_config['class_font_family'] ?? 'sans-serif';
                                    $metaFont = $template->layout_config['meta_font_family'] ?? 'sans-serif';

                                    // RAW TEXT METADATA PAYLOAD QR CODE IN COMPATIBLE JPEG FORMAT (BYPASSES GD LIBRARY ENGINE)
                                    $qrTextPayload = "Name: " . $student->full_name . "\nID: " . $student->student_id_number . "\nAdm: " . $student->admission_number . "\nSchool: " . $school->name . "\nExpiry: " . ($student->resolved_card_expiry ? $student->resolved_card_expiry->format('d-M-Y') : 'N/A');
                                    $qrRawUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=jpg&data=" . urlencode($qrTextPayload);
                                    
                                    // OFFLINE Base64 conversion
                                    try {
                                        $context = stream_context_create([
                                            "ssl" => [
                                                "verify_peer" => false,
                                                "verify_peer_name" => false,
                                            ],
                                        ]);
                                        $qrRawData = @file_get_contents($qrRawUrl, false, $context);
                                        if ($qrRawData) {
                                            $qrCodeUrlString = 'data:image/jpeg;base64,' . base64_encode($qrRawData);
                                        } else {
                                            $qrCodeUrlString = $qrRawUrl;
                                        }
                                    } catch (\Exception $e) {
                                        $qrCodeUrlString = $qrRawUrl;
                                    }

                                    // GENDER-SPECIFIC PHOTO RESOLUTION FALLBACK
                                    $photoPath = student_photo_src($student);

                                    // AUTO-SCALE PHOTO to fit the frame without stretching (aspect-ratio preserved)
                                    $cardWidth = ($template->orientation === 'landscape') ? 480 : 300;
                                    $cardHeight = ($template->orientation === 'landscape') ? 300 : 480;
                                    $photoFrameW = $cardWidth * (($template->layout_config['photo_width'] ?? 30) / 100);
                                    $photoFrameH = $cardHeight * (($template->layout_config['photo_height'] ?? 25) / 100);
                                    $photoImgDims = @getimagesize($photoPath);
                                    $photoDispW = $photoFrameW;
                                    $photoDispH = $photoFrameH;
                                    if ($photoImgDims) {
                                        $photoRatio = min($photoFrameW / $photoImgDims[0], $photoFrameH / $photoImgDims[1]);
                                        $photoDispW = $photoImgDims[0] * $photoRatio;
                                        $photoDispH = $photoImgDims[1] * $photoRatio;
                                    }
                                @endphp
                                <td class="a4-cell" style="width: {{ 100 / $columnsPerRow }}%; --card-margin-v: {{ $template->layout_config['card_margin_v'] ?? 0 }}px; --card-margin-h: {{ $template->layout_config['card_margin_h'] ?? 0 }}px;">
                                    
                                    <div class="id-card {{ $template->orientation }} theme-{{ $activeTheme }} {{ $crop_marks ? 'crop-marks' : '' }}" 
                                         style="background-image: url('{{ $template->background_path ? public_path($template->background_path) : '' }}');
                                                --name-font-family: {{ $nameFont }};
                                                --class-font-family: {{ $classFont }};
                                                --meta-font-family: {{ $metaFont }};
                                                --card-padding: {{ $template->layout_config['card_padding'] ?? 10 }}px;
                                                --card-border-width: {{ $template->layout_config['card_border_width'] ?? 3 }}px;
                                                --card-border-color: {{ $template->layout_config['card_border_color'] ?? '#1e3a8a' }};
                                                --canvas-bg-color: {{ $template->layout_config['canvas_bg_color'] ?? '#ffffff' }};
                                                --header-bg-color: {{ $template->layout_config['header_bg_color'] ?? '#1e3a8a' }};
                                                --header-text-color: {{ $template->layout_config['header_text_color'] ?? '#ffffff' }};
                                                --photo-x: {{ $template->layout_config['photo_x'] ?? 35 }}%;
                                                --photo-y: {{ $template->layout_config['photo_y'] ?? 15 }}%;
                                                --photo-width: {{ $template->layout_config['photo_width'] ?? 30 }}%;
                                                --photo-height: {{ $template->layout_config['photo_height'] ?? 25 }}%;
                                                --name-x: {{ $template->layout_config['name_x'] ?? 10 }}%;
                                                --name-y: {{ $template->layout_config['name_y'] ?? 45 }}%;
                                                --name-font-size: {{ $template->layout_config['name_font_size'] ?? 14 }}px;
                                                --name-color: {{ $template->layout_config['name_color'] ?? '#1e3a8a' }};
                                                --class-x: {{ $template->layout_config['class_x'] ?? 10 }}%;
                                                --class-y: {{ $template->layout_config['class_y'] ?? 52 }}%;
                                                --class-font-size: {{ $template->layout_config['class_font_size'] ?? 9 }}px;
                                                --class-color: {{ $template->layout_config['class_color'] ?? '#64748b' }};
                                                --qr-x: {{ $template->layout_config['qr_x'] ?? 10 }}%;
                                                --qr-y: {{ $template->layout_config['qr_y'] ?? 70 }}%;
                                                --qr-size: {{ $template->layout_config['qr_size'] ?? 50 }}px;
                                                --barcode-x: {{ $template->layout_config['barcode_x'] ?? 10 }}%;
                                                --barcode-y: {{ $template->layout_config['barcode_y'] ?? 82 }}%;
                                                --barcode-width: {{ $template->layout_config['barcode_width'] ?? 80 }}%;
                                                --barcode-height: {{ $template->layout_config['barcode_height'] ?? 30 }}px;">
                                        
                                        @if($school->logo_path)
                                            <img class="watermark" src="{{ public_path($school->logo_path) }}">
                                        @endif

                                        @if($template->layout_config['show_school_header'] ?? true)
                                            <div class="school-header card-header">
                                                <div style="font-size: 11px; font-weight: bold; text-transform: uppercase;">{{ $school->name }}</div>
                                            </div>
                                        @endif

                                        @if(($template->layout_config['show_photo'] ?? true))
                                            <div class="absolute-element photo-position" style="text-align: center; line-height: {{ $photoFrameH }}px;">
                                                <img class="student-photo" 
                                                     src="{{ $photoPath }}" 
                                                     style="width: {{ $photoDispW }}px; height: {{ $photoDispH }}px; vertical-align: middle; border-radius: {{ $template->layout_config['photo_rounded_corners'] ?? 8 }}px;">
                                            </div>
                                        @endif

                                        @if($template->layout_config['show_name'] ?? true)
                                            <div class="absolute-element name-position" style="font-weight: bold;">
                                                {{ $student->full_name }}
                                            </div>
                                        @endif

                                        @if($template->layout_config['show_class'] ?? true)
                                            <div class="absolute-element class-position">
                                                Class: {{ $student->currentEnrollment?->section?->full_name ?? 'Not Enrolled' }}
                                            </div>
                                        @endif

                                        <div class="absolute-element" style="left: {{ $template->layout_config['meta_x'] ?? 10 }}%; top: {{ $template->layout_config['meta_y'] ?? 58 }}%; font-size: {{ $template->layout_config['meta_font_size'] ?? 8 }}px; line-height: 1.4; color: {{ $template->layout_config['meta_color'] ?? '#334155' }}; font-family: var(--meta-font-family) !important;">
                                            @if($template->layout_config['show_student_id'] ?? true)
                                                <strong>{{ __('Student ID:') }}</strong> {{ $student->student_id_number }}<br/>
                                            @endif
                                            @if($template->layout_config['show_admission_no'] ?? true)
                                                <strong>{{ __('Admission No:') }}</strong> {{ $student->admission_number }}<br/>
                                            @endif
                                            @if($template->layout_config['show_expiry'] ?? true)
                                                <strong>{{ __('Expiry Date:') }}</strong> {{ $student->resolved_card_expiry ? $student->resolved_card_expiry->format('d-M-Y') : 'N/A' }}
                                            @endif
                                        </div>

                                        @if($template->layout_config['show_qr'] ?? true)
                                            <div class="absolute-element qr-position">
                                                <img class="qr-img" src="{{ $qrCodeUrlString }}">
                                            </div>
                                        @endif

                                        @if($template->layout_config['show_barcode'] ?? true)
                                            <div class="absolute-element barcode-position barcode-container" style="color: {{ $template->layout_config['barcode_text_color'] ?? '#000000' }};">
                                                <div class="barcode-svg">
                                                    @php
                                                        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                                                        $barcodeType = match ($template->barcode_format) {
                                                            'Code39' => $generator::TYPE_CODE_39,
                                                            'EAN13'  => $generator::TYPE_EAN_13,
                                                            default  => $generator::TYPE_CODE_128,
                                                        };
                                                        $barcodeVal = ($template->barcode_format === 'EAN13') ? preg_replace('/[^0-9]/', '', $student->student_id_number) : $student->student_id_number;
                                                    @endphp
                                                    {!! $generator->getBarcode($barcodeVal, $barcodeType) !!}
                                                </div>
                                                <span style="letter-spacing: 2px; font-weight: bold;">{{ $student->student_id_number }}</span>
                                            </div>
                                        @endif

                                        <!-- ADDED: Absolutely positioned school motto with complete design coordinates -->
                                        @if($template->layout_config['show_school_motto'] ?? true)
                                            <div class="absolute-element" 
                                                 style="left: {{ $template->layout_config['motto_x'] ?? 10 }}%; 
                                                        top: {{ $template->layout_config['motto_y'] ?? 8 }}%; 
                                                        font-size: {{ $template->layout_config['motto_font_size'] ?? 7 }}px; 
                                                        color: {{ $template->layout_config['motto_color'] ?? '#cbd5e1' }}; 
                                                        font-family: {{ $template->layout_config['motto_font_family'] ?? 'sans-serif' }} !important; 
                                                        font-weight: {{ ($template->layout_config['motto_is_bold'] ?? false) ? 'bold' : 'normal' }}; 
                                                        font-style: {{ ($template->layout_config['motto_is_italic'] ?? true) ? 'italic' : 'normal' }};
                                                        white-space: nowrap;">
                                                "{{ $school->motto }}"
                                            </div>
                                        @endif

                                        <div class="powered-seal">
                                            {{ __('Powered by Tinway Technologies') }}
                                        </div>
                                    </div>
                                    
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
            @php
                $student = $pageChunk->first();
                $template = $selectedTemplate ?? \App\Http\Controllers\StudentCardPrintController::resolveTemplateForStudent($student, $school->id);
                $activeTheme = $template->layout_config['design_theme'] ?? 'classic';
                
                $nameFont = $template->layout_config['name_font_family'] ?? 'sans-serif';
                $classFont = $template->layout_config['class_font_family'] ?? 'sans-serif';
                $metaFont = $template->layout_config['meta_font_family'] ?? 'sans-serif';

                // RAW TEXT METADATA PAYLOAD QR CODE IN COMPATIBLE JPEG FORMAT (BYPASSES GD ENGINE)
                $qrTextPayload = "Name: " . $student->full_name . "\nID: " . $student->student_id_number . "\nAdm: " . $student->admission_number . "\nSchool: " . $school->name . "\nExpiry: " . ($student->resolved_card_expiry ? $student->resolved_card_expiry->format('d-M-Y') : 'N/A');
                $qrRawUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=jpg&data=" . urlencode($qrTextPayload);
                
                // OFFLINE Base64 conversion
                try {
                    $context = stream_context_create([
                        "ssl" => [
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        ],
                    ]);
                    $qrRawData = @file_get_contents($qrRawUrl, false, $context);
                    if ($qrRawData) {
                        $qrCodeUrlString = 'data:image/jpeg;base64,' . base64_encode($qrRawData);
                    } else {
                        $qrCodeUrlString = $qrRawUrl;
                    }
                } catch (\Exception $e) {
                    $qrCodeUrlString = $qrRawUrl;
                }

                // GENDER-SPECIFIC PHOTO RESOLUTION FALLBACK
                $photoPath = student_photo_src($student);

                // AUTO-SCALE PHOTO to fit the frame without stretching (aspect-ratio preserved)
                $cardWidth = ($template->orientation === 'landscape') ? 480 : 300;
                $cardHeight = ($template->orientation === 'landscape') ? 300 : 480;
                $photoFrameW = $cardWidth * (($template->layout_config['photo_width'] ?? 30) / 100);
                $photoFrameH = $cardHeight * (($template->layout_config['photo_height'] ?? 25) / 100);
                $photoImgDims = @getimagesize($photoPath);
                $photoDispW = $photoFrameW;
                $photoDispH = $photoFrameH;
                if ($photoImgDims) {
                    $photoRatio = min($photoFrameW / $photoImgDims[0], $photoFrameH / $photoImgDims[1]);
                    $photoDispW = $photoImgDims[0] * $photoRatio;
                    $photoDispH = $photoImgDims[1] * $photoRatio;
                }
            @endphp
            <div class="pvc-page" style="--card-margin-v: {{ $template->layout_config['card_margin_v'] ?? 0 }}px; --card-margin-h: {{ $template->layout_config['card_margin_h'] ?? 0 }}px; page-break-after: {{ $chunkIndex < count($chunks) - 1 ? 'always' : 'avoid' }};">
                <div class="id-card {{ $template->orientation }} theme-{{ $activeTheme }} {{ $crop_marks ? 'crop-marks' : '' }}" 
                     style="background-image: url('{{ $template->background_path ? public_path($template->background_path) : '' }}');
                            --card-font-family: {{ $nameFont }};
                            --card-padding: {{ $template->layout_config['card_padding'] ?? 10 }}px;
                            --card-border-width: {{ $template->layout_config['card_border_width'] ?? 3 }}px;
                            --card-border-color: {{ $template->layout_config['card_border_color'] ?? '#1e3a8a' }};
                            --canvas-bg-color: {{ $template->layout_config['canvas_bg_color'] ?? '#ffffff' }};
                            --header-bg-color: {{ $template->layout_config['header_bg_color'] ?? '#1e3a8a' }};
                            --header-text-color: {{ $template->layout_config['header_text_color'] ?? '#ffffff' }};
                            --photo-x: {{ $template->layout_config['photo_x'] ?? 35 }}%;
                            --photo-y: {{ $template->layout_config['photo_y'] ?? 15 }}%;
                            --photo-width: {{ $template->layout_config['photo_width'] ?? 30 }}%;
                            --photo-height: {{ $template->layout_config['photo_height'] ?? 25 }}%;
                            --name-x: {{ $template->layout_config['name_x'] ?? 10 }}%;
                            --name-y: {{ $template->layout_config['name_y'] ?? 45 }}%;
                            --name-font-size: {{ $template->layout_config['name_font_size'] ?? 14 }}px;
                            --name-color: {{ $template->layout_config['name_color'] ?? '#1e3a8a' }};
                            --class-x: {{ $template->layout_config['class_x'] ?? 10 }}%;
                            --class-y: {{ $template->layout_config['class_y'] ?? 52 }}%;
                            --class-font-size: {{ $template->layout_config['class_font_size'] ?? 9 }}px;
                            --class-color: {{ $template->layout_config['class_color'] ?? '#64748b' }};
                            --qr-x: {{ $template->layout_config['qr_x'] ?? 10 }}%;
                            --qr-y: {{ $template->layout_config['qr_y'] ?? 70 }}%;
                            --qr-size: {{ $template->layout_config['qr_size'] ?? 50 }}px;
                            --barcode-x: {{ $template->layout_config['barcode_x'] ?? 10 }}%;
                            --barcode-y: {{ $template->layout_config['barcode_y'] ?? 82 }}%;
                            --barcode-width: {{ $template->layout_config['barcode_width'] ?? 80 }}%;
                            --barcode-height: {{ $template->layout_config['barcode_height'] ?? 30 }}px;">
                    
                    @if($school->logo_path)
                        <img class="watermark" src="{{ public_path($school->logo_path) }}">
                    @endif

                    @if($template->layout_config['show_school_header'] ?? true)
                        <div class="school-header card-header">
                            <div style="font-size: 11px; font-weight: bold; text-transform: uppercase;">{{ $school->name }}</div>
                        </div>
                    @endif

                    @if(($template->layout_config['show_photo'] ?? true))
                        <div class="absolute-element photo-position" style="text-align: center; line-height: {{ $photoFrameH }}px;">
                            <img class="student-photo" 
                                 src="{{ $photoPath }}" 
                                 style="width: {{ $photoDispW }}px; height: {{ $photoDispH }}px; vertical-align: middle; border-radius: {{ $template->layout_config['photo_rounded_corners'] ?? 8 }}px;">
                        </div>
                    @endif

                    @if($template->layout_config['show_name'] ?? true)
                        <div class="absolute-element name-position" style="font-weight: bold;">
                            {{ $student->full_name }}
                        </div>
                    @endif

                    @if($template->layout_config['show_class'] ?? true)
                        <div class="absolute-element class-position">
                            Class: {{ $student->currentEnrollment?->section?->full_name ?? 'Not Enrolled' }}
                        </div>
                    @endif

                    <div class="absolute-element" style="left: {{ $template->layout_config['meta_x'] ?? 10 }}%; top: {{ $template->layout_config['meta_y'] ?? 58 }}%; font-size: {{ $template->layout_config['meta_font_size'] ?? 8 }}px; line-height: 1.4; color: {{ $template->layout_config['meta_color'] ?? '#334155' }}; font-family: var(--meta-font-family) !important;">
                        @if($template->layout_config['show_student_id'] ?? true)
                            <strong>{{ __('Student ID:') }}</strong> {{ $student->student_id_number }}<br/>
                        @endif
                        @if($template->layout_config['show_admission_no'] ?? true)
                            <strong>{{ __('Admission No:') }}</strong> {{ $student->admission_number }}<br/>
                        @endif
                        @if($template->layout_config['show_expiry'] ?? true)
                            <strong>{{ __('Expiry Date:') }}</strong> {{ $student->resolved_card_expiry ? $student->resolved_card_expiry->format('d-M-Y') : 'N/A' }}
                        @endif
                    </div>

                    @if($template->layout_config['show_qr'] ?? true)
                        <div class="absolute-element qr-position">
                            <img class="qr-img" src="{{ $qrCodeUrlString }}">
                        </div>
                    @endif

                    @if($template->layout_config['show_barcode'] ?? true)
                        <div class="absolute-element barcode-position barcode-container" style="color: {{ $template->layout_config['barcode_text_color'] ?? '#000000' }};">
                            <div class="barcode-svg">
                                @php
                                    $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                                    $barcodeType = match ($template->barcode_format) {
                                        'Code39' => $generator::TYPE_CODE_39,
                                        'EAN13'  => $generator::TYPE_EAN_13,
                                        default  => $generator::TYPE_CODE_128,
                                    };
                                    $barcodeVal = ($template->barcode_format === 'EAN13') ? preg_replace('/[^0-9]/', '', $student->student_id_number) : $student->student_id_number;
                                @endphp
                                {!! $generator->getBarcode($barcodeVal, $barcodeType) !!}
                            </div>
                            <span style="letter-spacing: 2px; font-weight: bold;">{{ $student->student_id_number }}</span>
                        </div>
                    @endif

                    <!-- ADDED: Absolutely positioned school motto with complete design coordinates -->
                    @if($template->layout_config['show_school_motto'] ?? true)
                        <div class="absolute-element" 
                             style="left: {{ $template->layout_config['motto_x'] ?? 10 }}%; 
                                    top: {{ $template->layout_config['motto_y'] ?? 8 }}%; 
                                    font-size: {{ $template->layout_config['motto_font_size'] ?? 7 }}px; 
                                    color: {{ $template->layout_config['motto_color'] ?? '#cbd5e1' }}; 
                                    font-family: {{ $template->layout_config['motto_font_family'] ?? 'sans-serif' }} !important; 
                                    font-weight: {{ ($template->layout_config['motto_is_bold'] ?? false) ? 'bold' : 'normal' }}; 
                                    font-style: {{ ($template->layout_config['motto_is_italic'] ?? true) ? 'italic' : 'normal' }};
                                    white-space: nowrap;">
                            "{{ $school->motto }}"
                        </div>
                    @endif

                    <div class="powered-seal">
                        {{ __('Powered by Tinway Technologies') }}
                    </div>
                </div>

            </div>
        @endforeach
    @endif

</body>
</html>
