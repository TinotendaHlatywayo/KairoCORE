<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('No Student Record') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Link your student profile to view your residence.') }}</p>
            </div>
        @elseif(! $allocation)
            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <x-heroicon-o-home-modern class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600"/>
                <h2 class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('No Residence Allocated') }}</h2>
                <p class="mt-1 text-xs text-slate-400">{{ __('You have not been assigned a hostel bed yet. Contact the boarding office if you expect to be boarded.') }}</p>
            </div>
        @else
            @php
                $bed = $allocation->bed;
                $room = $bed?->room;
                $hostel = $room?->hostel;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                        <x-heroicon-o-home-modern class="h-7 w-7"/>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('Hostel') }}</p>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $hostel?->name ?? '—' }}</h2>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Wing') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $room?->wing?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Floor') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $room?->floor?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Room') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $room?->room_number ?? $room?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Bed Number') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $bed?->bed_number ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold
                    @if($allocation->status === 'active') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                    @else bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 @endif">
                    {{ strtoupper($allocation->status ?? 'active') }}
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>