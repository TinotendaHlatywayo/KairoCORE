{{-- Standard low-profile footer shown on every App Panel page.
     The "Powered by Tinway Technologies" hyperlink and its destination URL
     are configurable under System Administration → System Settings. --}}
@php
    use Modules\Admin\Models\SystemSetting;

    $schoolId = session('current_tenant')?->id ?? (auth()->check() ? auth()->user()->school_id : null);
    $schoolName = session('current_tenant')?->name ?? 'SchoolCore';

    $poweredByText = 'Powered by Tinway Technologies';
    $poweredByUrl = 'https://www.tinwaytechnologies.com';

    if ($schoolId) {
        $poweredByText = SystemSetting::get('footer', 'powered_by_text', $poweredByText);
        $poweredByUrl = SystemSetting::get('footer', 'powered_by_url', $poweredByUrl);
    }
@endphp

<footer class="sc-app-footer py-3 bg-white/85 text-slate-600 text-center border-t border-slate-200/80 backdrop-blur-md dark:bg-gray-950/85 dark:border-white/10 dark:text-slate-300">
    <div class="px-4 leading-relaxed">
        <p class="text-[0.72rem] font-semibold text-slate-800 dark:text-slate-100">
            &copy; {{ date('Y') }} {{ e($schoolName) }}. All rights reserved.
        </p>
        <p class="mt-0.5 text-[0.68rem] opacity-80">
            @if ($poweredByUrl)
                <a href="{{ $poweredByUrl }}" target="_blank" rel="noopener" class="font-semibold text-[color:var(--sc-brand)] hover:underline">
                    {{ $poweredByText }}
                </a>
            @else
                <span class="font-semibold">{{ $poweredByText }}</span>
            @endif
        </p>
    </div>
</footer>
