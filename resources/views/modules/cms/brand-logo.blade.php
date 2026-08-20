@php
    use Modules\Admin\Models\SystemSetting;

    $schoolId = session('current_tenant')?->id ?? (auth()->check() ? auth()->user()->school_id : null);
    $profileSchoolName = $schoolId ? SystemSetting::get('profile', 'school_name') : null;
    $schoolName = $profileSchoolName ?: (session('current_tenant')?->name ?? ($schoolId ? \App\Models\School::find($schoolId)?->name : null) ?: 'SchoolCore');
    
    // Set default assets
    $logoUrl = asset('images/Transparant Logo.png');
    if (!file_exists(public_path('images/Transparant Logo.png')) && file_exists(public_path('images/Transparent Logo.png'))) {
        $logoUrl = asset('images/Transparent Logo.png');
    }

    $logoHeight = '32px';
    $logoOpacity = '1.0';

    if ($schoolId) {
        $customLogo = SystemSetting::get('branding', 'logo_path');
        if (!empty($customLogo)) {
            $logoUrl = asset('storage/' . $customLogo);
        }
        $logoHeight = SystemSetting::get('branding', 'logo_height', '32px');
        $logoOpacity = SystemSetting::get('branding', 'logo_opacity', '1.0');
    }

    // Cap the logo so it always fits inside the top bar (never exceeds ~40px),
    // regardless of what value is stored in the branding settings.
    $logoHeight = max(24, min((int) $logoHeight, 40)).'px';

    // Direct inline rendering ensures standard circular aspect ratios [1]
    $inlineStyles = 'style="height: ' . $logoHeight . '; width: ' . $logoHeight . '; opacity: ' . $logoOpacity . ';"';
@endphp

<div class="flex items-center gap-3 py-1" x-data="{ schoolName: '{{ $schoolName }}' }" @theme-updated.window="schoolName = $event.detail.school_name || schoolName">
    <img src="{{ $logoUrl }}" 
         alt="School Logo" 
         class="object-cover rounded-full border border-gray-200 dark:border-gray-800 aspect-square" 
         {!! $inlineStyles !!} />
    <span class="font-bold text-sm tracking-tight text-gray-900 dark:text-white" x-text="schoolName">
        {{ $schoolName }}
    </span>
</div>