<div
    class="std-content"
    x-data
    x-on:keydown.window.slash.prevent="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && ! $event.target.isContentEditable) { $refs.search.focus(); $refs.search.select(); }"
>
    @include('filament.app.resources.student.partials.student-styles')
    @include('filament.app.resources.student.partials.student-toolbar')

    <div
        class="std-cards {{ $this->studentView === 'list' ? 'is-list' : '' }}"
        wire:loading.class="is-loading"
        wire:target="tableSearch,classFilter,genderFilter,statusFilter,studentSort,studentView,gotoPage,nextPage,previousPage"
        x-on:pointermove="
            const card = $event.target.closest('.student-card');
            if (! card) return;
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mx', ($event.clientX - rect.left) + 'px');
            card.style.setProperty('--my', ($event.clientY - rect.top) + 'px');
        "
    >
        @foreach ($records as $student)
            @php
                $resolvedPhoto = resolve_public_asset_path($student->photo_path ?? null);
                $fallbackPhoto = ($student->gender === 'female') ? 'images/no_profile_female.jpg' : 'images/no_profile_male.png';
                $fallbackUrl = asset($fallbackPhoto);
                $photoUrl = $resolvedPhoto ? asset($resolvedPhoto) : $fallbackUrl;

                $enrollment = $student->currentEnrollment;
                $className = $enrollment
                    ? trim(($enrollment->course?->name ?? '').' '.($enrollment->section?->name ?? '')) ?: __('Unassigned')
                    : __('Unassigned');

                $user = $student->user;
                $isActivated = $user && ($user->activated_at || $user->account_status === \App\Models\User::STATUS_ACTIVE);

                $statusLabel = $isActivated ? __('Active') : __('Pending');
                $statusColor = $isActivated ? '#10b981' : '#f59e0b';
                $statusInk = $isActivated ? '#047857' : '#b45309';

                $age = $student->date_of_birth
                    ? \Carbon\Carbon::parse($student->date_of_birth)->age
                    : null;

                $editUrl = route('filament.app.resources.students.edit', ['record' => $student]);
                $viewUrl = \App\Filament\App\Resources\StudentResource::getUrl('view', ['record' => $student]);
                $printUrl = route('students.print-cards', ['ids' => $student->id, 'layout' => 'pvc']);
                $recordKey = (string) $student->getKey();
            @endphp

            <article
                wire:key="student-card-{{ $student->getKey() }}"
                class="student-card"
                style="--status: {{ $statusColor }}; --status-ink: {{ $statusInk }};"
                x-bind:class="{ 'is-selected': isRecordSelected(@js($recordKey)) }"
            >
                <span class="card-status">{{ $statusLabel }}</span>

                <label class="sc-card-select" title="{{ __('Select for bulk actions') }}">
                    <input
                        type="checkbox"
                        class="fi-ta-record-checkbox"
                        value="{{ $recordKey }}"
                        x-model="selectedRecords"
                        aria-label="{{ __('Select :name', ['name' => $student->full_name]) }}"
                    >
                </label>

                <div class="photo-wrap">
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $student->full_name }}"
                        loading="lazy"
                        onerror="this.onerror=null;this.src='{{ $fallbackUrl }}';"
                    >
                </div>

                <div class="card-id">
                    <div class="card-name">{{ $student->full_name }}</div>
                    <div class="card-class">{{ $className }}</div>
                    <span class="sc-gender-pill sc-gender-{{ $student->gender ?? 'other' }}" title="{{ ucfirst($student->gender ?? 'other') }}">
                        @if (($student->gender ?? '') === 'female')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M12 14v8M9 18h6"/></svg>
                        @elseif (($student->gender ?? '') === 'male')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="10" cy="14" r="6"/><path d="M10 8V2M10 2l4 3M10 2L6 5"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="6"/><path d="M12 2v2M12 22v-2M2 12h2M22 12h-2"/></svg>
                        @endif
                        {{ ucfirst($student->gender ?? 'other') }}
                    </span>
                </div>

                <div class="card-meta">
                    <div>
                        <span class="m-label">{{ __('Student ID') }}</span>
                        <span class="m-value" title="{{ $student->student_id_number }}">{{ $student->student_id_number }}</span>
                    </div>
                    <div>
                        <span class="m-label">{{ __('Age') }}</span>
                        <span class="m-value">{{ $age ? $age.' '.__('years') : '-' }}</span>
                    </div>
                    <div>
                        <span class="m-label">{{ __('Enrolled') }}</span>
                        <span class="m-value">{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M Y') : '-' }}</span>
                    </div>
                    <div>
                        <span class="m-label">{{ __('Boarding') }}</span>
                        <span class="m-value">{{ ucwords(str_replace('_', ' ', $student->boarding_status ?? 'day scholar')) }}</span>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="{{ $viewUrl }}" class="btn-view">{{ __('View') }}</a>
                    <a href="{{ $editUrl }}" class="btn-edit">{{ __('Edit') }}</a>
                    <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="btn-print">{{ __('Print ID') }}</a>
                </div>
            </article>
        @endforeach
    </div>
</div>
