<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Verify Report Card - Eltrex ERP') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
        <!-- Secure Header -->
        <div class="bg-emerald-600 p-6 text-center text-white">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 text-emerald-800 mb-3">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-wide">{{ __('VERIFIED GENUINE') }}</h2>
            <p class="text-emerald-100 text-xs mt-1">{{ __('Authentic Academic Record Secured by Eltrex ERP') }}</p>
        </div>

        <!-- Student Metadata -->
        <div class="p-6 space-y-4 border-b border-slate-100">
            <div>
                <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('School Name') }}</span>
                <span class="text-sm font-bold text-slate-800">{{ $school->name }}</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Student') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $student->full_name }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Admission No.') }}</span>
                    <span class="text-sm font-mono text-slate-700">{{ $student->admission_number }}</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Class') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $report->section?->full_name }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Term / Period') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ ucwords(strtolower($term->name)) }}</span>
                </div>
            </div>
        </div>

        <!-- Behavioral Score & Audit Trails -->
        <div class="p-6 bg-slate-50/80 space-y-4 text-center">
            <div>
                <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('HBC Character Score') }}</span>
                <span class="text-3xl font-extrabold text-indigo-700">{{ $report->overall_score }} / 10.00</span>
            </div>
            
            <div class="text-left bg-white p-3 rounded-lg border border-slate-100">
                <span class="text-xs text-slate-400 block uppercase font-semibold mb-1">{{ __('Audit Fingerprint Hash') }}</span>
                <span class="text-[10px] font-mono text-slate-500 break-all select-all">{{ $report->integrity_hash }}</span>
            </div>
        </div>

        <!-- Bottom Certification Footer -->
        <div class="bg-slate-100 px-6 py-4 text-center text-xs text-slate-500">
            Certified on {{ $report->created_at->format('d-M-Y H:i') }}
        </div>
    </div>

</body>
</html>