@extends('marketing.layout')

@section('body_class', 'kairo-v2')

@section('title', __('Blog').' - '.platform_name())
@section('meta_description', __('Practical insights on running a modern school: admissions, fees, academics, security and product updates from the Kairo CORE team.'))

@push('styles')
@vite('resources/css/kairo-v2/blog.css')
@endpush
@push('styles')
<style>body.kairo-v2 section { padding-top: 0px !important; }</style>
@endpush

@section('content')
<div class="wrap">
    <section class="hero">
        <span class="pill">{{ marketing_content('blog_badge') }}</span>
        <h1>{{ marketing_content('blog_title') }}</h1>
        <p>{{ marketing_content('blog_intro') }}</p>
    </section>

    <div class="tools">
        <div class="chips" aria-label="{{ __('Filter articles by category') }}">
            <a class="chip @if(!$activeCategory) on @endif" href="{{ route('marketing.blog', request()->except('category', 'page')) }}">{{ __('All') }}</a>
            @foreach ($categories as $category)
                <a class="chip @if($activeCategory === $category) on @endif" href="{{ route('marketing.blog', array_merge(request()->except('page'), ['category' => $category])) }}">{{ __($category) }}</a>
            @endforeach
        </div>
        <form class="search-wrap" method="GET" action="{{ route('marketing.blog') }}">
            <label class="sr-only" for="blogSearch">{{ __('Search articles') }}</label>
            @if($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
            <input class="search" id="blogSearch" name="q" type="search" value="{{ $search }}" placeholder="{{ __('Search articles') }}">
        </form>
    </div>

    @if($posts->isEmpty())
        <div class="empty">{{ $search !== '' || $activeCategory !== '' ? __('No articles match your search. Try a different word.') : __('No posts published yet — check back soon.') }}</div>
    @else
        <div class="grid" id="grid">
            @foreach($posts as $post)
                <article class="card" data-blog-card data-search="{{ strtolower($post->title.' '.$post->excerpt.' '.$post->category) }}">
                    <a class="cover-link" href="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}" aria-label="{{ $post->title }}">
                        <div style="position:relative">
                            @if($post->cover_image)
                                <div class="cover"><img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}"></div>
                            @else
                                <div class="cover"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/></svg></div>
                            @endif
                            <span class="date">{{ optional($post->published_at)->format('d M Y') }}</span>
                        </div>
                    </a>
                    <div class="cbody">
                        <span class="meta">{{ __($post->category ?: 'Insights') }} · {{ $post->readingMinutes() }} {{ __('min read') }}</span>
                        <h3><a href="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a></h3>
                        <p>{{ \Illuminate\Support\Str::limit((string) $post->excerpt, 160) }}</p>
                        <div class="cfoot">
                            <a class="read" href="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}">{{ __('Read article') }} →</a>
                            <button class="like" type="button" data-like="{{ route('marketing.blog.like', ['slug' => $post->slug]) }}" aria-label="{{ __('Like this article') }}">
                                <svg viewBox="0 0 24 24"><path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/></svg>
                                <span data-like-count>{{ $post->likesCount() }}</span>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="empty" id="blogNoResults" hidden>{{ __('No articles match your search. Try a different word.') }}</div>

        <div class="mt-8">
            {{ $posts->links() }}
        </div>
    @endif

    <div class="cta">
        <h2>{{ marketing_content('blog_cta_title') }}</h2>
        <p>{{ marketing_content('blog_cta_subtitle') }}</p>
        <div class="row">
            <a class="btn pri" href="{{ route('register') }}">{{ __('Register School') }}</a>
            <a class="btn" href="{{ route('marketing.features') }}">{{ __('Explore Features') }}</a>
        </div>
    </div>
</div>
@endsection
