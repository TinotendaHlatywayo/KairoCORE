@php
    use Modules\Admin\Models\SystemSetting;

    $schoolId = session('current_tenant')?->id ?? (auth()->check() ? auth()->user()->school_id : null);
    $profileSchoolName = $schoolId ? SystemSetting::get('profile', 'school_name') : null;
    $schoolName = $profileSchoolName ?: (session('current_tenant')?->name ?? ($schoolId ? \App\Models\School::find($schoolId)?->name : null) ?: 'Kairo CORE');
    
    // Default logo is the Super Admin Console Logo (platform logo)
    $logoUrl = platform_logo_url();

    $logoHeight = '32px';
    $logoOpacity = '1.0';

    if ($schoolId) {
        $customLogo = SystemSetting::get('branding', 'logo_path');
        $school = \App\Models\School::find($schoolId);
        if (!empty($customLogo)) {
            $logoUrl = asset('storage/' . $customLogo);
        } elseif ($school && !empty($school->logo_path)) {
            $logoUrl = asset('storage/' . $school->logo_path);
        }
        $logoHeight = SystemSetting::get('branding', 'logo_height', '32px');
        $logoOpacity = SystemSetting::get('branding', 'logo_opacity', '1.0');
    }

    $numericHeight = max(24, min((int) $logoHeight, 40));
    // Flexible inline height & max dimensions so any logo type (square, rectangular, wide banner) fits cleanly without distortion
    $inlineStyles = 'style="max-height: ' . $numericHeight . 'px; opacity: ' . $logoOpacity . ';"';
@endphp

<div class="flex items-center gap-3 py-1" x-data="{ schoolName: '{{ $schoolName }}' }" @theme-updated.window="schoolName = $event.detail.school_name || schoolName">
    <div class="flex-shrink-0 flex items-center justify-center overflow-hidden">
        <img src="{{ $logoUrl }}" 
             alt="{{ __('School Logo') }}" 
             class="w-auto object-contain rounded-md" 
             {!! $inlineStyles !!} />
    </div>
    <span class="font-bold text-sm tracking-tight text-gray-900 dark:text-white truncate max-w-[180px]" x-text="schoolName">
        {{ $schoolName }}
    </span>
</div>