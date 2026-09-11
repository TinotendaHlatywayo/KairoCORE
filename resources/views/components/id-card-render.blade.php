{{-- Shared card canvas for the designer, PDF and PNG exports. --}}
@php
    $school = $school ?? current_tenant();
    $tpl = $template ?? \App\Http\Controllers\StudentCardPrintController::resolveTemplateForStudent($student, $school?->id ?? 0);
    $cfg = $tpl->layout_config ?? [];
    $orientation = $tpl->orientation ?? 'landscape';
    $designTheme = $cfg['design_theme'] ?? 'premium';
@endphp

@if($designTheme === 'professional')
    @include('components.id-card-professional', ['student' => $student, 'template' => $tpl, 'school' => $school, 'cropMarks' => $cropMarks ?? false])
@else
    {{-- Legacy absolute-positioning template (premium, classic, modern, etc.) --}}
    @php
        $canvasW = $orientation === 'landscape' ? 480 : 300;
        $canvasH = $orientation === 'landscape' ? 300 : 480;
        $val = fn (string $key, mixed $default = null) => $cfg[$key] ?? $default;
        $pct = fn (string $key, float $default) => max(0, min(100, (float) $val($key, $default)));
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
        $logoPath = $val('logo_path') ?: \Modules\Admin\Models\SystemSetting::get('branding', 'branding_logo_path', '');
        $logoData = id_card_file_data_uri($logoPath) ?: id_card_file_data_uri($schoolAttrs['logo_path'] ?? null);
        if (! $logoData && is_file(public_path('images/id-card-default-logo.png'))) $logoData = id_card_file_data_uri('images/id-card-default-logo.png');
        $photoPath = is_string($student->photo_path ?? null) ? $student->photo_path : null;
        $photoData = $photoPath ? id_card_file_data_uri($photoPath) : null;
        if (! $photoData) { $fallback = student_photo_src($student); $photoData = is_file($fallback) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($fallback)) : null; }
        $background = 'background-color: '.e($val('canvas_bg_color', '#ffffff')).';';
        $opacity = max(0, min(100, (float) $val('canvas_bg_watermark_opacity', 100))) / 100;
        $mode = $val('bg_mode', 'solid');
        if ($mode === 'gradient') {
            $gradient = id_card_gradient_data_uri((string) $val('canvas_bg_color', '#ffffff'), (string) $val('canvas_gradient_end_color', '#e0e7ff'), $canvasW, $canvasH, 1.0);
            $background .= $gradient ? " background-image:url('{$gradient}'); background-size:cover;" : ' background-image: linear-gradient(135deg, '.e($val('canvas_bg_color', '#ffffff')).', '.e($val('canvas_gradient_end_color', '#e0e7ff')).');';
        }
        if ($mode === 'image' && ($image = id_card_file_data_uri($tpl->background_path ?? null))) $background .= " background-image:url('{$image}'); background-size:cover; background-position:center;";
        if ($mode === 'logo' && $logoData) $background .= " background-image:url('{$logoData}'); background-size:contain; background-repeat:no-repeat; background-position:center;";
        $classLabel = $student->currentEnrollment?->section?->full_name ?? $student->currentEnrollment?->course?->name ?? 'Class';
        $logoBgAlpha = max(0, min(100, (float) $val('logo_bg_transparency', 50))) / 100;
        $logoBgStyle = '';
        if ($val('logo_bg_mode', 'none') === 'gradient') {
            $logoBgStyle = 'background: linear-gradient(135deg, '.id_card_hex_to_rgba((string) $val('logo_bg_color', '#ffffff'), $logoBgAlpha).', '.id_card_hex_to_rgba((string) $val('logo_bg_gradient_end', '#e0e7ff'), $logoBgAlpha).');';
        } elseif ($val('logo_bg_mode', 'none') === 'solid') {
            $logoBgStyle = 'background-color: '.id_card_hex_to_rgba((string) $val('logo_bg_color', '#ffffff'), $logoBgAlpha).';';
        }
        $id = $student->student_id_number ?: $student->admission_number ?: 'N/A';
        $expiry = $student->resolved_card_expiry?->format('d M Y') ?? 'N/A';
        $dob = $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') : 'N/A';
        $boarding = ucwords(str_replace('_', ' ', $student->boarding_status ?? 'day_scholar'));
        $studentAddress = \Illuminate\Support\Str::limit($student->physical_address ?: 'Borrowdale, Harare', 42);
        $studentPhone = $student->phone ?: 'N/A';
        $photoW = $canvasW * $pct('photo_width', 26) / 100;
        $photoH = $canvasH * $pct('photo_height', 34) / 100;
        $verify = route('card.verify', ['hash' => hash_hmac('sha256', (string) $id, config('app.key'))]);
        $qrDataText = "STUDENT IDENTITY CARD\n"
            . "School: " . $schoolName . "\n"
            . "Student ID: " . $id . "\n"
            . "Name: " . $student->full_name . "\n"
            . "Class: " . $classLabel . "\n"
            . "DOB: " . $dob . "\n"
            . "National ID: " . ($student->national_id ?: 'N/A') . "\n"
            . "Verify: " . $verify;
        $qr = id_card_generate_qr($qrDataText, (int) $px('qr_size', 58) * 2);
        $barcode = null;
        if ($val('show_barcode', false)) { try { $barcode = 'data:image/png;base64,'.base64_encode((new \Picqer\Barcode\BarcodeGeneratorPNG())->getBarcode($id, \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 2, (int) $px('barcode_height', 30))); } catch (\Throwable $e) {} }
    @endphp
    <div class="id-card {{ $orientation }}{{ !empty($cropMarks) ? ' crop-marks' : '' }}" style="border: {{ (int) $px('card_border_width', 3) }}px solid {{ e($val('card_border_color', '#1e3a8a')) }}; border-radius: 12px; background: {{ e($val('canvas_bg_color', '#ffffff')) }};">
        {{-- The opacity belongs to this layer only: card content remains fully opaque. --}}
        <div class="card-background-layer" style="{{ $background }} opacity: {{ $opacity }};"></div>
        @if($val('show_school_header', true))<div class="card-header-bar" style="height: {{ $orientation === 'landscape' ? 52 : 58 }}px; background: {{ e($val('header_bg_color', '#0f172a')) }};"></div>@endif
        @if($val('show_school_logo', true))<div class="positioned-element logo-box" style="left: {{ $pct('logo_x', 6) }}%; top: {{ $pct('logo_y', 6) }}%; width: {{ $px('logo_width', 26) }}px; height: {{ $px('logo_height', 26) }}px; padding: {{ (int) $px('logo_padding', 2) }}px; border-radius: {{ (int) $px('logo_rounded_corners', 6) }}px; {{ $logoBgStyle }}">@if($logoData)<img class="logo-img" src="{{ $logoData }}" alt="School logo" style="object-fit: {{ e($val('logo_fit', 'cover')) }};">@else<div class="logo-letter">{{ mb_substr($schoolName, 0, 1) }}</div>@endif</div>@endif
        @if($val('show_school_header', true))<div class="positioned-element school-name" style="left: {{ $pct('school_name_x', 16) }}%; top: {{ $pct('school_name_y', 4) }}%; width: {{ max(10, 100 - $pct('school_name_x', 16) - 4) }}%; {{ $font('school_name', 16, $val('header_text_color', '#fbbf24')) }}">{{ $schoolName }}</div>@endif
        @if($val('show_school_motto', true))<div class="positioned-element school-motto" style="left: {{ $pct('motto_x', 36) }}%; top: {{ $pct('motto_y', 7) }}%; width: {{ max(10, 100 - $pct('motto_x', 36) - 3) }}%; {{ $font('motto', 10, '#cbd5e1') }}">{{ $motto }}</div>@endif
        @if($val('show_photo', true) && $photoData)<img class="positioned-element student-photo" src="{{ $photoData }}" alt="Student photo" style="left: {{ $pct('photo_x', 6) }}%; top: {{ $pct('photo_y', 24) }}%; width: {{ $photoW }}px; height: {{ $photoH }}px; border-radius: {{ $px('photo_rounded_corners', 8) }}px; border: {{ $px('photo_border_width', 2) }}px solid {{ e($val('photo_border_color', '#fbbf24')) }};">@endif
        @if($val('show_name', true))<div class="positioned-element student-name" style="left: {{ $pct('name_x', 10) }}%; top: {{ $pct('name_y', 45) }}%; {{ $font('name', 22, '#1e3a8a') }}">{{ $student->full_name }}</div>@endif
        @if($val('show_class', true))<div class="positioned-element class-chip" style="left: {{ $pct('class_x', 10) }}%; top: {{ $pct('class_y', 52) }}%; {{ $font('class', 13, '#64748b') }}">{{ $classLabel }}</div>@endif
        <div class="positioned-element metadata-block" style="left: {{ $pct('meta_x', 34) }}%; top: {{ $pct('meta_y', 68) }}%; width: {{ max(8, 100 - $pct('meta_x', 34) - 4) }}%; {{ $font('meta', 10, '#334155') }}">
            @if($val('show_national_id', true))<div><strong>National ID:</strong> {{ $student->national_id ?: 'N/A' }}</div>@endif
            @if($val('show_dob', true))<div><strong>DOB:</strong> {{ $dob }}</div>@endif
            <div><strong>Status:</strong> {{ $boarding }}</div>
            @if($val('show_address', true))<div><strong>Address:</strong> {{ $studentAddress }}</div>@endif
            @if($val('show_student_phone', true))<div><strong>Tel:</strong> {{ $studentPhone }}</div>@endif
            @if($val('show_admission_no', false))<div><strong>Admission:</strong> {{ $student->admission_number }}</div>@endif
            @if($val('custom_metadata_text'))<div>{{ $val('custom_metadata_text') }}</div>@endif
        </div>
        @if($val('show_qr', true))<div class="positioned-element qr-cell" style="left: {{ $pct('qr_x', 10) }}%; top: {{ $pct('qr_y', 70) }}%;"><img class="qr-img" src="{{ $qr }}" style="width: {{ $px('qr_size', 58) }}px; height: {{ $px('qr_size', 58) }}px;" alt="Verification QR"><div class="qr-caption"><strong>{{ $id }}</strong><br>Expiry: {{ $expiry }}</div></div>@endif
        @if($barcode)<div class="positioned-element barcode-wrap" style="left: {{ $pct('barcode_x', 10) }}%; top: {{ $pct('barcode_y', 82) }}%; width: {{ $pct('barcode_width', 80) }}%;"><img class="barcode-img" src="{{ $barcode }}" alt="{{ $id }}"></div>@endif
        @if($val('show_contact_details', true))
            @php
                $showAddr = $val('show_contact_address', true);
                $showPh = $val('show_contact_phone', true);
                $showEm = $val('show_contact_email', true);
                $showWeb = $val('show_contact_website', true);
                $contactLineMode = $val('contact_line_mode', 'single');
                $contactLines = [];
                if ($showAddr && $contactAddress) $contactLines[] = $contactAddress;
                if ($showPh && $contactPhone) $contactLines[] = $contactPhone;
                if ($showEm && $contactEmail) $contactLines[] = strtolower($contactEmail);
                if ($showWeb && $contactWebsite) $contactLines[] = $contactWebsite;
            @endphp
            @if($contactLines)
                <div class="positioned-element contact-footer" style="left: {{ $pct('contact_x', 34) }}%; top: {{ $pct('contact_y', 78) }}%; width: {{ min(100 - $pct('contact_x', 34), $pct('contact_width', 62)) }}%; font-size: {{ $px('contact_font_size', 9) }}px; color: {{ e($val('contact_color', '#475569')) }}; line-height: 1.2;">
                    @if($contactLineMode === 'stacked')
                        @foreach($contactLines as $line)
                            <div>@if(str_contains($line, '@'))<span style="font-size: {{ max(6, (float) $px('contact_font_size', 9) - 1) }}px; text-transform: lowercase;">{{ $line }}</span>@else{{ $line }}@endif</div>
                        @endforeach
                    @else
                        <div>
                            @foreach($contactLines as $i => $line)
                                @if($i > 0)<span> • </span>@endif
                                @if(str_contains($line, '@'))<span style="font-size: {{ max(6, (float) $px('contact_font_size', 9) - 1) }}px; text-transform: lowercase;">{{ $line }}</span>@else{{ $line }}@endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        @endif
        @foreach((array) $val('custom_texts', []) as $text) @if(!empty($text['text']))<div class="positioned-element custom-text-line" style="left: {{ max(0, min(100, (float) ($text['x'] ?? 10))) }}%; top: {{ max(0, min(100, (float) ($text['y'] ?? 65))) }}%; font-family: {{ e($text['font_family'] ?? 'sans-serif') }}; font-size: {{ max(5, (float) ($text['font_size'] ?? 10)) }}px; color: {{ e($text['color'] ?? '#000000') }}; font-weight: {{ !empty($text['is_bold']) ? '700' : '400' }}; font-style: {{ !empty($text['is_italic']) ? 'italic' : 'normal' }};">{{ $text['text'] }}</div>@endif @endforeach
    </div>
@endif