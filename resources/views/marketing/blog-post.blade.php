@extends('marketing.layout')

@section('title', $post->title.' - '.platform_name())
@section('meta_description', $post->seo_description ?: \Illuminate\Support\Str::limit((string) $post->excerpt, 160))

@push('styles')
@include('marketing.partials.animations')
<style>
    /* Editorial styling for the sanitized blog body (plain CSS: inline block
       is not processed by Tailwind, so no @apply here). */
    .blog-content { font-size: 1.0625rem; line-height: 1.9; color: #334155; }
    .blog-content p { margin-bottom: 1.25rem; }
    .blog-content h1 { margin: 2.5rem 0 1rem; font-size: 1.875rem; font-weight: 800; letter-spacing: -0.02em; color: #0F172A; }
    .blog-content h2 { margin: 2.25rem 0 1rem; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; color: #0F172A; }
    .blog-content h3 { margin: 1.75rem 0 0.75rem; font-size: 1.25rem; font-weight: 700; color: #0F172A; }
    .blog-content h4, .blog-content h5, .blog-content h6 { margin: 1.5rem 0 0.75rem; font-size: 1.125rem; font-weight: 700; color: #0F172A; }
    .blog-content a { font-weight: 600; color: #D94B39; text-decoration: underline; text-decoration-color: rgba(217,75,57,.4); text-underline-offset: 2px; }
    .blog-content a:hover { text-decoration-color: #D94B39; }
    .blog-content strong, .blog-content b { font-weight: 700; color: #0F172A; }
    .blog-content ul { margin-bottom: 1.25rem; padding-left: 1.5rem; list-style: disc; }
    .blog-content ol { margin-bottom: 1.25rem; padding-left: 1.5rem; list-style: decimal; }
    .blog-content li { margin-bottom: 0.5rem; }
    .blog-content blockquote { margin: 1.5rem 0; border-left: 4px solid #D94B39; background: #F8FAFC; padding: 1rem 1.25rem; font-style: italic; color: #475569; }
    .blog-content pre { margin: 1.5rem 0; overflow-x: auto; border-radius: 0.75rem; background: #0F172A; padding: 1.25rem; font-size: 0.875rem; color: #F1F5F9; }
    .blog-content code { border-radius: 0.375rem; background: #F1F5F9; padding: 0.125rem 0.375rem; font-size: 0.875rem; color: #1E293B; }
    .blog-content img { margin: 1.5rem 0; width: 100%; height: auto; border-radius: 1rem; border: 1px solid #E2E8F0; }
    .blog-content figure { margin: 1.5rem 0; }
    .blog-content figcaption { margin-top: 0.5rem; text-align: center; font-size: 0.875rem; color: #64748B; }
    .blog-content hr { margin: 2rem 0; border: 0; border-top: 1px solid #E2E8F0; }
    html.dark .blog-content { color: #CBD5E1; }
    html.dark .blog-content h1, html.dark .blog-content h2, html.dark .blog-content h3,
    html.dark .blog-content h4, html.dark .blog-content h5, html.dark .blog-content h6,
    html.dark .blog-content strong, html.dark .blog-content b { color: #F1F5F9; }
    html.dark .blog-content blockquote { background: rgba(15,23,42,.6); color: #CBD5E1; }
    html.dark .blog-content code { background: #1E293B; color: #E2E8F0; }
    html.dark .blog-content img { border-color: #1E293B; }
    html.dark .blog-content hr { border-top-color: #1E293B; }
</style>
@endpush

@section('content')
<article class="bg-white pb-16 pt-10 dark:bg-[#0B1120]">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumbs --}}
        <nav class="reveal flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
            @foreach($breadcrumbs as $crumb)
                @if(!$loop->last)
                    <a href="{{ $crumb['url'] }}" class="hover:text-[#D94B39] transition-colors">{{ $crumb['name'] }}</a>
                    <span aria-hidden="true">/</span>
                @else
                    <span class="text-slate-400 dark:text-slate-500 line-clamp-1">{{ $crumb['name'] }}</span>
                @endif
            @endforeach
        </nav>

        <header class="reveal reveal-d1 mt-6">
            <time datetime="{{ optional($post->published_at)->toDateString() }}" class="inline-block rounded-full bg-[#D94B39]/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-[#D94B39]">
                {{ optional($post->published_at)->format('d M Y') }}
            </time>
            <h1 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 leading-[1.12] dark:text-white">{{ $post->title }}</h1>
            @if($post->excerpt)
                <p class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-300">{{ $post->excerpt }}</p>
            @endif
        </header>

        @if($post->cover_image)
            <div class="reveal reveal-d2 mt-8 overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
                <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" class="h-auto w-full object-cover" width="1200" height="675" loading="eager" decoding="async">
            </div>
        @endif

        <div class="reveal reveal-d3 blog-content mt-9">
            {!! $content !!}
        </div>

        <div class="mt-12 flex flex-col items-center gap-4 rounded-3xl border border-slate-200/80 bg-slate-50 p-8 text-center sm:flex-row sm:justify-between sm:text-left dark:border-slate-800 dark:bg-[#0F172A]">
            <div>
                <p class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Liked this article?') }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Run your whole school on Kairo CORE.') }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('marketing.blog') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-white transition-all dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Back to Blog') }}</a>
                <a href="{{ route('register') }}" class="rounded-xl bg-[#D94B39] px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-[#D94B39]/25 hover:bg-[#EF5F4D] transition-all">{{ __('Register School') }}</a>
            </div>
        </div>
    </div>
</article>
@endsection
