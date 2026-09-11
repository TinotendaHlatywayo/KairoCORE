{{-- Professional School ID Card Template --}}
{{-- DomPDF-compatible: absolute positioning, tables, no Grid/flex:1/clip-path/gap/aspect-ratio --}}
@php
    $school = $school ?? current_tenant();
    $tpl = $template ?? \App\Http\Controllers\StudentCardPrintController::resolveTemplateForStudent($student, $school?->id ?? 0);
    $cfg = $tpl->layout_config ?? [];
    $orientation = $tpl->orientation ?? 'landscape';
    
    $canvasW = $orientation === 'landscape' ? 480 : 300;
    $canvasH = $orientation === 'landscape' ? 300 : 480;
    
    $val = fn (string $key, mixed $default = null) => $cfg[$key] ?? $default;
    $px = fn (string $key, float $default) => max(1, (float) $val($key, $default));
    
    $font = function (string $prefix, float $size, string $color = '#1e293b') use ($val): string {
        return 'font-family: '.e($val($prefix.'_font_family', 'sans-serif')).'; font-size: '.max(5, (float) $val($prefix.'_font_size', $size)).'px; color: '.e($val($prefix.'_color', $color)).'; font-weight: '.($val($prefix.'_is_bold', false) ? '700' : '400').'; font-style: '.($val($prefix.'_is_italic', false) ? 'italic' : 'normal').';';
    };
    
    $schoolAttrs = $school?->getAttributes() ?? [];
    $schoolName = $val('custom_school_name') ?: ($school?->name ?? 'School Name');
    $motto = $val('custom_school_motto') ?: ($school?->motto ?? 'Excellence In Education');
    $contactAddress = $val('contact_address') ?: ($schoolAttrs['physical_address'] ?? '');
    $contactPhone = $val('contact_phone') ?: ($schoolAttrs['phone'] ?? $schoolAttrs['phone_number'] ?? '');
    $contactEmail = $val('contact_email') ?: ($schoolAttrs['email_address'] ?? $schoolAttrs['email'] ?? '');
    $contactWebsite = $val('contact_website') ?: ($schoolAttrs['website_url'] ?? '');
    
    $showContactAddr = $val('show_contact_address', true);
    $showContactPh   = $val('show_contact_phone', true);
    $showContactEm   = $val('show_contact_email', true);
    $showContactWeb  = $val('show_contact_website', true);
    $showContactDetails = $val('show_contact_details', true);
    
    // Logo — no background, just the image
    $logoPath = $val('logo_path') ?: \Modules\Admin\Models\SystemSetting::get('branding', 'branding_logo_path', '');
    $logoData = id_card_file_data_uri($logoPath) ?: id_card_file_data_uri($schoolAttrs['logo_path'] ?? null);
    if (! $logoData && is_file(public_path('images/id-card-default-logo.png'))) {
        $logoData = id_card_file_data_uri('images/id-card-default-logo.png');
    }
    
    $photoPath = is_string($student->photo_path ?? null) ? $student->photo_path : null;
    $photoData = $photoPath ? id_card_file_data_uri($photoPath) : null;
    if (! $photoData) {
        $fallback = student_photo_src($student);
        $photoData = is_file($fallback) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($fallback)) : null;
    }
    
    $courseName = $student->currentEnrollment?->course?->name ?? '';
    $sectionName = $student->currentEnrollment?->section?->name ?? '';
    $classLabel = $student->currentEnrollment?->section?->full_name
        ?: trim($courseName . ' ' . $sectionName)
        ?: 'Class';
    $id = $student->student_id_number ?: $student->admission_number ?: 'N/A';
    $expiry = $student->resolved_card_expiry?->format('d M Y') ?? 'N/A';
    $dob = $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : 'N/A';
    $studentAddress = \Illuminate\Support\Str::limit($student->physical_address ?: 'Borrowdale, Harare', 42);
    $studentPhone = $student->phone ?: 'N/A';
    $nationalId = $student->national_id ?: 'N/A';
    
    $primaryColor = $val('primary_color', '#1e3a8a');
    $primaryDark = $val('primary_dark', '#0f172a');
    $accentColor = $val('accent_color', '#fbbf24');
    $textPrimary = $val('text_primary', '#0f172a');
    $textSecondary = $val('text_secondary', '#334155');
    $textMuted = $val('text_muted', '#64748b');
    $footerBg = $val('footer_bg', '#0f172a');
    $footerText = $val('footer_text', '#fbbf24');
    
    $logoBgAlpha = max(0, min(100, (float) $val('logo_bg_transparency', 50))) / 100;
    $logoBgStyle = '';
    if ($val('logo_bg_mode', 'none') === 'gradient') {
        $logoBgStyle = 'background: linear-gradient(135deg, '.id_card_hex_to_rgba((string) $val('logo_bg_color', '#ffffff'), $logoBgAlpha).', '.id_card_hex_to_rgba((string) $val('logo_bg_gradient_end', '#e0e7ff'), $logoBgAlpha).');';
    } elseif ($val('logo_bg_mode', 'none') === 'solid') {
        $logoBgStyle = 'background-color: '.id_card_hex_to_rgba((string) $val('logo_bg_color', '#ffffff'), $logoBgAlpha).';';
    }
    
    // Orientation-adaptive heights — no strip, header takes full top area
    if ($orientation === 'portrait') {
        $headerH  = 90;
        $footerH  = 50;
    } else {
        $headerH  = 82;
        $footerH  = 42;
    }
    $mainH = $canvasH - $headerH - $footerH;
    $photoW = $orientation === 'portrait' ? 28 : 20;
    $infoW = 100 - $photoW;
    $logoAreaW = $orientation === 'portrait' ? 22 : 26;
    
    // Build contact lines for footer
    $contactLineMode = $val('contact_line_mode', 'single');
    $footerContactParts = [];
    if ($showContactDetails) {
        if ($showContactAddr && $contactAddress) $footerContactParts[] = $contactAddress;
        if ($showContactPh && $contactPhone) $footerContactParts[] = $contactPhone;
        if ($showContactEm && $contactEmail) $footerContactParts[] = strtolower($contactEmail);
        if ($showContactWeb && $contactWebsite) $footerContactParts[] = $contactWebsite;
    }
    
    // QR
    $verifyUrl = route('card.verify', ['hash' => hash_hmac('sha256', (string) $id, config('app.key'))]);
    $qrDataText = "STUDENT IDENTITY CARD\nSchool: {$schoolName}\nID: {$id}\nName: {$student->full_name}\nClass: {$classLabel}\nDOB: {$dob}\nNational ID: {$nationalId}\nVerify: {$verifyUrl}";
    $qr = id_card_generate_qr($qrDataText, (int) $px('qr_size', 58) * 2);
    
    $barcode = null;
    if ($val('show_barcode', false)) {
        try { $barcode = 'data:image/png;base64,'.base64_encode((new \Picqer\Barcode\BarcodeGeneratorPNG())->getBarcode($id, \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 2, (int) $px('barcode_height', 30))); } catch (\Throwable $e) {}
    }
