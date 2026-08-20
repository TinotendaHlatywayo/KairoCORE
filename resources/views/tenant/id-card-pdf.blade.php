<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student ID Card - {{ $student->first_name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .id-card {
            width: 320px;
            height: 480px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e1e8ed;
            position: relative;
            text-align: center;
        }
        .card-header {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            color: white;
            padding: 20px 15px;
            position: relative;
        }
        .school-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.8;
        }
        .avatar-container {
            margin-top: -45px;
            position: relative;
            z-index: 10;
        }
        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid #ffffff;
            object-fit: cover;
            background: #e1e8ed;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card-body {
            padding: 20px;
        }
        .student-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a202c;
            margin: 10px 0 5px 0;
        }
        .student-class {
            font-size: 13px;
            color: #15803d;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            text-align: left;
            margin-bottom: 20px;
            border-top: 1px solid #f0f4f8;
            padding-top: 15px;
        }
        .detail-item {
            font-size: 11px;
        }
        .detail-label {
            color: #718096;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            margin-bottom: 2px;
        }
        .detail-value {
            color: #2d3748;
            font-weight: 600;
        }
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 15px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .barcode {
            font-family: 'Libre Barcode 39', monospace;
            font-size: 32px;
            margin-top: 5px;
            color: #1a202c;
        }
    </style>
    <!-- Barcode web font (will fallback to clean monospace locally) -->
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
</head>
<body>

    <div class="id-card">
        <div class="card-header">
            <div class="school-name">{{ $school->name }}</div>
            <div class="card-title">{{ __('Student Identity Card') }}</div>
        </div>
        
        <div class="avatar-container">
            <!-- Pulls student photo, or default male/female fallback silhouette -->
            <img class="avatar" src="{{ student_photo_src($student) }}" alt="Avatar">
        </div>

        <div class="card-body">
            <div class="student-name">{{ $student->first_name }} {{ $student->last_name }}</div>
            <div class="student-class">
                {{ $student->currentEnrollment ? "{$student->currentEnrollment->course->name} {$student->currentEnrollment->section->name}" : 'Unassigned' }}
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">{{ __('Admission No.') }}</div>
                    <div class="detail-value">{{ $student->admission_number }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">{{ __('Gender') }}</div>
                    <div class="detail-value">{{ ucfirst($student->gender) }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">{{ __('Date of Birth') }}</div>
                    <div class="detail-value">{{ $student->date_of_birth->format('d M Y') }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">{{ __('Issued Date') }}</div>
                    <div class="detail-value">{{ date('M Y') }}</div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div>
                <!-- Generates a scannable pseudo-barcode matching their ID number -->
                <div class="barcode">*{{ $student->admission_number }}*</div>
                <span style="font-size: 8px; color: #a0aec0; text-transform: uppercase; font-weight: bold; display: block; margin-top: 2px;">{{ __('Barcode ID Reader') }}</span>
            </div>
        </div>
    </div>

</body>
</html>