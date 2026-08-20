@php
    $title = $block['title'] ?? __('Meet Our Certified Faculty');
@endphp

<div style="display: flex; flex-direction: column; gap: 2rem;">
    <div class="sc-section-head is-center">
        <span class="sc-eyebrow">{{ __('Our Team') }}</span>
        <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
    </div>

    <div class="sc-grid sc-grid-4">
        @forelse($staff as $member)
            @php
                $memberName = $member['name'] ?? (trim(($member['first_name'] ?? '').' '.($member['last_name'] ?? '')) ?: __('Staff Member'));
                $memberPhoto = $member['avatar_path'] ?? $member['photo_path'] ?? null;
            @endphp
            <div class="sc-card sc-card-hover sc-team-card">
                <img src="{{ $memberPhoto ? asset('storage/'.$memberPhoto) : asset('images/no_profile_male.png') }}"
                     alt="{{ $memberName }}"
                     class="sc-team-avatar"
                     loading="lazy"
                     width="96" height="96">
                <h4>{{ $memberName }}</h4>
                <p>{{ $member['designation'] ?? $member['position'] ?? __('Educator') }}</p>
            </div>
        @empty
            <div class="sc-muted" style="grid-column: 1 / -1; text-align: center; padding: 2rem 0; font-size: 0.9rem;">{{ __('Faculty directory coming soon.') }}</div>
        @endforelse
    </div>
</div>
