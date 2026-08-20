@php
    $title = $block['title'] ?? 'Welcome to Our School';
    $name = $block['principal_name'] ?? __('The Principal');
    $role = $block['principal_title'] ?? __('Executive Principal');
    $imageUrl = $block['image_url'] ?? asset('images/employee_profile.jpeg');
@endphp

<div class="sc-card sc-card-hover" style="padding: clamp(1.75rem, 3.5vw, 2.75rem); display: grid; grid-template-columns: minmax(0, 1fr) 2fr; gap: clamp(1.5rem, 3vw, 2.5rem); align-items: center; background-color: {{ $v['cardBg'] }};">
    <div class="{{ $v['alignClass'] }}" style="display: flex; flex-direction: column; align-items: {{ $v['align'] === 'left' ? 'flex-start' : ($v['align'] === 'right' ? 'flex-end' : 'center') }}; gap: 0.75rem;">
        <img src="{{ $imageUrl }}" alt="{{ $name }}" class="sc-avatar" width="176" height="176" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('images/employee_profile.jpeg') }}';" style="width: clamp(7rem, 12vw, 11rem); height: clamp(7rem, 12vw, 11rem);">
        <div>
            <h4 style="font-family: var(--sc-font-display); font-weight: 800; font-size: 1.1rem;">{{ $name }}</h4>
            <span class="sc-eyebrow" style="margin-bottom: 0;">{{ $role }}</span>
        </div>
    </div>

    <div class="{{ $v['alignClass'] }}">
        <h2 {!! 'style="' . $v['titleStyle'] . '; font-weight: 800;"' !!}>
            {{ $title }}
        </h2>
        <div class="sc-muted" style="margin-top: 0.9rem; font-size: 1.02rem; line-height: 1.7;">
            {!! $rich($block['description']) ?: __('Our vision is to deliver rigorous, holistic education.') !!}
        </div>
    </div>
</div>
