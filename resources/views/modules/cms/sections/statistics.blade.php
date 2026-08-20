@php
    use Modules\CMS\Services\ComponentRegistry;

    $statDefs = [
        ['key' => 'students_count', 'label' => __('Active Learners'), 'icon' => 'users'],
        ['key' => 'courses_count', 'label' => __('Specialized Programs'), 'icon' => 'book'],
        ['key' => 'books_count', 'label' => __('Library Items'), 'icon' => 'books'],
        ['key' => 'teachers_count', 'label' => __('Certified Faculty'), 'icon' => 'award'],
    ];
    $icons = [
        'users' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'books' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M9 7h6"/></svg>',
        'award' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
    ];

    $variant = ComponentRegistry::safeVariant('statistics', $block['variant'] ?? null);
    $title = $block['title'] ?? __('Our School in Numbers');
    $stat = fn ($key) => (int) ($stats[$key] ?? 0);
@endphp

@if ($variant === 'horizontal-marquee')
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('By The Numbers') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-stats-marquee">
            <div class="sc-marquee">
                <div class="sc-marquee-track">
                    @foreach(array_merge($statDefs, $statDefs) as $def)
                        @php($value = $stat($def['key']))
                        <span class="sc-stat-marquee-item">
                            <span class="sc-stat-num" data-sc-count="{{ $value }}" data-sc-suffix="+">{{ number_format($value) }}+</span>
                            <span class="sc-stat-label">{{ $def['label'] }}</span>
                            <span class="sc-marquee-dot" aria-hidden="true">✦</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@elseif ($variant === 'minimal-editorial')
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('By The Numbers') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-stats-editorial">
            @foreach($statDefs as $i => $def)
                @php($value = $stat($def['key']))
                <div class="sc-stat-row">
                    <span class="sc-stat-idx" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="sc-stat-label">{{ $def['label'] }}</span>
                    <span class="sc-stat-num" data-sc-count="{{ $value }}" data-sc-suffix="+">{{ number_format($value) }}+</span>
                </div>
            @endforeach
        </div>
    </div>
@elseif ($variant === 'cinematic-overlay')
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('By The Numbers') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-stats">
            @foreach($statDefs as $def)
                @php($value = $stat($def['key']))
                <div class="sc-card sc-stat sc-stat-cinematic">
                    <span class="sc-stat-icon" aria-hidden="true">{!! $icons[$def['icon']] !!}</span>
                    <span class="sc-stat-num" data-sc-count="{{ $value }}" data-sc-suffix="+">{{ number_format($value) }}+</span>
                    <span class="sc-stat-label">{{ $def['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('By The Numbers') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-stats {{ $variant === 'large-number' ? 'sc-stats-large' : '' }}">
            @foreach($statDefs as $def)
                @php($value = $stat($def['key']))
                <div class="sc-card sc-stat">
                    <span class="sc-stat-icon" aria-hidden="true">{!! $icons[$def['icon']] !!}</span>
                    <span class="sc-stat-num" data-sc-count="{{ $value }}" data-sc-suffix="+">{{ number_format($value) }}+</span>
                    <span class="sc-stat-label">{{ $def['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
