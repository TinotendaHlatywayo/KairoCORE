<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-base font-extrabold text-slate-900 dark:text-white">{{ $invoice->invoice_number }}</p>
            <p class="text-[11px] text-slate-400">{{ $invoice->school?->name }}</p>
        </div>
        <div class="text-right text-[11px] text-slate-500">
            <p>${{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</p>
            <p>{{ $invoice->status }}</p>
        </div>
    </div>

    <iframe src="{{ route('saas.invoice.view', $invoice->uuid) }}" class="h-[520px] w-full rounded-lg border border-slate-200 dark:border-slate-700"></iframe>

    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('saas.invoice.download', $invoice->uuid) }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
            <x-heroicon-o-arrow-down-tray class="h-3 w-3"/>
            {{ __('Download PDF') }}
        </a>
    </div>
</div>