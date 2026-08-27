@php
    use Modules\CMS\Services\CmsTemplateService;

    $title = $block['title'] ?? 'Nurturing Academic Excellence';
    $imageUrl = $block['image_url'] ?? asset('images/School_repository_cover.jpeg');
    $imageFit = $block['image_fit'] ?? 'cover';
    $imagePos = $block['image_position'] ?? 'center';
    $reverse = ($block['layout'] ?? 'image-right') === 'image-left';

    // Academic enrollment year: 2026 → "2026/27", 2027 → "2027/28".
    // Editable per-block via badge_text; falls back to the dynamic default.
    if (! empty($block['badge_text'])) {
        $badgeLine = $block['badge_text'];
    } else {
        $year = (int) now()->format('Y');
        $nextShort = str_pad((string) (($year + 1) % 100), 2, '0', STR_PAD_LEFT);
        $badgeLine = __('Enrollment Open for') . ' ' . $year . '/' . $nextShort;
    }

    // DepthText is reserved for motion-forward templates on the lead hero.
    $useDepth = in_array(CmsTemplateService::canonicalTemplate($theme['template'] ?? null), ['cinematic-immersive'], true);
    $students = max((int) ($stats['students_count'] ?? 0), 0);
@endphp

<div class="sc-hero-grid {{ $v['alignClass'] }}">

    <div class="sc-hero-copy">
        <span class="sc-badge">{{ $badgeLine }}</span>

        @if($useDepth)
            <h1 class="sc-hero-title sc-depth"
                data-sc-depth
                data-sc-layers="4"
                data-sc-step="2.2"
                data-sc-depth-color="color-mix(in srgb, var(--sc-primary) 30%, transparent)"
                {!! 'style="' . $v['titleStyle'] . '"' !!}>
                <span class="sc-depth-face">{{ $title }}</span>
            </h1>
        @else
            <h1 class="sc-hero-title" {!! 'style="' . $v['titleStyle'] . '"' !!}>{{ $title }}</h1>
        @endif

        <p class="sc-hero-lead">{!! $rich($block['description']) ?: 'A premier educational institution guiding next-generation achievements.' !!}</p>

        @if(!empty($block['cta_text']) || !empty($block['secondary_cta_text']))
            <div class="sc-hero-actions">
                @if(!empty($block['cta_text']))
                    <a href="{{ $block['cta_url'] ?? '/apply-online' }}" class="sc-btn sc-btn-primary sc-btn-lg">
                        <span>{{ $block['cta_text'] }}</span>
                        <span class="sc-btn-arrow" aria-hidden="true">→</span>
                    </a>
                @endif
                @if(!empty($block['secondary_cta_text']))
                    <a href="{{ $block['secondary_cta_url'] ?? '#' }}" class="sc-btn sc-btn-ghost sc-btn-lg">{{ $block['secondary_cta_text'] }}</a>
                @endif
            </div>
        @endif
    </div>

    <div class="sc-hero-media">
        <span class="sc-hero-frame-ring" aria-hidden="true"></span>
        <div class="sc-hero-frame"@if(! empty($v['imgRadius'])) style="border-radius: {{ $v['imgRadius'] }};"@endif>
            <img src="{{ $imageUrl }}"
                 alt="{{ $title }}"
                 width="1200"
                 height="900"
                 fetchpriority="high"
                 onerror="this.onerror=null; this.src='{{ asset('images/School_repository_cover.jpeg') }}';"
                 style="aspect-ratio: var(--sc-img-ratio, 4 / 3); object-fit: var(--sc-img-fit, {{ $imageFit }}); object-position: var(--sc-img-pos, {{ $imagePos }});">
        </div>

        @if($students > 0)
            <span class="sc-hero-float sc-hero-float-1" aria-hidden="true">
                <span class="sc-dot"></span>
                <span><strong>{{ number_format($students) }}</strong> {{ __('Active Learners') }}</span>
            </span>
        @endif
        @if(!empty($block['secondary_cta_text']))
            <span class="sc-hero-float sc-hero-float-2">
                <span class="sc-badge sc-badge-solid">{{ __('Open Enrollment') }}</span>
            </span>
        @endif
    </div>
</div>
