<div class="p-4">
    <style>
        .student-card {
            position: relative;
            display: flex;
            flex-direction: column;
            border-radius: var(--theme-radius, 1rem);
            background: linear-gradient(160deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(20, 184, 166, 0.22);
            box-shadow:
                0 10px 34px -16px var(--status),
                0 1px 3px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .student-card:hover {
            transform: translateY(-5px);
            border-color: #14b8a6;
            box-shadow:
                0 0 0 1.5px rgba(20, 184, 166, 0.25),
                0 0 26px -4px var(--status),
                0 24px 50px -18px var(--status);
        }
        .student-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
            background: linear-gradient(90deg, var(--status), var(--theme-accent, #2dd4bf));
        }
        .student-card .photo-wrap {
            margin: 18px auto 6px auto;
            width: 92px;
            height: 92px;
            border-radius: 9999px;
            padding: 3px;
            background: conic-gradient(from 180deg, var(--status), var(--theme-accent, #2dd4bf), var(--status));
            box-shadow: 0 8px 22px -10px var(--status);
        }
        .student-card .photo-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 9999px;
            object-fit: cover;
            border: 3px solid #ffffff;
            background: #f1f5f9;
        }
        .student-card .card-name {
            font-weight: 800;
            font-size: 1.05rem;
            color: #0f172a;
            text-align: center;
            padding: 0 12px;
            line-height: 1.25;
        }
        .student-card .card-class {
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 3px;
        }
        .student-card .card-meta {
            margin: 12px 16px 10px 16px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 12px;
        }
        .student-card .card-meta .m-label {
            font-size: 0.62rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
        }
        .student-card .card-meta .m-value {
            font-size: 0.78rem;
            font-weight: 600;
            color: #334155;
            word-break: break-word;
        }
        .student-card .card-status {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ffffff;
            background: var(--status);
            border-radius: 9999px;
            padding: 4px 10px;
            box-shadow: 0 4px 12px -4px var(--status);
        }
        .student-card .card-actions {
            display: flex;
            gap: 8px;
            padding: 0 16px 16px 16px;
            margin-top: auto;
        }
        .student-card .card-actions a {
            flex: 1;
            text-align: center;
            font-size: 0.74rem;
            font-weight: 700;
            border-radius: 10px;
            padding: 8px 10px;
            transition: background 0.15s ease, color 0.15s ease;
            text-decoration: none;
        }
        .student-card .card-actions .btn-edit {
            background: var(--sc-primary-50);
            color: var(--sc-primary-600);
        }
        .student-card .card-actions .btn-edit:hover {
            background: var(--sc-primary-600);
            color: #ffffff;
        }
        .student-card .card-actions .btn-view {
            background: #ecfeff;
            color: #0891b2;
        }
        .student-card .card-actions .btn-view:hover {
            background: #0891b2;
            color: #ffffff;
        }
        .student-card .card-actions .btn-print {
            background: #ecfdf5;
            color: #059669;
        }
        .student-card .card-actions .btn-print:hover {
            background: #059669;
            color: #ffffff;
        }
        .student-card .sc-card-select {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 5;
            display: flex;
            align-items: center;
        }
        .student-card .sc-card-select input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #14b8a6;
            cursor: pointer;
        }
        .sc-gender-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 2px 9px;
            border-radius: 9999px;
            line-height: 1.5;
            vertical-align: middle;
        }
        .sc-gender-pill svg {
            width: 0.8rem;
            height: 0.8rem;
        }
        .sc-gender-male {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .sc-gender-female {
            background: #fce7f3;
            color: #be185d;
        }
        .sc-gender-other {
            background: #e2e8f0;
            color: #475569;
        }
        .dark .student-card {
            background: linear-gradient(160deg, #1e293b 0%, #0f172a 100%);
            border-color: rgba(45, 212, 191, 0.18);
            box-shadow:
                0 10px 34px -16px var(--status),
                0 1px 3px rgba(0, 0, 0, 0.35);
        }
        .dark .student-card:hover {
            border-color: #14b8a6;
            box-shadow:
                0 0 0 1.5px rgba(45, 212, 191, 0.25),
                0 0 26px -4px var(--status),
                0 24px 50px -18px var(--status);
        }
        .dark .student-card .card-name { color: #f1f5f9; }
        .dark .student-card .card-meta { border-top-color: #334155; }
        .dark .student-card .card-meta .m-value { color: #cbd5e1; }
        .dark .student-card .photo-wrap img { border-color: #1e293b; }
    </style>

    @if($records->isEmpty())
        <div class="fi-ta-empty-state px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('No students found.') }}
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($records as $student)
                @php
                    $resolvedPhoto = resolve_public_asset_path($student->photo_path ?? null);
                    $fallbackPhoto = ($student->gender === 'female') ? 'images/no_profile_female.jpg' : 'images/no_profile_male.png';
                    $photoUrl = $resolvedPhoto ? asset($resolvedPhoto) : asset($fallbackPhoto);

                    $enrollment = $student->currentEnrollment;
                    $className = $enrollment
                        ? trim(($enrollment->course?->name ?? '') . ' ' . ($enrollment->section?->name ?? '')) ?: 'Unassigned'
                        : 'Unassigned';

                    $statusColor = match ($student->status) {
                        'active' => '#10b981',
                        'suspended' => '#ef4444',
                        'inactive' => '#f59e0b',
                        'graduated' => 'var(--sc-primary-500)',
                        default => '#64748b',
                    };

                    $age = $student->date_of_birth
                        ? \Carbon\Carbon::parse($student->date_of_birth)->age
                        : null;

                    $editUrl = route('filament.app.resources.students.edit', ['record' => $student]);
                    $viewUrl = \App\Filament\App\Resources\StudentResource::getUrl('view', ['record' => $student]);
                    $printUrl = route('students.print-cards', ['ids' => $student->id, 'layout' => 'pvc']);
                @endphp
                <div class="student-card" style="--status: {{ $statusColor }};">
                    <span class="card-status">{{ ucfirst($student->status) }}</span>

                    <span class="sc-card-select">
                        <input type="checkbox" value="{{ $student->getKey() }}" x-model="selectedRecords"
                               aria-label="Select {{ $student->full_name }}" title="Select for bulk actions">
                    </span>

                    <div class="photo-wrap">
                        <img src="{{ $photoUrl }}" alt="{{ $student->full_name }}">
                    </div>
                    <div class="card-name">
                        {{ $student->full_name }}
                        <span class="sc-gender-pill sc-gender-{{ $student->gender ?? 'other' }}" title="{{ ucfirst($student->gender ?? 'other') }}">
                            @if (($student->gender ?? '') === 'female')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="8" r="6"/><path d="M12 14v8M9 18h6"/></svg>
                            @elseif (($student->gender ?? '') === 'male')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="10" cy="14" r="6"/><path d="M10 8V2M10 2l4 3M10 2L6 5"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="6"/><path d="M12 2v2M12 22v-2M2 12h2M22 12h-2"/></svg>
                            @endif
                            {{ ucfirst($student->gender ?? 'other') }}
                        </span>
                    </div>
                    <div class="card-class">{{ $className }}</div>
                    <div class="card-meta">
                        <div>
                            <span class="m-label">{{ __('Student ID') }}</span>
                            <span class="m-value">{{ $student->student_id_number }}</span>
                        </div>
                        <div>
                            <span class="m-label">{{ __('Age') }}</span>
                            <span class="m-value">{{ $age ? $age.' years' : '-' }}</span>
                        </div>
                        <div>
                            <span class="m-label">{{ __('National ID') }}</span>
                            <span class="m-value">{{ $student->national_id ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="m-label">{{ __('Enrolled') }}</span>
                            <span class="m-value">{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M Y') : '-' }}</span>
                        </div>
                        <div>
                            <span class="m-label">{{ __('Boarding') }}</span>
                            <span class="m-value">{{ ucwords(str_replace('_', ' ', $student->boarding_status ?? 'day scholar')) }}</span>
                        </div>
                        <div>
                            <span class="m-label">{{ __('Gender') }}</span>
                            <span class="m-value">{{ ucfirst($student->gender) }}</span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <a href="{{ $viewUrl }}" class="btn-view">{{ __('View') }}</a>
                        <a href="{{ $editUrl }}" class="btn-edit">{{ __('Edit') }}</a>
                        <a href="{{ $printUrl }}" target="_blank" class="btn-print">{{ __('Print ID') }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
