<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Paynow Secure Sandbox Checkout') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-slate-800 border border-slate-700 rounded-2xl p-8 shadow-2xl space-y-6">
        <div class="text-center space-y-2">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-green-500/20 text-green-400 font-bold text-xl mb-2">
                {{ __('🔒') }}
            </div>
            <h1 class="text-xl font-extrabold tracking-tight">Paynow Secure Checkout (Sandbox)</h1>
            <p class="text-xs text-slate-400">{{ __('Simulating payment gateway processing for Kairo CORE SaaS licensing.') }}</p>
        </div>

        <div class="bg-slate-900/50 rounded-xl p-4 space-y-3 text-xs border border-slate-700/50">
            <div class="flex justify-between">
                <span class="text-slate-400">{{ __('Invoice Number:') }}</span>
                <strong class="font-mono text-white">{{ $invoiceNumber }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">{{ __('Transaction Reference:') }}</span>
                <strong class="font-mono text-emerald-400 truncate max-w-[200px]">{{ $ref }}</strong>
            </div>
            <div class="flex justify-between border-t border-slate-800 pt-2 font-bold text-sm">
                <span>{{ __('Total Charge:') }}</span>
                <span class="text-emerald-400">${{ number_format((float)$amount, 2) }} USD</span>
            </div>
        </div>

        <div class="space-y-3">
            <label class="block text-xs font-bold text-slate-300">{{ __('Select Simulation Outcome') }}</label>
            <form action="{{ route('saas.paynow.sandbox-complete') }}" method="POST">
                @csrf
                <input type="hidden" name="ref" value="{{ $ref }}">
                <button type="submit" class="w-full py-3 bg-green-600 hover:bg-green-500 font-bold rounded-xl text-white shadow-lg shadow-green-600/30 transition-all text-sm flex items-center justify-center gap-2">
                    <span>✅ Simulate Successful Payment (EcoCash / Card)</span>
                </button>
            </form>
            <a href="/workspace/saas-billing-overview" class="block text-center text-xs text-slate-400 hover:text-white pt-2">
                {{ __('Cancel and return to billing overview') }}
            </a>
        </div>
    </div>
</body>
</html>
