<div style="display: flex; flex-direction: column; gap: 2rem;">
    <div class="sc-section-head is-center">
        <span class="sc-eyebrow">{{ __('SchoolCore Live Feed') }}</span>
        <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ __('Latest News & Announcements') }}</h2>
    </div>

    <div class="sc-grid sc-grid-3">
        @forelse($news as $item)
            <article class="sc-card sc-card-hover sc-news-card">
                <span class="sc-tag">{{ $item['priority'] ?? __('Notice') }}</span>
                <h3>{{ $item['title'] ?? __('School Notice') }}</h3>
                <time class="sc-date" datetime="{{ ($item['published_at'] ?? $item['created_at'] ?? now()) }}">{{ \Carbon\Carbon::parse($item['published_at'] ?? $item['created_at'] ?? now())->format('d M Y') }}</time>
            </article>
        @empty
            <div class="sc-muted" style="grid-column: 1 / -1; text-align: center; padding: 2.5rem 0; font-size: 0.9rem;">{{ __('No active announcements found.') }}</div>
        @endforelse
    </div>
</div>
