<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Secure ID Card Verification - Eltrex ERP') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
        
        <!-- Status Indicator Headers -->
        @php 
            $isValid = $student->card_status === 'active' && ($student->card_expiry_date ? $student->card_expiry_date->isFuture() : true);
        @endphp

        @if($isValid)
            <div class="bg-emerald-600 p-6 text-center text-white">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 text-emerald-800 mb-3">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-wide">{{ __('CARD VALID & ACTIVE') }}</h2>
                <p class="text-emerald-100 text-xs mt-1">Certified Student Record of {{ $school->name }}</p>
            </div>
        @else
            <div class="bg-rose-600 p-6 text-center text-white">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 text-rose-800 mb-3">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-wide">{{ __('CARD INVALID / EXPIRED') }}</h2>
                <p class="text-rose-100 text-xs mt-1">{{ __('This ID card is either inactive, lost, or has expired.') }}</p>
            </div>
        @endif

        <!-- Public Student Directory Data Card -->
        <div class="p-6 space-y-4 border-b border-slate-100">
            <div class="flex items-center space-x-4">
                @if($student->photo_path && file_exists(public_path($student->photo_path)))
                    <img class="h-16 w-16 rounded-full object-cover border-2 border-slate-100" src="{{ public_path($student->photo_path) }}">
                @else
                    <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-semibold">{{ __('No Photo') }}</div>
                @endif
                <div>
                    <h3 class="text-lg font-bold text-slate-800">{{ $student->full_name }}</h3>
                    <p class="text-xs text-slate-500">Student ID: {{ $student->student_id_number }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <div>
                    <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">{{ __('Admission No.') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $student->admission_number }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">{{ __('Current Class') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $student->currentEnrollment?->section?->full_name ?? 'Not Enrolled' }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">{{ __('Card Expiry') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $student->card_expiry_date ? $student->card_expiry_date->format('d-M-Y') : 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">{{ __('Card Status') }}</span>
                    <span class="text-sm font-semibold capitalize text-slate-700">{{ str_replace('_', ' ', $student->card_status) }}</span>
                </div>
            </div>
        </div>

        <!-- Security Verification Footer -->
        <div class="bg-slate-50 px-6 py-4 text-center text-xs text-slate-500">
            Verification generated on {{ date('d-M-Y H:i:s') }}<br/>
            <span class="text-[9px] text-slate-400 font-mono mt-1 block">{{ __('Tinway Technologies Secure Core') }}</span>
        </div>
    </div>

</body>
</html>