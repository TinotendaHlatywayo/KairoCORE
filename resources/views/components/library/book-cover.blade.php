@props([
    'src' => null,
    'alt' => '',
    'aspect' => '2/3',
])

@php
    $coverSrc = $src ?? random_library_cover();
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800']) }}
     style="aspect-ratio: {{ $aspect }};">
    <img src="{{ $coverSrc }}"
         alt=""
         aria-hidden="true"
         loading="lazy"
         class="absolute inset-0 h-full w-full scale-125 object-cover blur-lg opacity-60">
    <img src="{{ $coverSrc }}"
         alt="{{ $alt }}"
         loading="lazy"
         class="relative z-10 h-full w-full object-contain drop-shadow-lg">
</div>