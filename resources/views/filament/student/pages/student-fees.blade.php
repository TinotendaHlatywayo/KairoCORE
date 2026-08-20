<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('Student Profile Not Linked Yet') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    {{ __('Your account is not yet linked to a student record. Please contact the school administration to link your profile.') }}
                </p>
            </div>
        @else

            <!-- Student Profile Header -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-xl">
                        {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) . strtoupper(substr($student->last_name ?? 'T', 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $student->full_name }}</h2>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <span><x-heroicon-o-identification class="inline h-3.5 w-3.5 text-indigo-500"/> {{ __('Student No:') }} <span class="font-mono font-semibold">{{ $student->student_id_number }}</span></span>
                            <span><x-heroicon-o-arrow-right-circle class="inline h-3.5 w-3.5 text-indigo-500"/> {{ __('Admission:') }} <span class="font-mono font-semibold">{{ $student->admission_number }}</span></span>
                            <span><x-heroicon-o-book-open class="inline h-3.5 w-3.5 text-indigo-500"/> {{ $student->currentEnrollment?->course?->name ?? __('Not Enrolled') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Term Selector -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('Viewing fees for:') }}</label>
                    <select wire:model.live="selectedTermId" class="flex-1 text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">{{ __('All Terms (Full Year)') }}</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}">
                                {{ $term->academicYear?->name ?? '' }} — {{ $term->name }}
                                @if($term->start_date && $term->end_date)
                                    ({{ $term->start_date->format('d M') }} – {{ $term->end_date->format('d M Y') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($selectedTermId)
                    <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
                        {{ __('Showing selected term only.') }}
                        <button wire:click="$set('selectedTermId', null)" class="font-bold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('View All Terms') }}</button>
                    </p>
                @endif
            </div>

            <!-- Fee Summary Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Total Billed') }}</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">${{ number_format($totalBilled, 2) }}</p>
                    @if($selectedTermId && $totalBilledAll > $totalBilled)
                        <p class="mt-0.5 text-[10px] text-slate-400">{{ __('Year total: $') }}{{ number_format($totalBilledAll, 2) }}</p>
                    @endif
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/20">
                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Total Paid') }}</p>
                    <p class="mt-1 text-2xl font-extrabold text-emerald-700 dark:text-emerald-400">${{ number_format($totalPaid, 2) }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5 shadow-sm dark:border-rose-800 dark:bg-rose-950/20">
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400">{{ __('Outstanding Balance') }}</p>
                    <p class="mt-1 text-2xl font-extrabold text-rose-700 dark:text-rose-400">${{ number_format($totalDue, 2) }}</p>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                    @if($selectedTermId)
                        {{ __('Term Fee Invoice') }}
                    @else
                        {{ __('My Fee Invoices') }}
                    @endif
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Invoice No') }}</th>
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Term') }}</th>
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Issued') }}</th>
                                <th class="py-2.5 pr-3 font-semibold text-right">{{ __('Total') }}</th>
                                <th class="py-2.5 pr-3 font-semibold text-right">{{ __('Paid') }}</th>
                                <th class="py-2.5 pr-3 font-semibold text-right">{{ __('Balance') }}</th>
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Status') }}</th>
                                <th class="py-2.5 font-semibold text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $inv)
                                <tr class="border-b border-slate-50 dark:border-slate-800/60">
                                    <td class="py-3 pr-3 font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $inv->invoice_number }}</td>
                                    <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $inv->term?->name ?? '—' }}</td>
                                    <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $inv->created_at?->format('d M Y') }}</td>
                                    <td class="py-3 pr-3 text-right font-semibold text-slate-700 dark:text-slate-300">${{ number_format((float) $inv->total_amount, 2) }}</td>
                                    <td class="py-3 pr-3 text-right text-emerald-600">${{ number_format((float) $inv->paid_amount, 2) }}</td>
                                    <td class="py-3 pr-3 text-right font-bold text-rose-600">${{ number_format((float) $inv->balance_amount, 2) }}</td>
                                    <td class="py-3 pr-3">
                                        @if($inv->status === 'paid')
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('PAID') }}</span>
                                        @elseif($inv->status === 'partially_paid')
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('PARTIAL') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ __('UNPAID') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right">
                                        @if((float) $inv->balance_amount > 0)
                                            <button wire:click="initializeOnlinePayment({{ $inv->id }})" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-[10px] font-bold text-white shadow-sm hover:bg-emerald-500">
                                                <x-heroicon-o-credit-card class="h-3.5 w-3.5"/>
                                                {{ __('Pay via Paynow') }}
                                            </button>
                                        @else
                                            <span class="text-[10px] font-semibold text-slate-400">{{ __('Settled') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-xs text-slate-400">{{ __('No fee invoices for the selected period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Options -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Option A: Paynow -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-600 text-white font-bold mb-4 shadow-sm shadow-green-600/20">
                        <x-heroicon-o-shield-check class="h-5 w-5"/>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Pay Online via Paynow') }}</h3>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        {{ __('Pay your outstanding fees instantly using the "Pay via Paynow" button on your invoice above. Supports EcoCash, OneMoney, Zimswitch, Visa/Mastercard.') }}
                    </p>
                </div>

                <!-- Option B: Bank Deposit -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white font-bold mb-4 shadow-sm shadow-blue-600/20">
                        <x-heroicon-o-building-library class="h-5 w-5"/>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">{{ __('Bank Deposit') }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ __('Made a bank deposit? Submit the details and attach your proof of payment for the finance office to verify.') }}</p>

                    <!-- School Bank Accounts (Destination) -->
                    @if($bankAccounts->count() > 0)
                        <div class="rounded-lg border border-blue-100 bg-blue-50/50 p-4 text-xs dark:border-blue-800 dark:bg-blue-950/20 mb-4">
                            <p class="font-bold text-blue-800 dark:text-blue-200 mb-2">{{ __('School Bank Accounts (Pay To)') }}</p>
                            @foreach($bankAccounts as $ba)
                                <div class="mb-2 last:mb-0 rounded-lg bg-white/80 p-3 dark:bg-slate-900/60 border border-blue-100 dark:border-blue-800/40">
                                    <div class="flex items-start gap-2">
                                        <x-heroicon-o-building-office-2 class="h-4 w-4 mt-0.5 text-blue-500"/>
                                        <div class="space-y-0.5 text-slate-600 dark:text-slate-300">
                                            <p class="font-bold">{{ $ba->bank_name }}</p>
                                            <p>{{ __('Account Name:') }} <span class="font-semibold">{{ $ba->account_name }}</span></p>
                                            <p>{{ __('Account No:') }} <span class="font-mono font-bold">{{ $ba->account_number }}</span></p>
                                            @if($ba->branch_code)
                                                <p>{{ __('Branch:') }} <span class="font-mono font-semibold">{{ $ba->branch_code }}</span></p>
                                            @endif
                                            @if($ba->swift_code)
                                                <p>{{ __('SWIFT:') }} <span class="font-mono font-semibold">{{ $ba->swift_code }}</span></p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit.prevent="submitManualProof" class="space-y-3 text-xs">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Select Invoice') }}</label>
                            <select wire:model="selectedInvoiceId" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ __('-- Select Target Invoice --') }}</option>
                                @foreach($invoices->where('balance_amount', '>', 0) as $inv)
                                    <option value="{{ $inv->id }}">{{ $inv->invoice_number }} — {{ $inv->term?->name ?? 'N/A' }} (${{ number_format((float) $inv->balance_amount, 2) }})</option>
                                @endforeach
                            </select>
                            @error('selectedInvoiceId') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Destination Bank Account -->
                        @if($bankAccounts->count() > 1)
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Deposit To (School Account)') }}</label>
                                <select wire:model="selectedBankAccountId" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">{{ __('-- Select School Account --') }}</option>
                                    @foreach($bankAccounts as $ba)
                                        <option value="{{ $ba->id }}">{{ $ba->bank_name }} — {{ $ba->account_number }}{{ $ba->is_default ? ' (Default)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($bankAccounts->count() === 1)
                            <input type="hidden" wire:model="selectedBankAccountId" value="{{ $bankAccounts->first()->id }}"/>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Transaction Ref / Slip Code') }}</label>
                            <input type="text" wire:model="refNumber" placeholder="{{ __('e.g. CBZ260817001234') }}" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500"/>
                            @error('refNumber') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Paid Amount (USD)') }}</label>
                                <input type="number" step="0.01" wire:model="payAmount" placeholder="0.00" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500"/>
                                @error('payAmount') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Payment Date') }}</label>
                                <input type="date" wire:model="payDate" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500"/>
                                @error('payDate') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Source Bank (Searchable) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Source Bank (Your Bank)') }}</label>
                            <div x-data="{ search: '', showCustom: false }" class="relative">
                                <input type="text" x-model="search" @input="showCustom = search.length > 0 && !{{ json_encode($bankList) }}.some(b => b.toLowerCase() === search.toLowerCase())" placeholder="{{ __('Search your bank...') }}" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500"/>
                                <div x-show="search.length > 0" x-cloak class="absolute z-50 mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900" style="scrollbar-width: thin;">
                                    @foreach($bankList as $bank)
                                        <button type="button" x-show="'{{ strtolower($bank) }}'.includes(search.toLowerCase())" @click="search = '{{ $bank }}'; $wire.set('sourceBankName', '{{ $bank }}'); showCustom = false" class="w-full px-3 py-2 text-left text-xs hover:bg-blue-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $bank }}</button>
                                    @endforeach
                                    <button type="button" x-show="showCustom" @click="$wire.set('sourceBankName', search)" class="w-full px-3 py-2 text-left text-xs hover:bg-blue-50 dark:hover:bg-slate-800 text-blue-600 dark:text-blue-400 font-semibold border-t border-slate-100 dark:border-slate-700">
                                        {{ __('Use') }} "<span x-text="search"></span>"
                                    </button>
                                </div>
                                <input type="hidden" wire:model="sourceBankName"/>
                            </div>
                            @error('sourceBankName') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Source Account Number -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Your Bank Account Number (Optional)') }}</label>
                            <input type="text" wire:model="sourceAccountNumber" placeholder="{{ __('Account number you paid from') }}" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500"/>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Notes (Optional)') }}</label>
                            <textarea wire:model="notes" rows="2" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:ring-blue-500 focus:border-blue-500" placeholder="{{ __('Any additional details about this payment...') }}"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('Proof of Payment (JPG, PNG, PDF — max 5MB)') }}</label>
                            <input type="file" wire:model="uploadedReceiptFile" accept=".jpeg,.jpg,.png,.pdf" class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white file:mr-2 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-1.5 file:text-[10px] file:font-bold file:text-white"/>
                            @error('uploadedReceiptFile') <span class="text-[10px] text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-3 text-xs font-extrabold text-white shadow-lg shadow-blue-600/25 transition-all">
                            <x-heroicon-o-paper-airplane class="h-4 w-4"/>
                            {{ __('Submit Deposit Proof') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Submission History -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Payment Submission History') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Date') }}</th>
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Invoice') }}</th>
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Method') }}</th>
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Source Bank') }}</th>
                                <th class="py-2.5 pr-3 font-semibold text-right">{{ __('Amount') }}</th>
                                <th class="py-2.5 font-semibold">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($submissions as $sub)
                                <tr class="border-b border-slate-50 dark:border-slate-800/60">
                                    <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $sub->created_at?->format('d M Y H:i') }}</td>
                                    <td class="py-3 pr-3 font-mono text-slate-700 dark:text-slate-300">{{ $sub->invoice?->invoice_number ?? '—' }}</td>
                                    <td class="py-3 pr-3">
                                        @if($sub->gateway === 'paynow')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                <x-heroicon-o-signal class="h-2.5 w-2.5"/> Paynow
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                <x-heroicon-o-building-library class="h-2.5 w-2.5"/> Bank Deposit
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $sub->source_bank_name ?? $sub->bank_name ?? '—' }}</td>
                                    <td class="py-3 pr-3 text-right font-semibold text-slate-700 dark:text-slate-300">${{ number_format((float) $sub->amount, 2) }}</td>
                                    <td class="py-3">
                                        @if($sub->status === 'approved')
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('APPROVED') }}</span>
                                        @elseif($sub->status === 'rejected')
                                            <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300" title="{{ $sub->rejection_reason }}">{{ __('REJECTED') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('PENDING') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-slate-400">{{ __('No payment submissions yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @endif
    </div>

    @script
    <script>
        $wire.on('open-paynow-tab', ({url}) => {
            window.open(url, '_blank');
        });
    </script>
    @endscript
</x-filament-panels::page>
