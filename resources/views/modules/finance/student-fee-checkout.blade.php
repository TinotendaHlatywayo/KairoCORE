<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Fee Payment — Paynow Checkout') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
    <body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg space-y-5">

        <!-- Header -->
        <div class="text-center">
            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white font-extrabold text-xl shadow-lg shadow-indigo-600/30 mb-3">
                {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->last_name ?? 'T', 0, 1)) }}
            </div>
            <h1 class="text-xl font-extrabold text-slate-900">{{ __('Fee Payment') }}</h1>
            <p class="text-xs text-slate-500 mt-1">{{ __('Complete your payment securely via Paynow') }}</p>
        </div>

        <!-- Student & Invoice Details -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <x-heroicon-o-user class="h-5 w-5"/>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">{{ $student->full_name }}</p>
                    <p class="text-[11px] text-slate-500">{{ __('Student No:') }} <span class="font-mono font-semibold">{{ $student->student_id_number }}</span></p>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('Invoice Number') }}</span>
                    <span class="font-mono font-bold text-slate-900">{{ $invoice->invoice_number }}</span>
                </div>
                @if($invoice->term)
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ __('Term') }}</span>
                        <span class="font-semibold text-slate-900">{{ $invoice->term->name }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('Total Billed') }}</span>
                    <span class="font-semibold text-slate-700">${{ number_format((float) $invoice->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('Already Paid') }}</span>
                    <span class="font-semibold text-emerald-600">${{ number_format((float) $invoice->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-2">
                    <span class="font-bold text-slate-900">{{ __('Amount Due') }}</span>
                    <span class="text-lg font-extrabold text-indigo-600">${{ number_format((float) $invoice->balance_amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <!-- Payment Form -->
        <form action="{{ route('student.fee-checkout.process', $invoice->id) }}" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
            @csrf

            <!-- Payment Method -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('Select Payment Method') }}</label>
                <div class="grid grid-cols-2 gap-2" x-data="{ selected: 'ecocash' }">
                    <!-- EcoCash -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="payment_method" value="ecocash" x-model="selected" class="peer sr-only" checked>
                        <div class="rounded-xl border-2 border-slate-200 peer-checked:border-green-500 peer-checked:bg-green-50 p-3 text-center transition-all hover:border-slate-300">
                            <div class="text-lg mb-1">💚</div>
                            <p class="text-xs font-bold text-slate-900">EcoCash</p>
                            <p class="text-[10px] text-slate-500">Mobile Money</p>
                        </div>
                    </label>

                    <!-- OneMoney -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="payment_method" value="one_money" x-model="selected" class="peer sr-only">
                        <div class="rounded-xl border-2 border-slate-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 p-3 text-center transition-all hover:border-slate-300">
                            <div class="text-lg mb-1">💙</div>
                            <p class="text-xs font-bold text-slate-900">OneMoney</p>
                            <p class="text-[10px] text-slate-500">Mobile Money</p>
                        </div>
                    </label>

                    <!-- Zimswitch / Visa / Mastercard -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="payment_method" value="zimswitch" x-model="selected" class="peer sr-only">
                        <div class="rounded-xl border-2 border-slate-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 p-3 text-center transition-all hover:border-slate-300">
                            <div class="text-lg mb-1">💳</div>
                            <p class="text-xs font-bold text-slate-900">Card</p>
                            <p class="text-[10px] text-slate-500">Zimswitch / Visa / MC</p>
                        </div>
                    </label>

                    <!-- Paynow Wallet -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="payment_method" value="omari" x-model="selected" class="peer sr-only">
                        <div class="rounded-xl border-2 border-slate-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 p-3 text-center transition-all hover:border-slate-300">
                            <div class="text-lg mb-1">🪙</div>
                            <p class="text-xs font-bold text-slate-900">O'mari</p>
                            <p class="text-[10px] text-slate-500">Digital Currency</p>
                        </div>
                    </label>
                </div>
                @error('payment_method') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Mobile Number -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">{{ __('Your Mobile Number (Sender)') }}</label>
                <input type="tel" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="+2637XXXXXXXX"
                    class="w-full text-sm rounded-xl border-slate-300 bg-white text-slate-900 focus:ring-indigo-500 focus:border-indigo-500"
                    required/>
                @error('mobile_number') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Email notification -->
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" checked disabled class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"/>
                {{ __('Email me a copy of my payment') }}
            </label>

            <!-- Pay Button -->
            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 font-extrabold rounded-xl text-white shadow-lg shadow-indigo-600/30 transition-all text-sm flex items-center justify-center gap-2">
                <x-heroicon-o-credit-card class="h-4 w-4"/>
                {{ __('Pay $') }}{{ number_format((float) $invoice->balance_amount, 2) }} USD
            </button>

            <!-- School Bank Details (for manual deposit) -->
            @if($bankAccounts->count() > 0)
                <div class="border-t border-slate-100 pt-4">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('Or Deposit to School Bank Account') }}</p>
                    @foreach($bankAccounts as $ba)
                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-3 text-[11px] space-y-0.5">
                            <p class="font-bold text-slate-800">{{ $ba->bank_name }}@if($ba->is_default) <span class="text-indigo-600">(Default)</span>@endif</p>
                            <p class="text-slate-500">{{ __('Account Name:') }} <span class="font-semibold text-slate-700">{{ $ba->account_name }}</span></p>
                            <p class="text-slate-500">{{ __('Account No:') }} <span class="font-mono font-bold text-slate-700">{{ $ba->account_number }}</span></p>
                            @if($ba->branch_code)
                                <p class="text-slate-500">{{ __('Branch:') }} <span class="font-mono font-semibold text-slate-700">{{ $ba->branch_code }}</span></p>
                            @endif
                        </div>
                    @endforeach
                    <a href="/student/my-fees" class="mt-2 block text-center text-[11px] text-indigo-600 hover:underline font-semibold">
                        {{ __('Upload bank deposit proof instead →') }}
                    </a>
                </div>
            @endif
        </form>

        <!-- Footer -->
        <p class="text-center text-[10px] text-slate-400">
            {{ __('Secured by Paynow — Trusted by 25,000+ businesses across Zimbabwe') }} 🔒
        </p>
    </div>

    </body>
</html>
