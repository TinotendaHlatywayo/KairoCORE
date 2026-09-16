{{-- Classic Academic (Double Border) ID Card Template --}}
{{-- DomPDF-compatible: tables only, no Grid/flex/gap/aspect-ratio/clip-path. Same data path as the live preview and PNG export. --}}
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
        return 'font-family: '.e($val($prefix.'_font_family', 'serif')).'; font-size: '.max(5, (float) $val($prefix.'_font_size', $size)).'px; color: '.e($val($prefix.'_color', $color)).'; font-weight: '.($val($prefix.'_is_bold', false) ? '700' : '400').'; font-style: '.($val($prefix.'_is_italic', false) ? 'italic' : 'normal').';';
    };

    $schoolAttrs = $school?->getAttributes() ?? [];
    $schoolName = $val('custom_school_name') ?: ($school?->name ?? 'School Name');
    $motto = $val('custom_school_motto') ?: ($school?->motto ?? 'Excellence In Education');
    $contactAddress = $val('contact_address') ?: ($schoolAttrs['physical_address'] ?? '');
    $contactPhone = $val('contact_phone') ?: ($schoolAttrs['phone'] ?? $schoolAttrs['phone_number'] ?? '');
    $contactEmail = $val('contact_email') ?: ($schoolAttrs['email_address'] ?? $schoolAttrs['email'] ?? '');
    $contactWebsite = (string) school_website_url($school, $val('contact_website'));
    if ($contactWebsite) {
        $contactWebsite = preg_replace('#^https?://#', '', $contactWebsite);
    }

    $showContactDetails = $val('show_contact_details', true);
    $contactLineMode = $val('contact_line_mode', 'single');
    $footerContactParts = [];
    if ($showContactDetails) {
        if ($val('show_contact_address', true) && $contactAddress) $footerContactParts[] = $contactAddress;
        if ($val('show_contact_phone', true) && $contactPhone) $footerContactParts[] = $contactPhone;
        if ($val('show_contact_email', true) && $contactEmail) $footerContactParts[] = strtolower($contactEmail);
        if ($val('show_contact_website', true) && $contactWebsite) $footerContactParts[] = $contactWebsite;
    }

    // Logo
    $logoPath = $val('logo_path') ?: \Modules\Admin\Models\SystemSetting::get('branding', 'branding_logo_path', '');
    $logoData = id_card_file_data_uri($logoPath) ?: id_card_file_data_uri($schoolAttrs['logo_path'] ?? null);
    if (! $logoData && is_file(public_path('images/id-card-default-logo.png'))) {
        $logoData = id_card_file_data_uri('images/id-card-default-logo.png');
    }

    // Photo
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
    $studentAddress = id_card_short_address($student->physical_address);
    $studentPhone = $student->phone ?: 'N/A';
    $nationalId = $student->national_id ?: 'N/A';

    // Classic Academic palette
    $primaryColor = $val('primary_color', '#92400e');
    $primaryDark = $val('primary_dark', '#78350f');
    $accentColor = $val('accent_color', '#b45309');
    $textPrimary = $val('text_primary', '#78350f');
    $textMuted = $val('text_muted', '#a8a29e');
    $headerText = $val('header_text_color', '#fffbeb');
    $canvasColor = e($val('canvas_bg_color', '#fffbeb'));
    $footerBg = $val('footer_bg', '#78350f');
    $footerText = $val('footer_text', '#fef3c7');

    // Double border: outer configured border + interior frame line with a gutter
    $borderW = (int) $px('card_border_width', 5);
    $frameOff = $borderW + 4;
    $frameW = $canvasW - 2 * $frameOff;
    $frameH = $canvasH - 2 * $frameOff;
    $headerH = $orientation === 'portrait' ? 84 : 64;
    $footerH = $orientation === 'portrait' ? 26 : 22;
    $mainH = $frameH - $headerH - $footerH;

    $logoBox = $orientation === 'portrait' ? 58 : 50;
    $schoolNameContactW = $orientation === 'portrait' ? 92 : 120;
    $schoolNameCellW = $frameW - $schoolNameContactW - ($logoBox + 4);
    $schoolNameLen = max(1, mb_strlen($schoolName));
    $schoolNameFontPx = (int) floor(($schoolNameCellW - 12) / (1.0 * $schoolNameLen));
    $schoolNameFontPx = $orientation === 'portrait' ? max(5, min(9, $schoolNameFontPx)) : max(8, min(12, $schoolNameFontPx));
    $typeW = $orientation === 'portrait' ? 64 : 104;
    $photoCellW = $orientation === 'portrait' ? 27 : 20;
    $showQr = $val('show_qr', true);
    $qrCellW = $orientation === 'portrait' ? 30 : 30;

    $verifyUrl = route('card.verify', ['hash' => hash_hmac('sha256', (string) $id, config('app.key'))]);
    $qrDataText = "STUDENT IDENTITY CARD\nSchool: {$schoolName}\nID: {$id}\nName: {$student->full_name}\nClass: {$classLabel}\nDOB: {$dob}\nNational ID: {$nationalId}\nVerify: {$verifyUrl}";
    $qr = id_card_generate_qr($qrDataText, (int) $px('qr_size', 58) * 2);

    $barcode = null;
    if ($val('show_barcode', false)) {
        try { $barcode = 'data:image/png;base64,'.base64_encode((new \Picqer\Barcode\BarcodeGeneratorPNG())->getBarcode($id, \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 2, (int) $px('barcode_height', 30))); } catch (\Throwable $e) {}
    }
