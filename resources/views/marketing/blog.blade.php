@extends('marketing.layout')

@section('title', __('Blog').' - '.platform_name())
@section('meta_description', __('Practical insights on running a modern school: admissions, fees, academics, security and product updates from the Kairo CORE team.'))

@push('styles')
@include('marketing.partials.animations')
@endpush

@section('content')
<section class="relative overflow-hidden bg-white pb-16 pt-14 lg:pt-20 dark:bg-[#0B1120]">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 right-1/4 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Insights & Updates') }}</p>
            <h1 class="reveal reveal-d1 mt-4 text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-[1.08] dark:text-white">{{ __('The Kairo CORE Blog') }}</h1>
            <p class="reveal reveal-d2 mt-5 text-lg leading-8 text-slate-600 dark:text-slate-300">{{ __('Practical advice on admissions, fees, academics and security — plus news from the product team.') }}</p>
        </div>

        @if($posts->isEmpty())
            <div class="reveal reveal-d3 mx-auto mt-14 max-w-xl rounded-3xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-700">
                <p class="text-lg font-semibold text-slate-700 dark:text-slate-300">{{ __('No posts published yet — check back soon.') }}</p>
            </div>
        @else
            <div class="mt-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $i => $post)
                    <article class="reveal card-lift group flex flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition-all dark:border-slate-800 dark:bg-[#0F172A]"
                             style="transition-delay: {{ ($i % 3) * 0.08 }}s">
                        <a href="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}" class="relative block aspect-[16/9] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                            <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" class="zoom-img h-full w-full object-cover" loading="lazy" decoding="async">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/35 via-transparent to-transparent"></div>
                            <time datetime="{{ optional($post->published_at)->toDateString() }}"
                                  class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-700 backdrop-blur dark:bg-slate-900/80 dark:text-slate-200">
                                {{ optional($post->published_at)->format('d M Y') }}
                            </time>
                        </a>
                        <div class="flex flex-1 flex-col p-6">
                            <h2 class="text-lg font-bold leading-snug text-slate-900 dark:text-white">
                                <a href="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}" class="hover:text-[#D94B39] transition-colors">{{ $post->title }}</a>
                            </h2>
                            <p class="mt-3 flex-1 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ \Illuminate\Support\Str::limit((string) $post->excerpt, 160) }}</p>
                            <a href="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}"
                               class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold text-[#D94B39] hover:gap-2.5 transition-all">
                                {{ __('Read article') }}
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        @endif

        <div class="reveal reveal-d3 mt-16 flex flex-col items-center gap-5 rounded-3xl border border-slate-200/80 bg-slate-50 p-10 text-center dark:border-slate-800 dark:bg-[#0F172A]">
            <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ __('See Kairo CORE in action') }}</h2>
            <p class="max-w-xl text-slate-600 dark:text-slate-300">{{ __('Create a free school workspace and explore every feature — no credit card required.') }}</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-magnetic rounded-xl bg-[#D94B39] px-6 py-3 text-sm font-bold text-white shadow-md shadow-[#D94B39]/25 hover:bg-[#EF5F4D] transition-all">{{ __('Register School') }}</a>
                <a href="{{ route('marketing.features') }}" class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-bold text-slate-700 hover:bg-white transition-all dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Explore Features') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
