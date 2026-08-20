{{-- Employee cards – card-based rendering of the Employees table,
     matching the shared card design language used across the system. --}}
@include('components.table-card-styles')

@if ($records->isEmpty())
    <div class="fi-ta-empty-state px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('No employees found.') }}
    </div>
@else
    <div class="p-4">
        <div class="sc-card-grid">
            @foreach ($records as $employee)
                @php
                    $statusColor = match ($employee->status) {
                        'active' => '#10b981',
                        'suspended' => '#ef4444',
                        'on_leave' => '#f59e0b',
                        'terminated' => 'var(--sc-primary-500)',
                        default => '#64748b',
                    };

                    $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: __('Unnamed Employee');
                    $designation = $employee->designation ?: ($employee->role ?: '—');
                    $department = $employee->department ?: '—';

                    $resolvedPhoto = resolve_public_asset_path($employee->avatar_path ?? null);
                    $photoUrl = $resolvedPhoto ? asset($resolvedPhoto) : null;

                    $initials = strtoupper(
                        mb_substr($employee->first_name ?? '', 0, 1)
                        . mb_substr($employee->last_name ?? '', 0, 1)
                    ) ?: '?';

                    $employeeUrl = \App\Filament\App\Resources\EmployeeResource::getUrl('view', ['record' => $employee]);
                    $editUrl = \App\Filament\App\Resources\EmployeeResource::getUrl('edit', ['record' => $employee]);

                    $recordKey = $employee->getKey();
                @endphp
                <div class="sc-card" style="--sc-card-color: {{ $statusColor }};">
                    <span class="sc-card-status">{{ ucwords(str_replace('_', ' ', $employee->status ?? 'active')) }}</span>

                    <span class="sc-card-select">
                        <input type="checkbox" value="{{ $recordKey }}" x-model="selectedRecords"
                               aria-label="{{ __('Select :name', ['name' => $fullName]) }}" title="{{ __('Select for bulk actions') }}">
                    </span>

                    <div class="sc-card-avatar" style="background: conic-gradient(from 180deg, {{ $statusColor }}, #2dd4bf, {{ $statusColor }});">
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="{{ $fullName }}">
                        @else
                            <span class="sc-avatar-inner" style="color: {{ $statusColor }};">{{ $initials }}</span>
                        @endif
                    </div>

                    <div class="sc-card-name">{{ $fullName }}</div>
                    <div class="sc-card-sub">{{ $designation }}</div>

                    <div class="sc-card-meta">
                        <div>
                            <span class="sc-m-label">{{ __('Employee No.') }}</span>
                            <span class="sc-m-value">{{ $employee->employee_number ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Department') }}</span>
                            <span class="sc-m-value">{{ $department }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Job Role') }}</span>
                            <span class="sc-m-value">{{ $employee->role ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Employment') }}</span>
                            <span class="sc-m-value">{{ ucwords($employee->employment_type ?: '—') }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Joined') }}</span>
                            <span class="sc-m-value">{{ $employee->date_joined ? \Carbon\Carbon::parse($employee->date_joined)->format('d M Y') : '—' }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Phone') }}</span>
                            <span class="sc-m-value">{{ $employee->phone_number ?: '—' }}</span>
                        </div>
                    </div>

                    <div class="sc-card-actions">
                        <a href="{{ $employeeUrl }}" class="sc-btn-view">{{ __('View') }}</a>
                        <a href="{{ $editUrl }}" class="sc-btn-edit">{{ __('Edit') }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
