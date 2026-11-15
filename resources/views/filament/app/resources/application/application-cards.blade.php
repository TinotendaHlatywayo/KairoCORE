{{-- Application cards – card-based rendering of the Online Admissions table,
     integrated with actual system data, mouse-tracking glow, and list/grid
     interactivity matching the Student Directory. --}}
@include('components.table-card-styles')

<style>
    .adm-card-wrap { min-width: 0; }
    .adm-card-wrap .sc-card-status {
        position: absolute !important;
        top: 16px !important;
        right: 16px !important;
        left: auto !important;
        bottom: auto !important;
        inset: auto 16px auto auto !important;
        width: auto !important;
        max-width: max-content;
        height: auto !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        align-self: flex-start !important;
        flex: 0 0 auto !important;
        z-index: 5;
        margin: 0 !important;
        padding: 6px 12px !important;
        line-height: 1.2 !important;
    }
    .adm-card-wrap .sc-card { padding-top: 10px; }
    .sc-card-grid.is-list { grid-template-columns: 1fr !important; gap: 12px; }
    .sc-card-grid.is-list .sc-card {
        flex-direction: row; align-items: center; flex-wrap: wrap; gap: 16px; padding: 14px 18px; padding-right: 132px;
    }
    .sc-card-grid.is-list .sc-card::before { inset: 0 auto 0 0; width: 4px; height: auto; }
    .sc-card-grid.is-list .sc-card-avatar { margin: 0; width: 52px; height: 52px; flex: none; }
    .sc-card-grid.is-list .sc-card-avatar .sc-avatar-inner { font-size: 1rem; }
    .sc-card-grid.is-list .sc-card-name,
    .sc-card-grid.is-list .sc-card-sub { text-align: left; padding: 0; margin: 0; }
    .sc-card-grid.is-list .sc-card-name-block { min-width: 180px; }
    .sc-card-grid.is-list .sc-card-meta {
        margin: 0; border-top: 0; padding-top: 0; grid-template-columns: repeat(4, auto); flex: 1; gap: 8px 24px;
    }
    .sc-card-grid.is-list .sc-card-actions { margin: 0; padding: 0; width: 360px; flex: none; }
    .sc-card-grid.is-list .sc-card-select { position: static; order: -1; }
    .adm-results-foot { display:flex; justify-content:space-between; align-items:center; padding:14px 4px 4px; color:var(--adm-muted, #64668a); font-size:13px; }
    @media (max-width: 900px) {
        .sc-card-grid.is-list .sc-card { display:grid; grid-template-columns:auto 52px 1fr; padding-right: 18px; padding-top: 14px; }
        .sc-card-grid.is-list .sc-card-meta { grid-column: 2 / -1; grid-template-columns: repeat(2, minmax(0,1fr)); }
        .sc-card-grid.is-list .sc-card-actions { grid-column: 2 / -1; width: 100%; }
        .adm-card-wrap .sc-card-status { top: 12px !important; right: 12px !important; inset: auto 12px auto auto !important; }
    }
</style>

<div class="adm-card-wrap">
    @include('filament.app.resources.application.partials.application-toolbar')

    @if ($records->isEmpty())
        <div class="fi-ta-empty-state px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400" style="border:1px dashed var(--adm-line,#e6e7f5);border-radius:16px;background:var(--adm-surface,#fff);">
            <b style="display:block;color:var(--adm-text,#1e1b4b);font-size:16px;margin-bottom:4px;">{{ __('No applications match') }}</b>
            {{ __('Try a different search, or clear the status and grade filters.') }}
        </div>
    @else
        <div
            x-data
            x-on:pointermove="
                const card = $event.target.closest('.sc-card');
                if (! card) return;
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--mx', ($event.clientX - rect.left) + 'px');
                card.style.setProperty('--my', ($event.clientY - rect.top) + 'px');
            "
        >
            <div class="sc-card-grid {{ ($this->applicationView ?? 'grid') === 'list' ? 'is-list' : '' }}">
                @foreach ($records as $application)
                    @php
                        $statusColor = match ($application->status) {
                            'enrolled' => '#10b981',
                            'pending' => '#f59e0b',
                            'verified', 'confirmed' => '#0ea5e9',
                            'rejected', 'declined' => '#e11d48',
                            'waiting_list' => '#8b5cf6',
                            default => '#64748b',
                        };

                        $resolvedPhoto = resolve_public_asset_path($application->photo_path ?? null);
                        $initials = strtoupper(substr($application->first_name, 0, 1) . substr($application->last_name, 0, 1));

                        $grade = $application->course?->name ?? __('Unassigned');
                        $age = $application->date_of_birth
                            ? \Carbon\Carbon::parse($application->date_of_birth)->age
                            : null;

                        $editUrl = \App\Filament\App\Resources\ApplicationResource::getUrl('edit', ['record' => $application]);
                        $viewUrl = \App\Filament\App\Resources\ApplicationResource::getUrl('view', ['record' => $application]);
                        $recordKey = $application->getKey();

                        $appliedDate = $application->created_at?->format('d M Y') ?? '-';
                        $statusLabel = match ($application->status) {
                            'verified', 'confirmed' => __('In review'),
                            'rejected', 'declined' => __('Declined'),
                            default => __(ucfirst(str_replace('_', ' ', $application->status))),
                        };
                    @endphp
                    <div class="sc-card" style="--sc-card-color: {{ $statusColor }};" x-bind:class="{ 'is-selected': isRecordSelected(@js($recordKey)) }">
                        <span class="sc-card-select">
                            <input type="checkbox" value="{{ $recordKey }}" x-model="selectedRecords"
                                   aria-label="{{ __('Select :name', ['name' => $application->full_name]) }}" title="{{ __('Select for bulk actions') }}">
                        </span>

                        <span class="sc-card-status">{{ strtoupper($statusLabel) }}</span>

                        <div class="sc-card-avatar">
                            @if ($resolvedPhoto)
                                <img src="{{ asset($resolvedPhoto) }}" alt="{{ $application->full_name }}">
                            @else
                                <span class="sc-avatar-inner">{{ $initials }}</span>
                            @endif
                        </div>

                        <div class="sc-card-name-block">
                            <div class="sc-card-name">
                                {{ $application->full_name }}
                                <span class="sc-gender-pill sc-gender-{{ $application->gender ?? 'other' }}" title="{{ ucfirst($application->gender ?? 'other') }}">
                                    @if (($application->gender ?? '') === 'female')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="8" r="6"/><path d="M12 14v8M9 18h6"/></svg>
                                    @elseif (($application->gender ?? '') === 'male')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="10" cy="14" r="6"/><path d="M14 4l6 0 0 6"/><path d="M14 10l6-6"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="6"/><path d="M12 2v2M12 22v-2M2 12h2M22 12h-2"/></svg>
                                    @endif
                                    {{ ucfirst($application->gender ?? 'other') }}
                                </span>
                            </div>
                            <div class="sc-card-sub">{{ $grade }}</div>
                        </div>

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
                                <span class="sc-m-value">{{ $application->parent_phone ?: ($application->phone ?? '-') }}</span>
                            </div>
                            <div>
                                <span class="sc-m-label">{{ __('Age') }}</span>
                                <span class="sc-m-value">{{ $age ? $age.' '.__('years') : '-' }}</span>
                            </div>
                        </div>

                        <div class="sc-card-actions">
                            <a href="{{ $editUrl }}" class="sc-btn-edit">{{ __('Edit') }}</a>
                            <a href="{{ $viewUrl }}" class="sc-btn-view">{{ __('View') }}</a>
                            <button type="button" class="sc-btn-docs" wire:click="mountTableAction('viewDocuments', '{{ $recordKey }}')">{{ __('Docs') }}</button>
                            @if (!in_array($application->status, ['enrolled', 'rejected', 'declined'], true))
                                <button type="button" class="sc-btn-decline" wire:click="mountTableAction('decline', '{{ $recordKey }}')">{{ __('Decline') }}</button>
                                <button type="button" class="sc-btn-enroll" wire:click="mountTableAction('enroll', '{{ $recordKey }}')">{{ __('Enroll') }}</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="adm-results-foot">
            <span>{{ __('Showing :shown of :total applications', ['shown' => $records->count(), 'total' => method_exists($records, 'total') ? $records->total() : $records->count()]) }}</span>
        </div>
    @endif
</div>
