<div style="display: flex; flex-direction: column; gap: 2rem;">
    <div class="sc-section-head is-center">
        <span class="sc-eyebrow">{{ __('School Schedule') }}</span>
        <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ __('Upcoming Campus Events') }}</h2>
    </div>

    <div class="sc-grid sc-grid-3">
        @forelse($events as $event)
            @php($start = \Carbon\Carbon::parse($event['start_time'] ?? $event['start_date'] ?? now()))
            <div class="sc-card sc-card-hover sc-event">
                <span class="sc-date-chip" aria-hidden="true">
                    <span class="sc-date-chip-day">{{ $start->format('d') }}</span>
                    <span class="sc-date-chip-mon">{{ $start->format('M') }}</span>
                </span>
                <div class="sc-event-body">
                    <h4>{{ $event['title'] ?? __('School Event') }}</h4>
                    <p>{{ $event['location'] ?? __('Main Campus') }}</p>
                </div>
            </div>
        @empty
            <div class="sc-muted" style="grid-column: 1 / -1; text-align: center; padding: 2rem 0; font-size: 0.9rem;">{{ __('No upcoming events scheduled.') }}</div>
        @endforelse
    </div>
</div>
