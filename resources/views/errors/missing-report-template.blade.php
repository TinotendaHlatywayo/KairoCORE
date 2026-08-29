<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Report Template Required') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-8 text-center">
        <div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900 mb-2">{{ __('Report Template Required') }}</h2>
        <p class="text-sm text-slate-600 mb-6">
            {{ __('No active academic report template has been created yet. You need to configure and activate at least one report template before printing student report cards.') }}
        </p>
        <div class="flex flex-col gap-3">
            <a href="{{ $createUrl }}" class="w-full inline-flex justify-center items-center px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition shadow-sm">
                {{ __('Proceed to Template Creation') }}
            </a>
            <a href="javascript:window.close();" class="w-full inline-flex justify-center items-center px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-sm transition">
                {{ __('Close Window') }}
            </a>
        </div>
    </div>
</body>
</html>
