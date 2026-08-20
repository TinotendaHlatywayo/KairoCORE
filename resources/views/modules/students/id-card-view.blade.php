<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Student ID Card') }}</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica', sans-serif; background-color: #f1f5f9; }
        
        /* CARD CANVAS BODY */
        .id-card {
            position: relative;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            background-size: cover;
            background-position: center;
        }
        
        .portrait { width: 320px; height: 500px; }
        .landscape { width: 500px; height: 320px; }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            width: 70%;
            pointer-events: none;
        }

        .school-header {
            text-align: center;
            background: rgba(30, 58, 138, 0.95);
            color: #ffffff;
            padding: 10px;
        }
        .school-name { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .school-motto { font-size: 7px; font-style: italic; color: #93c5fd; margin-top: 2px; }

        .absolute-element { position: absolute; }
        
        .student-photo {
            border: 2px solid #1e3a8a;
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        .barcode-container {
            text-align: center;
            font-size: 8px;
            font-family: monospace;
        }
        .barcode-svg svg { width: 100%; height: 100%; }

        .powered-seal {
            position: absolute;
            bottom: 4px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 6px;
            color: #94a3b8;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Clean CSS Variables to handle dynamic positioning safely */
        .photo-position {
            left: var(--photo-x);
            top: var(--photo-y);
            width: var(--photo-width);
            height: var(--photo-height);
        }
        .name-position {
            left: var(--name-x);
            top: var(--name-y);
            width: 80%;
            text-align: center;
            font-size: var(--name-font-size);
            color: var(--name-color);
        }
        .qr-position {
            left: var(--qr-x);
            top: var(--qr-y);
        }
        .qr-img {
            width: var(--qr-size);
            height: var(--qr-size);
            border: 1px solid #cbd5e1;
            padding: 2px;
        }
        .barcode-position {
            left: var(--barcode-x);
            top: var(--barcode-y);
            width: var(--barcode-width);
        }
        .barcode-svg {
            height: var(--barcode-height);
        }
    </style>
</head>
<body>

    <!-- CSS variables declared inside inline HTML styles to silence CSS validation warnings -->
    <div class="id-card {{ $template->orientation }}" 
         style="background-image: url('{{ $template->background_path ? public_path($template->background_path) : '' }}');
                --photo-x: {{ $template->layout_config['photo_x'] ?? 35 }}%;
                --photo-y: {{ $template->layout_config['photo_y'] ?? 15 }}%;
                --photo-width: {{ $template->layout_config['photo_width'] ?? 30 }}%;
                --photo-height: {{ $template->layout_config['photo_height'] ?? 25 }}%;
                --name-x: {{ $template->layout_config['name_x'] ?? 10 }}%;
                --name-y: {{ $template->layout_config['name_y'] ?? 45 }}%;
                --name-font-size: {{ $template->layout_config['name_font_size'] ?? 14 }}px;
                --name-color: {{ $template->layout_config['name_color'] ?? '#1e3a8a' }};
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

        <div class="school-header">
            <div class="school-name">{{ $school->name }}</div>
            @if($school->motto)
                <div class="school-motto">"{{ $school->motto }}"</div>
            @endif
        </div>

        <!-- PHOTO -->
        <div class="absolute-element photo-position">
            <img class="student-photo" 
                 src="{{ student_photo_src($student) }}" 
                 style="border-radius: {{ $template->layout_config['photo_rounded_corners'] ?? 8 }}px;">
        </div>

        <!-- STUDENT NAME -->
        <div class="absolute-element name-position" style="font-weight: bold;">
            {{ $student->full_name }}
            <div style="font-size: 9px; color: #64748b; font-weight: normal; margin-top: 3px;">
                Class: {{ $student->currentEnrollment?->section?->full_name ?? 'Not Enrolled' }}
            </div>
        </div>

        <!-- METADATA -->
        <div class="absolute-element" style="left: 10%; top: 58%; width: 80%; font-size: 8px; line-height: 1.4; color: #334155;">
            <strong>{{ __('Student ID:') }}</strong> {{ $student->student_id_number }}<br/>
            <strong>{{ __('Admission No:') }}</strong> {{ $student->admission_number }}<br/>
            <strong>{{ __('Expiry Date:') }}</strong> {{ $student->card_expiry_date ? $student->card_expiry_date->format('d-M-Y') : 'N/A' }}
        </div>

        <!-- QR CODE -->
        <div class="absolute-element qr-position">
            <img class="qr-img" src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ route('card.verify', ['hash' => hash_hmac('sha256', $student->student_id_number, config('app.key'))]) }}">
        </div>

        <!-- BARCODE -->
        <div class="absolute-element barcode-position barcode-container">
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

        <div class="powered-seal">
            {{ __('Powered by Tinway Technologies') }}
        </div>

    </div>

</body>
</html>