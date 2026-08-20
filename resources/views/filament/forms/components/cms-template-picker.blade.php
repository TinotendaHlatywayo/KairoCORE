<div class="space-y-3" x-data>
    <div>
        <p class="text-sm font-semibold text-slate-900 dark:text-zinc-100">{{ __('Choose a premium starting point') }}</p>
        <p class="mt-1 text-sm text-slate-600 dark:text-zinc-400">{{ __('Each option is a distinct website direction. Your pages, media and SEO remain intact when you change it later.') }}</p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($templates as $key => $template)
            <label wire:key="cms-template-{{ $key }}" class="group relative block cursor-pointer">
                <input type="radio" value="{{ $key }}" wire:model.live="data.active_template" class="peer sr-only">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 ease-out group-hover:-translate-y-1 group-hover:border-indigo-400 group-hover:shadow-md peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:group-hover:border-indigo-400">
                    <div class="relative h-40 overflow-hidden p-4" style="background: linear-gradient(135deg, {{ $template['palette']['background'] }} 0%, {{ $template['palette']['card_bg'] }} 100%);">
                        <div class="flex items-center justify-between">
                            <span class="h-2 w-16 rounded-full" style="background-color: {{ $template['palette']['primary'] }}"></span>
                            <span class="h-2 w-8 rounded-full" style="background-color: {{ $template['palette']['accent'] }}"></span>
                        </div>
                        @if($key === 'minimalist-academic')
                            <div class="mt-6 border-y border-slate-400/40 py-3 text-center"><span class="mx-auto block h-2 w-2/3 rounded" style="background:{{ $template['palette']['primary'] }}"></span><span class="mx-auto mt-2 block h-1 w-1/2 rounded bg-slate-500/40"></span></div><div class="mt-3 grid grid-cols-3 gap-2"><span class="h-7 border border-slate-400/30"></span><span class="h-7 border border-slate-400/30"></span><span class="h-7 border border-slate-400/30"></span></div>
                        @elseif($key === 'community-warm')
                            <div class="mt-5 rounded-[1.4rem] p-4" style="background:{{ $template['palette']['primary'] }}"><span class="block h-2 w-3/4 rounded bg-white/90"></span><span class="mt-2 block h-1 w-full rounded bg-white/50"></span><span class="mt-4 block h-5 w-20 rounded-full" style="background:{{ $template['palette']['accent'] }}"></span></div>
                        @elseif($key === 'cinematic-immersive')
                            <div class="mt-5 grid grid-cols-4 gap-2"><div class="col-span-3 rounded-lg border p-3" style="border-color:{{ $template['palette']['primary'] }}; background:rgba(6,182,212,.1)"><span class="block h-2 w-4/5 rounded" style="background:{{ $template['palette']['primary'] }}"></span><span class="mt-3 block h-1 w-full rounded bg-white/30"></span></div><div class="rounded-lg" style="background:linear-gradient(145deg,{{ $template['palette']['secondary'] }},{{ $template['palette']['accent'] }})"></div></div><div class="mt-2 flex gap-2"><span class="h-1 flex-1" style="background:{{ $template['palette']['primary'] }}"></span><span class="h-1 flex-1" style="background:{{ $template['palette']['secondary'] }}"></span></div>
                        @elseif($key === 'modern-vibrant')
                            <div class="mt-5 flex items-end gap-2"><span class="h-14 flex-1 rounded-t-[1.5rem]" style="background:{{ $template['palette']['primary'] }}"></span><span class="h-9 flex-1 rounded-t-[1.5rem]" style="background:{{ $template['palette']['secondary'] }}"></span><span class="h-12 flex-1 rounded-t-[1.5rem]" style="background:{{ $template['palette']['accent'] }}"></span></div><div class="mt-2 flex justify-center gap-2"><span class="h-2 w-2 rounded-full" style="background:{{ $template['palette']['primary'] }}"></span><span class="h-2 w-2 rounded-full" style="background:{{ $template['palette']['secondary'] }}"></span><span class="h-2 w-2 rounded-full" style="background:{{ $template['palette']['accent'] }}"></span></div>
                        @else
                            <div class="mt-5 grid grid-cols-5 gap-2"><div class="col-span-3 rounded-xl p-3" style="background-color: {{ $template['palette']['primary'] }}"><span class="block h-2 w-3/4 rounded bg-white/90"></span><span class="mt-3 block h-1 w-full rounded bg-white/50"></span><span class="mt-1 block h-1 w-4/5 rounded bg-white/50"></span></div><div class="col-span-2 rounded-xl" style="background:linear-gradient(145deg,{{ $template['palette']['secondary'] }},{{ $template['palette']['accent'] }})"></div></div>
                        @endif
                        <span class="absolute bottom-3 right-3 rounded-full bg-slate-950/75 px-2 py-1 text-[9px] font-bold text-white">{{ __('Full site') }}</span>
                    </div>
                    <div class="p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-zinc-100">{{ $template['name'] }}</p>
                                <p class="mt-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300">{{ $template['subtitle'] }}</p>
                            </div>
                            <span class="rounded-full border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-700 peer-checked:bg-indigo-600 peer-checked:text-white dark:border-zinc-700 dark:text-zinc-300">{{ __('Select') }}</span>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-zinc-400">{{ $template['description'] }}</p>
                    </div>
                </div>
            </label>
        @endforeach
    </div>
</div>
