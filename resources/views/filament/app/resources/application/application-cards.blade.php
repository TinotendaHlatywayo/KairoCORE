{{-- Application cards – card-based rendering of the Online Admissions table,
     matching the Student Directory card style across the whole system. --}}
@include('components.table-card-styles')

@if ($records->isEmpty())
    <div class="fi-ta-empty-state px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('No applications found.') }}
    </div>
@else
    <div class="p-4">
        <div class="sc-card-grid">
            @foreach ($records as $application)
                @php
                    $statusColor = match ($application->status) {
                        'enrolled' => '#10b981',
                        'pending' => '#f59e0b',
                        'verified' => '#3b82f6',
                        'confirmed' => '#8b5cf6',
                        'rejected' => '#ef4444',
                        'waiting_list' => '#f43f5e',
                        default => '#64748b',
                    };

                    $resolvedPhoto = resolve_public_asset_path($application->photo_path ?? null);
                    $initials = strtoupper(substr($application->first_name, 0, 1) . substr($application->last_name, 0, 1));

                    $grade = $application->course?->name ?? 'Unassigned';
                    $age = $application->date_of_birth
                        ? \Carbon\Carbon::parse($application->date_of_birth)->age
                        : null;

                    $editUrl = \App\Filament\App\Resources\ApplicationResource::getUrl('edit', ['record' => $application]);
                    $viewUrl = \App\Filament\App\Resources\ApplicationResource::getUrl('view', ['record' => $application]);
                    $recordKey = $application->getKey();

                    $appliedDate = $application->created_at?->format('d M Y') ?? '-';
                @endphp
                <div class="sc-card" style="--sc-card-color: {{ $statusColor }};">
                    <span class="sc-card-select">
                        <input type="checkbox" value="{{ $recordKey }}" x-model="selectedRecords"
                               aria-label="Select {{ $application->full_name }}" title="Select for bulk actions">
                    </span>

                    <span class="sc-card-status">{{ strtoupper(str_replace('_', ' ', $application->status)) }}</span>

                    <div class="sc-card-avatar">
                        @if ($resolvedPhoto)
                            <img src="{{ asset($resolvedPhoto) }}" alt="{{ $application->full_name }}">
                        @else
                            <span class="sc-avatar-inner">{{ $initials }}</span>
                        @endif
                    </div>

                    <div class="sc-card-name">
                        {{ $application->full_name }}
                        <span class="sc-gender-pill sc-gender-{{ $application->gender ?? 'other' }}" title="{{ ucfirst($application->gender ?? 'other') }}">
                            @if (($application->gender ?? '') === 'female')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="8" r="6"/><path d="M12 14v8M9 18h6"/></svg>
                            @elseif (($application->gender ?? '') === 'male')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="10" cy="14" r="6"/><path d="M10 8V2M10 2l4 3M10 2L6 5"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="6"/><path d="M12 2v2M12 22v-2M2 12h2M22 12h-2"/></svg>
                            @endif
                            {{ ucfirst($application->gender ?? 'other') }}
                        </span>
                    </div>
                    <div class="sc-card-sub">{{ $grade }}</div>

                    <div class="sc-card-meta">
                        <div>
                            <span class="sc-m-label">{{ __('App No.') }}</span>
                            <span class="sc-m-value">{{ $application->application_number }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Applied') }}</span>
                            <span class="sc-m-value">{{ $appliedDate }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Phone') }}</span>
                            <span class="sc-m-value">{{ $application->parent_phone ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Age') }}</span>
                            <span class="sc-m-value">{{ $age ? $age.' years' : '-' }}</span>
                        </div>
                    </div>

                    <div class="sc-card-actions">
                        <a href="{{ $editUrl }}" class="sc-btn-edit">{{ __('Edit') }}</a>
                        <a href="{{ $viewUrl }}" class="sc-btn-view">{{ __('View') }}</a>
                        <button type="button" class="sc-btn-docs" wire:click="mountTableAction('viewDocuments', '{{ $recordKey }}')">{{ __('Docs') }}</button>
                        @if ($application->status !== 'enrolled')
                            <button type="button" class="sc-btn-enroll" wire:click="mountTableAction('enroll', '{{ $recordKey }}')">{{ __('Enroll') }}</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