@endphp

<div class="id-card-professional id-card {{ $orientation }}" style="width: {{ $canvasW }}px; height: {{ $canvasH }}px; position: relative; box-sizing: border-box; font-family: 'Inter', Helvetica, Arial, sans-serif; border: {{ (int) $px('card_border_width', 3) }}px solid {{ e($val('card_border_color', '#1e3a8a')) }}; border-radius: 0; overflow: hidden; background: #ffffff;">

    {{-- BACKGROUND --}}
    @php
        $bgColor = e($val('canvas_bg_color', '#ffffff'));
        $gradient = id_card_gradient_data_uri((string) $val('canvas_bg_color', '#ffffff'), (string) $val('canvas_gradient_end_color', '#e0e7ff'), $canvasW, $canvasH, 1.0);
        $bgMode = $val('bg_mode', 'solid');
        $rawBgPath = $tpl->background_path ?? ($cfg['background_path'] ?? null);
        $watermarkImage = null;
        if ($bgMode === 'image') { $watermarkImage = id_card_file_data_uri($rawBgPath); }
        elseif ($bgMode === 'logo') { $watermarkImage = $logoData; }
        else { $watermarkImage = id_card_file_data_uri($rawBgPath); }
        $watermarkOpacity = max(0, min(100, (float) $val('canvas_bg_watermark_opacity', 100))) / 100;
    @endphp
    <div style="position: absolute; top: 0; left: 0; width: {{ $canvasW }}px; height: {{ $canvasH }}px; z-index: 0; background-color: {{ $bgColor }}; @if($bgMode === 'gradient') {{ $gradient ? "background-image:url('{$gradient}');" : '' }} @endif overflow: hidden;">
        @if($watermarkImage)
            <img src="{{ $watermarkImage }}" style="position: absolute; top: 0; left: 0; width: {{ $canvasW }}px; height: {{ $canvasH }}px; opacity: {{ $watermarkOpacity }};">
        @endif
    </div>

    {{-- HEADER --}}
    <div style="position: absolute; top: 0; left: 0; width: {{ $canvasW }}px; height: {{ $headerH }}px; z-index: 1; background: {{ $primaryDark }};">
        {{-- Logo — no background, transparent blend --}}
        <div style="position: absolute; top: 0; left: 0; width: {{ $logoAreaW }}%; height: {{ $headerH }}px; text-align: center; vertical-align: middle;">
            @if($val('show_school_logo', true) && $logoData)
                <div style="padding-top: {{ $orientation === 'portrait' ? 14 : 12 }}px;">
                    @if($logoBgStyle)
                    <div style="display: inline-block; padding: {{ (int) $px('logo_padding', 2) }}px; border-radius: {{ (int) $px('logo_rounded_corners', 6) }}px; {{ $logoBgStyle }} ">
                    @endif
                    <img src="{{ $logoData }}" style="width: {{ $orientation === 'portrait' ? 52 : 58 }}px; height: {{ $orientation === 'portrait' ? 52 : 58 }}px;">
                    @if($logoBgStyle)
                    </div>
                    @endif
                </div>
            @elseif($val('show_school_logo', true))
                <div style="padding-top: {{ $orientation === 'portrait' ? 18 : 16 }}px; color: {{ $accentColor }}; font-weight: 800; font-size: 22px; text-transform: uppercase;">
                    {{ mb_substr($schoolName, 0, 1) }}
                </div>
            @endif
        </div>
        {{-- School name & motto --}}
        <div style="position: absolute; top: 0; left: {{ $logoAreaW }}%; width: {{ 100 - $logoAreaW }}%; height: {{ $headerH }}px; padding: 6px 10px; box-sizing: border-box; color: {{ $accentColor }};">
            @if($val('show_school_header', true))
                <div style="{{ $font('school_name', $orientation === 'portrait' ? 14 : 16, $accentColor) }} text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.1; padding-top: 6px;">
                    {{ $schoolName }}
                </div>
            @endif
            @if($val('show_school_motto', true))
                <div style="{{ $font('motto', $orientation === 'portrait' ? 8 : 9, '#cbd5e1') }} line-height: 1.15; margin-top: 1px;">
                    {{ $motto }}
                </div>
            @endif
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div style="position: absolute; top: {{ $headerH }}px; left: 0; width: {{ $canvasW }}px; height: {{ $mainH }}px; z-index: 1; background: #ffffff; padding: 6px 10px; box-sizing: border-box;">

        {{-- PHOTO --}}
        @if($val('show_photo', true) && $photoData)
            <div style="float: left; width: {{ $photoW }}%; text-align: center; padding-top: 2px;">
                <img src="{{ $photoData }}" style="width: {{ $orientation === 'portrait' ? 80 : 72 }}px; height: {{ $orientation === 'portrait' ? 104 : 96 }}px; border-radius: {{ $px('photo_rounded_corners', 8) }}px; border: {{ $px('photo_border_width', 2) }}px solid {{ e($val('photo_border_color', '#fbbf24')) }}; box-sizing: border-box;">
                @if($val('show_photo_caption', true))
                    <div style="{{ $font('photo_caption', 7, $textMuted) }} text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                        {{ $val('photo_caption_text', 'STUDENT') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- INFO --}}
        <div style="position: relative; float: right; width: {{ $infoW - 2 }}%;">
            @if($val('show_name', true))
                <div style="{{ $font('name', $orientation === 'portrait' ? 14 : 16, $textPrimary) }} text-transform: uppercase; letter-spacing: 0.3px; line-height: 1.1; margin-bottom: 3px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0;">
                    {{ $student->full_name }}
                </div>
            @endif

            <table style="width: 100%; border-collapse: collapse; border-spacing: 0; font-size: {{ $orientation === 'portrait' ? 9 : 10 }}px; line-height: 1.45;">
                @if($val('show_student_id', true))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Student ID') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $primaryColor) }} font-weight: 600; padding: 1px 0; vertical-align: top;">{{ $id }}</td>
                    </tr>
                @endif
                @if($val('show_class', true))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Class') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $textPrimary) }} font-weight: 600; padding: 1px 0; vertical-align: top;">{{ $classLabel }}</td>
                    </tr>
                @endif
                @if($val('show_dob', true))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Date of Birth') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $textPrimary) }} padding: 1px 0; vertical-align: top;">{{ $dob }}</td>
                    </tr>
                @endif
                @if($val('show_address', true))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Address') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $textPrimary) }} padding: 1px 0; vertical-align: top;">{{ $studentAddress }}</td>
                    </tr>
                @endif
                @if($val('show_student_phone', true))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Contact No.') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $textPrimary) }} padding: 1px 0; vertical-align: top;">{{ $studentPhone }}</td>
                    </tr>
                @endif
                @if($val('show_national_id', true))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('National ID') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $textPrimary) }} padding: 1px 0; vertical-align: top;">{{ $nationalId }}</td>
                    </tr>
                @endif
                @if($val('show_admission_no', false))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Admission No') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $textPrimary) }} padding: 1px 0; vertical-align: top;">{{ $student->admission_number }}</td>
                    </tr>
                @endif
                @if($val('show_expiry', true))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Expiry') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $textPrimary) }} padding: 1px 0; vertical-align: top;">{{ $expiry }}</td>
                    </tr>
                @endif
                @if($val('custom_metadata_text'))
                    <tr>
                        <td style="width: 28%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $textSecondary) }} font-weight: 600; padding: 1px 3px 1px 0; vertical-align: top;">{{ $val('custom_metadata_text') }}</td>
                        <td style="width: 4%; text-align: center; color: {{ $textMuted }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 68%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $accentColor) }} font-weight: 600; padding: 1px 0; vertical-align: top;">{{ $val('custom_metadata_value') }}</td>
                    </tr>
                @endif
            </table>

            {{-- QR --}}
            @if($val('show_qr', true))
                <div style="position: absolute; bottom: 0; right: 0; text-align: center;">
                    <img src="{{ $qr }}" style="width: {{ $px('qr_size', 58) }}px; height: {{ $px('qr_size', 58) }}px; border: 1px solid #cbd5e1; border-radius: 3px; padding: 2px; background: #fff; box-sizing: border-box;">
                    <div style="{{ $font('qr_caption', 5, $textMuted) }} font-weight: 700; letter-spacing: 0.5px; line-height: 1.1; margin-top: 1px;">
                        <strong>{{ $id }}</strong>
                    </div>
                </div>
            @endif

            {{-- Barcode --}}
            @if($barcode)
                <div style="position: absolute; bottom: 0; left: 0; right: {{ $px('qr_size', 58) + 12 }}px; text-align: center;">
                    <img src="{{ $barcode }}" style="max-width: 100%; height: auto;">
                    <div style="font-family: 'Courier New', monospace; font-size: 5px; letter-spacing: 1px; margin-top: 1px; font-weight: 700; color: {{ $textMuted }};">{{ $id }}</div>
                </div>
            @endif

            <div style="clear: both;"></div>
        </div>
        <div style="clear: both;"></div>
    </div>

    {{-- FOOTER — contact details only here (not duplicated at top) --}}
    <div style="position: absolute; bottom: 0; left: 0; width: {{ $canvasW }}px; height: {{ $footerH }}px; z-index: 1; background: {{ $footerBg }}; color: {{ $footerText }}; text-align: center; padding: 0 10px; box-sizing: border-box; display: table; table-layout: fixed; line-height: 1.15;">
        <div style="display: table-cell; vertical-align: middle;">
            @if($showContactDetails && $footerContactParts)
                @if($contactLineMode === 'stacked')
                    @foreach($footerContactParts as $part)
                        <div style="font-size: {{ $px('contact_font_size', 7) }}px; letter-spacing: 0.5px; text-transform: uppercase;">@if(str_contains($part, '@'))<span style="font-size: {{ max(5, (float) $px('contact_font_size', 7) - 1) }}px; text-transform: lowercase;">{{ $part }}</span>@else{{ $part }}@endif</div>
                    @endforeach
                @else
                    <div style="font-size: {{ $px('contact_font_size', 7) }}px; letter-spacing: 0.5px; text-transform: uppercase;">{{ implode(' • ', $footerContactParts) }}</div>
                @endif
            @endif
        </div>
    </div>

</div>
