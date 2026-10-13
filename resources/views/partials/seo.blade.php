@php
    $seoTitle = $seoTitle ?? platform_name();
    $seoDescription = $seoDescription ?? platform_seo_description();
    $seoCanonical = $seoCanonical ?? url()->current();
    $seoImage = $seoImage ?? platform_og_image_url();
    $seoImageAlt = $seoImageAlt ?? platform_name();
    $seoRobots = $seoRobots ?? 'index, follow';
    $seoType = $seoType ?? 'website';
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta name="robots" content="{{ $seoRobots }}">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:site_name" content="{{ platform_name() }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
@if ($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="{{ $seoImageAlt }}">
    @if (str_starts_with($seoImage, 'https://'))
        <meta property="og:image:secure_url" content="{{ $seoImage }}">
    @endif
@endif
<meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
@if ($seoImage)
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $seoImageAlt }}">
@endif
@foreach (($seoJsonLd ?? []) as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
@yield('seo_jsonld')