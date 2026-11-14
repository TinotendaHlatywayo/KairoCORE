@php
    $photoPath = is_array($photo ?? null) ? (collect($photo)->filter()->first() ?: null) : ($photo ?? null);
    $resolvedPhoto = $photoPath ? resolve_public_asset_path($photoPath) : null;
    $fallback = ($gender ?? null) === 'female' ? 'images/no_profile_female.jpg' : 'images/no_profile_male.png';
    $fallbackUrl = asset($fallback);
    $photoUrl = $resolvedPhoto ? asset($resolvedPhoto) : $fallbackUrl;
@endphp

<div class="std-photo-preview">
    <img
        src="{{ $photoUrl }}"
        alt="{{ __('Student photo preview') }}"
        onerror="this.onerror=null;this.src='{{ $fallbackUrl }}';"
    >
    <div>
        <b>{{ $photoPath ? __('Photo ready') : __('No photo uploaded') }}</b>
        <span>{{ $photoPath ? __('This photo will appear on the student profile and ID card.') : __('A gender silhouette is used until a photo is uploaded.') }}</span>
    </div>
</div>
