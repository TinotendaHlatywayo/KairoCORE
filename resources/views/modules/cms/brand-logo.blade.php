@php
    use Modules\Admin\Models\SystemSetting;

    $tenant = session('current_tenant');
    $schoolId = $tenant?->id ?? (auth()->check() ? auth()->user()->school_id : null);
    $profileSchoolName = $schoolId ? SystemSetting::get('profile', 'school_name') : null;
    $schoolName = $profileSchoolName ?: ($tenant?->name ?? ($schoolId ? \App\Models\School::find($schoolId)?->name : null) ?: 'Kairo CORE');
    $loginCode = $tenant?->login_code ?? ($schoolId ? \App\Models\School::withTrashed()->find($schoolId)?->login_code : null);

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
    <div class="min-w-0">
        <span class="font-bold text-sm tracking-tight text-gray-900 dark:text-white truncate max-w-[180px] block" x-text="schoolName">
            {{ $schoolName }}
        </span>
        @if(!empty($loginCode))
            <span class="block text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                  title="{{ __('School login code — share it so people can find your login page at kairo.co.zw/school-login') }}">
                ({{ $loginCode }})
            </span>
        @endif
    </div>
</div>