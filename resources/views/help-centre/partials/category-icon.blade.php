{{--
    Small line icon for a Help Centre category.

    @param string $categoryKey  Category folder key (or "all").
    @param string $svgClass     Extra classes for the <svg> element.

    Simple composed shapes rather than a dependency on any icon font, so the
    icons render identically everywhere the public docs are served.
--}}
@php
    $paths = match ($categoryKey) {
        'all' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h5"/>',
        'getting-started' => '<path d="M6 3v18"/><path d="M6 4.5h11l-2.2 3L17 10.5H6"/>',
        'academics' => '<path d="M3 9l9-4 9 4-9 4-9-4z"/><path d="M7 11.5V16c0 1.6 2.4 3 5 3s5-1.4 5-3v-4.5"/><path d="M21 9v5"/>',
        'students' => '<circle cx="9" cy="8" r="3.2"/><circle cx="17" cy="9" r="2.4"/><path d="M3.5 18.5c0-3 2.5-5.2 5.5-5.2s5.5 2.2 5.5 5.2"/><path d="M15.5 13.7c2.4.4 4.2 2.2 4.2 4.5"/>',
        'admissions' => '<rect x="6" y="5" width="12" height="15" rx="2"/><path d="M9.5 5V4.2A1.2 1.2 0 0110.7 3h2.6a1.2 1.2 0 011.2 1.2V5"/><path d="M9.5 10.5h5M9.5 14h5"/>',
        'finance' => '<rect x="3" y="7" width="18" height="10" rx="2"/><circle cx="12" cy="12" r="2.4"/><path d="M6.5 12h.01M17.5 12h.01"/>',
        'exams' => '<path d="M4 20l4.2-1L18.5 8.7l-3.2-3.2L5 15.8 4 20z"/><path d="M14.2 6.6l3.2 3.2"/>',
        'hr' => '<circle cx="12" cy="8" r="3.4"/><path d="M5 19.5c0-3.6 3.1-6 7-6s7 2.4 7 6"/><path d="M17.5 5.2a3 3 0 010 5.6"/>',
        'inventory' => '<path d="M4 8.2l8-4 8 4v7.6l-8 4-8-4V8.2z"/><path d="M4 8.2l8 4 8-4M12 12.2v7.6"/>',
        'library' => '<path d="M6 4h12v16H6.8A1.8 1.8 0 015 18.2V5.8A1.8 1.8 0 016.8 4z"/><path d="M9 4v16"/><path d="M12 8h3.5"/>',
        'boarding' => '<path d="M4 11l8-7 8 7"/><path d="M6.5 10v9h11v-9"/><path d="M10 19v-4.5h4V19"/>',
        'health' => '<path d="M12 20.5S4.8 16 4.8 10.6A3.9 3.9 0 0112 8.2a3.9 3.9 0 017.2 2.4C19.2 16 12 20.5 12 20.5z"/>',
        'communication' => '<path d="M21 11.5a7.5 7.5 0 01-7.5 7.5H8.2L4.5 21.5V18A7.5 7.5 0 0112 4.5a7.5 7.5 0 019 7z"/><path d="M8.5 11h7M8.5 14h4.5"/>',
        'lms' => '<circle cx="12" cy="12" r="8.5"/><path d="M10 8.5l5.5 3.5L10 15.5z"/>',
        'website' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c2.4 2.3 3.6 5.2 3.6 8.5S14.4 18.2 12 20.5c-2.4-2.3-3.6-5.2-3.6-8.5S9.6 5.8 12 3.5z"/>',
        'reports' => '<path d="M4.5 20V13M9.8 20V7M15.2 20v-9M20.5 20V4.5"/>',
        'administration' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3.5v2.6M12 17.9v2.6M3.5 12h2.6M17.9 12h2.6M6 6l1.9 1.9M16.1 16.1L18 18M18 6l-1.9 1.9M7.9 16.1L6 18"/>',
        'troubleshooting' => '<circle cx="11" cy="11" r="6.5"/><path d="M15.8 15.8L21 21"/><path d="M9.2 9.4a1.9 1.9 0 013.6.7c0 1.3-1.9 1.7-1.9 2.8"/><path d="M11 15.5h.01"/>',
        'saas' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 14.5h3"/>',
        default => '<circle cx="12" cy="12" r="8.5"/><path d="M12 8.5v4l2.5 1.5"/>',
    };
@endphp
<svg class="{{ $svgClass ?? 'h-5 w-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths !!}</svg>
