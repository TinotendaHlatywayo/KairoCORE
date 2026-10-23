@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: platform_name().' - Features')
@section('meta_description', marketing_content('meta_description') ?: __('Explore all features of the school management system - admissions, academics, finance, HR, communications, attendance, library and more.'))
@section('seo_image', asset('images/Features_thumbnail.jpg'))

@push('styles')
@include('marketing.partials.animations')
@endpush

@section('content')
@php
    $features = [];
    foreach (marketing_merged_list('features_list') as $i => $item) {
        $features[] = [
            'key' => $item['key'] ?? 'feature-'.$i,
            'cat' => $item['cat'] ?? 'admin',
            'img' => marketing_resolve_image($item['image'] ?? ''),
            'name' => $item['name'] ?? '',
            'desc' => $item['desc'] ?? '',
            'detail' => $item['detail'] ?? '',
            'subs' => array_values(array_filter([$item['sub1'] ?? '', $item['sub2'] ?? '', $item['sub3'] ?? ''])),
        ];
    }
@endphp

<section class="relative overflow-hidden bg-white pb-4 pt-14 lg:pt-20" x-data="featureExplorer()">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 left-1/4 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ marketing_content('features_badge') }}</p>
            <h1 class="reveal reveal-d1 mt-4 text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-[1.08]">{{ marketing_content('features_title') }}</h1>
            <p class="reveal reveal-d2 mt-5 text-lg leading-8 text-slate-600">{{ marketing_content('features_subtitle') }}</p>
        </div>

        {{-- Category filter --}}
        <div class="reveal reveal-d3 mt-9 flex flex-wrap justify-center gap-2">
            <template x-for="f in filters" :key="f.id">
                <button type="button" @click="active = f.id"
                    class="rounded-full border px-4 py-2 text-xs font-bold transition-colors"
                    :class="active === f.id ? 'border-[#1F2E43] bg-[#1F2E43] text-white shadow-md shadow-[#1F2E43]/20' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                    x-text="f.label"></button>
            </template>
        </div>

        {{-- Feature grid --}}
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($features as $i => $f)
                <article class="reveal card-lift group flex flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm"
                    style="transition-delay: {{ ($i % 3) * 0.08 }}s"
                    x-show="active === 'all' || active === '{{ $f['cat'] }}'"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <div class="relative aspect-[16/9] w-full overflow-hidden bg-slate-100">
                        <img src="{{ $f['img'] }}" alt="{{ $f['name'] }}" class="zoom-img h-full w-full object-cover" loading="lazy" decoding="async">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/35 via-transparent to-transparent"></div>
                        <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-700 backdrop-blur">{{ $f['cat'] === 'academic' ? __('Academic') : ($f['cat'] === 'admin' ? __('Administration') : __('Campus life')) }}</span>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <h3 class="text-base font-bold text-slate-900">{{ $f['name'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $f['desc'] }}</p>
                        <ul class="mt-4 space-y-2">
                            @foreach($f['subs'] as $sub)
                                <li class="flex items-center gap-2 text-sm text-slate-600">
                                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#EF5F4D]/12 text-[#EF5F4D]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </span>
                                    <span>{{ $sub }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <button type="button" @click="open = {{ $i }}; document.body.style.overflow='hidden'"
                            class="mt-5 inline-flex items-center gap-1.5 self-start rounded-xl border-2 border-[#1F2E43]/10 bg-[#1F2E43]/[0.04] px-4 py-2 text-xs font-bold text-[#1F2E43] transition hover:border-[#1F2E43]/25 hover:bg-[#1F2E43]/[0.08]">
                            {{ __('Explore feature') }}
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Explore modal --}}
        <div x-show="open !== null" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="close()"
             @keydown.escape.window="close()" style="display:none">
            <div x-show="open !== null" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-6 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="relative flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl" role="dialog" aria-modal="true">
                <button type="button" @click="close()" class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-600 shadow-md backdrop-blur transition hover:bg-white hover:text-slate-900" aria-label="{{ __('Close') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <template x-if="current">
                    <div class="flex flex-1 flex-col overflow-y-auto">
                        <div class="relative h-52 w-full shrink-0 overflow-hidden bg-slate-100 sm:h-64">
                            <img :src="current.img" :alt="current.name" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/55 via-slate-900/10 to-transparent"></div>
                            <div class="absolute bottom-4 left-5 right-5">
                                <span class="rounded-full bg-white/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white backdrop-blur" x-text="catLabel(current.cat)"></span>
                                <h3 class="mt-2 text-2xl font-extrabold text-white" x-text="current.name"></h3>
                            </div>
                        </div>
                        <div class="p-6 sm:p-8">
                            <p class="text-sm leading-7 text-slate-600" x-text="current.detail"></p>
                            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                <template x-for="s in current.subs" :key="s">
                                    <div class="flex items-start gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#EF5F4D]/12 text-[#EF5F4D]">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        </span>
                                        <span class="text-xs font-semibold leading-5 text-slate-700" x-text="s"></span>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-7 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                                <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-5 py-3 text-xs font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ marketing_content('features_cta_primary_label') }}</a>
                                <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 px-5 py-3 text-xs font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition-colors">{{ __('Ask us about this feature') }}</a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16 mt-4">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="reveal text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ marketing_content('features_cta_title') }}</h2>
        <p class="reveal reveal-d1 mt-4 text-lg text-slate-600">{{ marketing_content('features_cta_subtitle') }}</p>
        <div class="reveal reveal-d2 mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ marketing_content('features_cta_primary_label') }}</a>
            <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 px-6 py-3.5 text-sm font-bold text-slate-700 hover:bg-white transition-colors">{{ marketing_content('features_cta_secondary_label') }}</a>
        </div>
    </div>
</section>

<script>
function featureExplorer() {
    return {
        open: null,
        active: 'all',
        filters: [
            { id: 'all', label: @json(__('All features')) },
            { id: 'academic', label: @json(__('Academic')) },
            { id: 'admin', label: @json(__('Administration')) },
            { id: 'campus', label: @json(__('Campus life')) },
        ],
        features: @json($features),
        get current() { return this.open !== null ? this.features[this.open] : null; },
        catLabel(c) {
            if (c === 'academic') return @json(__('Academic'));
            if (c === 'admin') return @json(__('Administration'));
            return @json(__('Campus life'));
        },
        close() { this.open = null; document.body.style.overflow = ''; },
    };
}
</script>
@endsection
