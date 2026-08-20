<x-filament-panels::page>
<div class="space-y-8 text-slate-900 dark:text-slate-100">

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        
        <!-- Outstanding Fees Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Account Balance') }}</span>
                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-950/30 dark:text-rose-400">{{ __('Unpaid') }}</span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                @php
                    $unpaidInvoice = $invoices->where('status', 'unpaid')->first();
                    $outstandingAmount = $unpaidInvoice ? $unpaidInvoice->total : 0.00;
                @endphp
                <span class="text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                    ${{ number_format($outstandingAmount, 2) }}
                </span>
                <span class="text-xs text-slate-400 font-bold">{{ __('USD') }}</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                {{ __('Outstanding licensing fees required to preserve active workspace access.') }}
            </div>
        </div>

        <!-- Prepaid Credits Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Prepaid Credit Balance') }}</span>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">{{ __('Prepaid') }}</span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                    ${{ number_format($subscription->credit_balance, 2) }}
                </span>
                <span class="text-xs text-slate-400 font-bold">{{ __('USD') }}</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                {{ __('Lump-sum advance payments. Deducts automatically on renewal dates.') }}
            </div>
        </div>

        <!-- Next Renewal Date Card -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Licensing Expiry Date') }}</span>
                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400">{{ __('Anchor') }}</span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                    {{ $subscription->next_payment_date ? $subscription->next_payment_date->format('M d, Y') : 'Trial Active' }}
                </span>
            </div>
            <div class="mt-3 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5 font-bold">
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ __('Access Status:') }} <strong class="text-emerald-600 dark:text-emerald-400">{{ $subscription->getDaysRemaining() }} Days Remaining</strong></span>
            </div>
        </div>
    </div>

    <!-- Main Workspace Area Split -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        
        <!-- Left Side: Interactive Choice Payment Panel (2-Columns Span) -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Payment Action Box -->
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ __('Choose Payment Option') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Authorize payments online via Paynow (EcoCash, Card, ZimSwitch) or process a manual bank deposit transfer.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Option A: Paynow Redirection -->
                    <div class="rounded-xl border border-slate-150 bg-slate-50/50 p-6 dark:border-slate-800/80 dark:bg-slate-950/20 flex flex-col justify-between">
                        <div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-600 text-white font-bold mb-4 shadow-sm shadow-green-600/20">
                                <x-heroicon-o-credit-card class="h-5 w-5"/>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Pay Online via Paynow') }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                                {{ __('Instantly renew access. Supports local EcoCash wallets, OneMoney, Zimswitch debit cards, and USD Visa/Mastercard credit accounts.') }}
                            </p>
                        </div>

                        <div class="mt-6 space-y-4">
                            <!-- Currency selector -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-800/50">
                                <span class="text-xs font-semibold text-slate-400">{{ __('Checkout Currency:') }}</span>
                                <div class="flex gap-1 bg-slate-100 p-0.5 rounded-lg dark:bg-slate-950">
                                    <button type="button" wire:click="$set('checkoutCurrency', 'USD')" @class([
                                        'px-2.5 py-1 rounded-md text-xs font-bold transition-all',
                                        'bg-white text-indigo-600 shadow-sm dark:bg-slate-800 dark:text-white' => $checkoutCurrency === 'USD',
                                        'text-slate-500' => $checkoutCurrency !== 'USD'
                                    ])>{{ __('USD') }}</button>
                                    <button type="button" wire:click="$set('checkoutCurrency', 'ZiG')" @class([
                                        'px-2.5 py-1 rounded-md text-xs font-bold transition-all',
                                        'bg-white text-indigo-600 shadow-sm dark:bg-slate-800 dark:text-white' => $checkoutCurrency === 'ZiG',
                                        'text-slate-500' => $checkoutCurrency !== 'ZiG'
                                    ])>{{ __('ZiG') }}</button>
                                </div>
                            </div>

                            @php
                                $unpaidInvoice = $invoices->where('status', 'unpaid')->first();
                            @endphp

                            @if($unpaidInvoice)
                                <button wire:click="initializeOnlinePayment({{ $unpaidInvoice->id }})" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-400 hover:bg-emerald-300 px-5 py-3 text-xs font-extrabold text-slate-950 shadow-lg shadow-emerald-400/30 transition-all transform hover:-translate-y-0.5">
                                    <x-heroicon-o-shield-check class="h-4 w-4 text-slate-950"/>
                                    {{ __('Proceed to Paynow Secure Checkout') }}
                                </button>
                            @else
                                <button wire:click="generateUpcomingBill" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-5 py-3 text-xs font-extrabold text-white shadow-lg shadow-indigo-500/25 transition-all">
                                    <x-heroicon-o-document-plus class="h-4 w-4"/>
                                    {{ __('Generate Licensing Invoice') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Option B: Bank Deposit Proof form -->
                    <div class="rounded-xl border border-slate-150 bg-slate-50/50 p-6 dark:border-slate-800/80 dark:bg-slate-950/20">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-600 text-white font-bold mb-4 shadow-sm shadow-green-600/20">
                            <x-heroicon-o-document-arrow-up class="h-5 w-5"/>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">{{ __('Manual Bank Settlement') }}</h3>

                        <form wire:submit.prevent="submitManualProof" class="space-y-3 text-xs">
                            
                            <!-- Labeled Select Target Invoice -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Select Target Invoice') }}</label>
                                <select wire:model="selectedInvoiceId" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:ring-green-500 focus:border-green-500">
                                    <option value="">{{ __('-- Select Target Invoice --') }}</option>
                                    @foreach($invoices->where('status', 'unpaid') as $inv)
                                        <option value="{{ $inv->id }}">{{ $inv->invoice_number }} (${{ number_format($inv->total, 2) }})</option>
                                    @endforeach
                                </select>
                                @error('selectedInvoiceId') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Labeled Reference Number -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Transaction Ref / Slip Code') }}</label>
                                <input type="text" wire:model="refNumber" placeholder="Enter bank transaction code" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:ring-green-500 focus:border-green-500" />
                                @error('refNumber') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Labeled Paid Amount & Date -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Paid Amount ($)</label>
                                    <input type="number" step="0.01" wire:model="payAmount" placeholder="0.00" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:ring-green-500 focus:border-green-500" />
                                    @error('payAmount') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Payment Date') }}</label>
                                    <input type="date" wire:model="payDate" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:ring-green-500 focus:border-green-500" />
                                    @error('payDate') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Labeled Bank Name -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Source Bank / Vendor Name') }}</label>
                                <input type="text" wire:model="bankName" placeholder="e.g. CBZ Bank, CABS" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:ring-green-500 focus:border-green-500" />
                                @error('bankName') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Labeled Explanatory Notes -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Additional Explanatory Notes') }}</label>
                                <textarea wire:model="notes" placeholder="Optional comments..." rows="2" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:ring-green-500 focus:border-green-500"></textarea>
                            </div>

                            <!-- Labeled Attachment -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Upload Receipt Slip (PDF / Image)</label>
                                <input type="file" wire:model="uploadedReceiptFile" class="block w-full text-[10px] text-slate-400 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-slate-800 dark:file:text-white" />
                                @error('uploadedReceiptFile') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 hover:bg-green-500 py-2.5 font-bold text-white shadow-md shadow-green-500/20 transition-all">
                                <x-heroicon-o-arrow-up-tray class="h-3.5 w-3.5"/>
                                {{ __('Submit Deposit Slip') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Invoices Ledger panel -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Active Licensing Invoices') }}</h3>
                    
                    <button wire:click="generateUpcomingBill" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <x-heroicon-o-document-plus class="h-3.5 w-3.5"/>
                        {{ __('Create New Invoice Statement') }}
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950">
                            <tr class="text-xs font-bold uppercase text-slate-400 dark:text-slate-300">
                                <th class="px-6 py-4">{{ __('Invoice Reference') }}</th>
                                <th class="px-6 py-4">{{ __('Due Date') }}</th>
                                <th class="px-6 py-4">{{ __('Billing Amount') }}</th>
                                <th class="px-6 py-4">{{ __('Payment Status') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800/50 dark:text-slate-400">
                            @forelse($invoices as $inv)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 font-bold text-slate-900 dark:text-white">{{ $inv->invoice_number }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $inv->due_date->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 font-bold text-slate-900 dark:text-white">
                                        @if($checkoutCurrency === 'ZiG' && $inv->status === 'unpaid')
                                            ZWG {{ number_format($inv->total * $conversionRate, 2) }}
                                        @else
                                            ${{ number_format($inv->total, 2) }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider',
                                            'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' => $inv->status === 'unpaid',
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' => $inv->status === 'paid',
                                        ])>
                                            {{ $inv->status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <a href="{{ route('saas.invoice.download', ['uuid' => $inv->uuid]) }}" target="_blank" class="inline-flex items-center gap-1.5 font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4"/>
                                            {{ __('Download Invoice PDF') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('No invoices generated yet. Use the header action to create one.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Receipts Ledger Panel -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800/50">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Official Payments Receipts') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950">
                            <tr class="text-xs font-bold uppercase text-slate-400 dark:text-slate-300">
                                <th class="px-6 py-4">{{ __('Receipt Number') }}</th>
                                <th class="px-6 py-4">{{ __('Amount Paid') }}</th>
                                <th class="px-6 py-4">{{ __('Issued On') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800/50 dark:text-slate-400">
                            @forelse($receipts as $rec)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 font-bold text-slate-900 dark:text-white">{{ $rec->receipt_number }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-slate-900 dark:text-white font-bold">${{ number_format($rec->amount_paid, 2) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $rec->issued_at->format('M d, Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <a href="{{ route('saas.receipt.download', ['uuid' => $rec->uuid]) }}" target="_blank" class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4"/>
                                            {{ __('Download Receipt PDF') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('No payment receipts found on file.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side: Bank details & History -->
        <div class="space-y-8">
            <!-- Platform Bank details -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 border-b border-slate-100 pb-3 dark:border-slate-800">{{ __('Platform Banking Details') }}</h3>
                <div class="space-y-4 text-xs text-slate-600 dark:text-slate-400">
                    <div>
                        <span class="block text-slate-400">{{ __('Bank Name') }}</span>
                        <strong class="text-slate-900 dark:text-white font-bold">{{ $bankSettings->bank_name }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-400">{{ __('Account Holder Name') }}</span>
                        <strong class="text-slate-900 dark:text-white font-bold">{{ $bankSettings->bank_account_name }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-400">{{ __('Account Number') }}</span>
                        <strong class="text-slate-900 dark:text-white font-mono text-sm font-bold tracking-wider">{{ $bankSettings->bank_account_number }}</strong>
                    </div>
                    @if($bankSettings->bank_branch_code)
                        <div>
                            <span class="block text-slate-400">{{ __('Branch Code') }}</span>
                            <strong class="text-slate-900 dark:text-white font-semibold">{{ $bankSettings->bank_branch_code }}</strong>
                        </div>
                    @endif
                    @if($bankSettings->bank_swift_code)
                        <div>
                            <span class="block text-slate-400">{{ __('SWIFT BIC Code') }}</span>
                            <strong class="text-slate-900 dark:text-white font-mono font-semibold">{{ $bankSettings->bank_swift_code }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Manual uploads history list -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 border-b border-slate-100 pb-3 dark:border-slate-800">{{ __('Bank Settlement Audits') }}</h3>
                <div class="space-y-3">
                    @forelse($history as $hist)
                        <div class="border-b border-slate-50 pb-3 last:border-b-0 last:pb-0 dark:border-slate-800/40 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $hist->reference_number }}</span>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400' => $hist->status === 'pending',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400' => $hist->status === 'approved',
                                    'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400' => $hist->status === 'rejected',
                                ]) hybrids-badge>
                                    {{ $hist->status }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-1 text-slate-400">
                                <span>${{ number_format($hist->amount, 2) }} via {{ $hist->bank_name }}</span>
                                <span>{{ $hist->payment_date->format('M d, Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-400 dark:text-slate-500 py-4 text-xs">
                            {{ __('No manual deposit submissions logged.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>