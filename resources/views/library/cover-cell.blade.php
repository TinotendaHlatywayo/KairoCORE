@php
    $record = $getRecord();
    $cover = $record?->cover_image_path
        ? asset(resolve_public_asset_path($record->cover_image_path))
        : random_library_cover();
    $coverClass = $coverClass ?? 'w-full';
@endphp
<x-library.book-cover
    :src="$cover"
    :alt="optional($record)->title ?? ''"
    :class="trim($coverClass)"
/>