{{-- Platform brand mark shown next to the platform name in the console top bar.
    Uses the uploaded Super Admin Console Logo when available, otherwise the
    bundled transparent logo. --}}
<img src="{{ platform_logo_url() }}"
     alt="{{ platform_name() }}"
     class="h-9 w-9 rounded-lg object-contain"
     style="height: 36px; width: auto; max-width: 160px; object-fit: contain;" />