@endphp

<div class="id-card-classic id-card {{ $orientation }}" style="width: {{ $canvasW }}px; height: {{ $canvasH }}px; position: relative; margin: 0 auto; box-sizing: border-box; font-family: Georgia, 'Times New Roman', serif; border: {{ $borderW }}px solid {{ e($val('card_border_color', '#92400e')) }}; overflow: hidden; background: {{ $canvasColor }};">

    {{-- Inner content background (fill inside the frame line) --}}
    <div style="position: absolute; top: {{ $frameOff }}px; left: {{ $frameOff }}px; width: {{ $frameW }}px; height: {{ $frameH }}px; background: {{ $canvasColor }}; z-index: 0;"></div>

    {{-- HEADER --}}
    <div style="position: absolute; top: {{ $frameOff }}px; left: {{ $frameOff }}px; width: {{ $frameW }}px; height: {{ $headerH }}px; background: {{ e($val('header_bg_color', $primaryColor)) }}; z-index: 2;">
        <table style="width: 100%; height: {{ $headerH }}px; border-collapse: collapse; border-spacing: 0; table-layout: fixed;">
            <tr>
                {{-- School name & motto (centered, single line, sized so every letter is visible) --}}
                <td style="vertical-align: middle; padding: 0 4px 0 6px; text-align: center;">
                    @if($val('show_school_header', true))
                        <div style="{{ $font('school_name', $orientation === 'portrait' ? 10 : 11, $headerText) }} font-size: {{ $schoolNameFontPx }}px !important; width: 100%; min-width: 100%; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-bottom: 1px solid {{ $accentColor }}; padding-bottom: 1px;">
                            {{ $schoolName }}
                        </div>
                    @endif
                    @if($val('show_school_motto', true))
                        <div style="{{ $font('motto', $orientation === 'portrait' ? 6.5 : 7, $headerText) }} width: 100%; line-height: 1.1; margin-top: 1px; opacity: 0.9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $motto }}
                        </div>
                    @endif
                </td>
                {{-- School contact details, placed just before (left of) the logo. Each on its own line within the logo height. --}}
                <td style="width: {{ $orientation === 'portrait' ? 92 : 120 }}px; vertical-align: middle; padding: 2px 4px; font-size: {{ $orientation === 'portrait' ? 5.5 : 6 }}px; line-height: 1.2; color: {{ $headerText }};">
                    @if($val('show_contact_email', true) && $contactEmail)<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: lowercase;">{{ $contactEmail }}</div>@endif
                    @if($val('show_contact_phone', true) && $contactPhone)<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contactPhone }}</div>@endif
                    @if($val('show_contact_website', true) && $contactWebsite)<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contactWebsite }}</div>@endif
                    @if($val('show_contact_address', true) && $contactAddress)<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contactAddress }}</div>@endif
                </td>
                {{-- Logo pushed to the right corner --}}
                <td style="width: {{ $logoBox + 4 }}px; text-align: right; vertical-align: middle; padding-right: 4px;">
                    @if($val('show_school_logo', true) && $logoData)
                        <img src="{{ $logoData }}" style="width: {{ $logoBox }}px; height: {{ $logoBox }}px;" alt="">
                    @elseif($val('show_school_logo', true))
                        <div style="width: {{ $logoBox }}px; height: {{ $logoBox }}px; line-height: {{ $logoBox }}px; border: 1px solid {{ $accentColor }}; border-radius: 50%; color: {{ $headerText }}; font-size: 20px; font-weight: 700; display: inline-block;">
                            {{ mb_substr($schoolName, 0, 1) }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

        {{-- MAIN CONTENT --}}
        <div style="position: absolute; top: {{ $frameOff + $headerH }}px; left: {{ $frameOff }}px; width: {{ $frameW }}px; height: {{ $mainH }}px; background: #ffffff; z-index: 2;">
            <table style="width: 100%; height: {{ $mainH }}px; border-collapse: collapse; border-spacing: 0; table-layout: fixed;">
                <tr>
                    {{-- PHOTO --}}
                    @if($val('show_photo', true) && $photoData)
                        <td style="width: {{ $photoCellW }}%; vertical-align: top; text-align: center; padding: 10px 0 0 9px;">
                            <div style="display: inline-block; padding: 2px; border: 2px solid {{ $primaryColor }}; background: #ffffff; line-height: 0;">
                                <img src="{{ $photoData }}" style="width: {{ $orientation === 'portrait' ? 72 : 78 }}px; height: {{ $orientation === 'portrait' ? 98 : 104 }}px; border-radius: {{ $px('photo_rounded_corners', 4) }}px; box-sizing: border-box;">
                            </div>
                            @if($val('show_photo_caption', true))
                                <div style="font-family: Georgia, 'Times New Roman', serif; font-size: 6.5px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: {{ $primaryDark }}; margin-top: 4px; line-height: 1.1;">
                                    {{ $val('photo_caption_text', 'STUDENT') }}
                                </div>
                            @endif
                        </td>
                    @endif

                    {{-- IDENTITY + QR --}}
                    <td style="vertical-align: top; padding: {{ $orientation === 'portrait' ? '9px 9px 9px 7px' : '7px 9px 7px 6px' }};">
                        <table style="width: 100%; border-collapse: collapse; border-spacing: 0;">
                            <tr>
                                <td style="vertical-align: middle; height: {{ $orientation === 'portrait' ? 24 : 26 }}px;">
                                    @if($val('show_name', true))
                                        <div style="{{ $font('name', $orientation === 'portrait' ? 14 : 15, $primaryDark) }} text-transform: uppercase; letter-spacing: 0.4px; line-height: 1.15; padding-bottom: 3px; border-bottom: 1.5px solid {{ $accentColor }};">
                                            {{ $student->full_name }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; width: {{ $showQr || $barcode ? (100 - $qrCellW) : 100 }}%; padding-top: 2px;">
                                    <table style="width: 100%; border-collapse: collapse; border-spacing: 0; font-size: {{ $orientation === 'portrait' ? 9 : 9 }}px; line-height: 1.45;">
                @if($val('show_student_id', true))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Student ID') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $val('value_color', '#78350f')) }} font-weight: 700; padding: 1px 0; vertical-align: top;">{{ $id }}</td>
                    </tr>
                @endif
                @if($val('show_class', true))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Class') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $val('value_color', '#78350f')) }} font-weight: 700; padding: 1px 0; vertical-align: top;">{{ $classLabel }}</td>
                    </tr>
                @endif
                @if($val('show_dob', true))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Date of Birth') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $val('value_color', '#78350f')) }} padding: 1px 0; vertical-align: top;">{{ $dob }}</td>
                    </tr>
                @endif
                @if($val('show_address', true))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Address') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $val('value_color', '#78350f')) }} padding: 1px 0; vertical-align: top;">{{ $studentAddress }}</td>
                    </tr>
                @endif
                @if($val('show_student_phone', true))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Contact No.') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 7 : 8, $val('value_color', '#78350f')) }} padding: 1px 0; vertical-align: top;">{{ $studentPhone }}</td>
                    </tr>
                @endif
                @if($val('show_national_id', true))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('National ID') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $val('value_color', '#78350f')) }} padding: 1px 0; vertical-align: top;">{{ $nationalId }}</td>
                    </tr>
                @endif
                @if($val('show_expiry', true))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Expiry') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $val('value_color', '#78350f')) }} padding: 1px 0; vertical-align: top;">{{ $expiry }}</td>
                    </tr>
                @endif
                @if($val('show_admission_no', false))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ __('Admission No') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $val('value_color', '#78350f')) }} padding: 1px 0; vertical-align: top;">{{ $student->admission_number }}</td>
                    </tr>
                @endif
                @if($val('custom_metadata_text'))
                    <tr>
                        <td style="width: 30%; {{ $font('label', $orientation === 'portrait' ? 8 : 9, $val('label_color', '#92400e')) }} font-weight: 700; padding: 1px 3px 1px 0; vertical-align: top;">{{ $val('custom_metadata_text') }}</td>
                        <td style="width: 3%; text-align: center; color: {{ $primaryColor }}; padding: 1px 0; vertical-align: top;">:</td>
                        <td style="width: 67%; {{ $font('value', $orientation === 'portrait' ? 8 : 9, $val('value_color', '#78350f')) }} font-weight: 700; padding: 1px 0; vertical-align: top;">{{ $val('custom_metadata_value') }}</td>
                    </tr>
                @endif
            </table>
                </td>
                @if($showQr)
                    <td style="vertical-align: bottom; text-align: center; width: {{ $qrCellW }}%; padding-left: 5px;">
                        <img src="{{ $qr }}" style="width: {{ $px('qr_size', 58) }}px; height: {{ $px('qr_size', 58) }}px; border: 1px solid {{ $primaryColor }}; border-radius: 2px; padding: 1px; background: #ffffff; box-sizing: border-box;">
                        <div style="font-family: Georgia, 'Times New Roman', serif; font-size: 5.5px; font-weight: 700; letter-spacing: 0.5px; color: {{ $primaryDark }}; line-height: 1.2; margin-top: 1px;">
                            <strong>{{ $id }}</strong>
                        </div>
                    </td>
                @elseif($barcode)
                    <td style="vertical-align: middle; text-align: center; width: {{ $qrCellW }}%; padding-left: 5px;">
                        <img src="{{ $barcode }}" style="width: 100%;">
                        <div style="font-family: 'Courier New', monospace; font-size: 5px; letter-spacing: 1px; margin-top: 1px; font-weight: 700; color: {{ $primaryDark }};">{{ $id }}</div>
                    </td>
                @endif
            </tr>
        </table>
    </td>
    </tr>
    </table>
    </div>

    {{-- FOOTER STRIP --}}
    <div style="position: absolute; bottom: {{ $frameOff }}px; left: {{ $frameOff }}px; width: {{ $frameW }}px; height: {{ $footerH }}px; background: {{ $footerBg }}; color: {{ $footerText }}; border-top: 1px solid {{ $accentColor }}; box-sizing: border-box; text-align: center; font-size: 6.5px; line-height: {{ $footerH }}px; text-transform: uppercase; letter-spacing: 1px; z-index: 2;">
        Powered by Kairo CORE
    </div>

    {{-- Inner frame line: decorative double-border inset (behind content, visible in gutter) --}}
    <div style="position: absolute; top: {{ $frameOff }}px; left: {{ $frameOff }}px; width: {{ $frameW }}px; height: {{ $frameH }}px; border: 2px solid {{ $primaryColor }}; box-sizing: border-box; z-index: 1;"></div>

</div>