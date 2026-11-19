@extends('marketing.layout')

@section('body_class', 'kairo-v2')

@section('title', $post->title.' - '.platform_name())
@section('meta_description', $post->seo_description ?: \Illuminate\Support\Str::limit((string) $post->excerpt, 160))

@push('styles')
@vite('resources/css/kairo-v2/blog.css')
@endpush

@section('content')
<div class="art">
    <div class="crumbs">
        <a href="{{ route('marketing.home') }}">{{ marketing_text('Home') }}</a> / <a href="{{ route('marketing.blog') }}">{{ marketing_text('Blog') }}</a> / {{ $post->title }}
    </div>
    <span class="pill">{{ optional($post->published_at)->format('d M Y') }}</span>
    <h1>{{ $post->title }}</h1>
    @if($post->excerpt)
        <p class="lede">{{ $post->excerpt }}</p>
    @endif
    <div class="byline">
            <span class="meta">{{ marketing_text($post->category ?: 'Insights') }} · {{ $post->readingMinutes() }} {{ marketing_text('min read') }} · {{ marketing_text('Kairo CORE team') }}</span>
        <div class="acts">
            <button class="act like-big" type="button" data-like="{{ route('marketing.blog.like', ['slug' => $post->slug]) }}" aria-pressed="false" data-like-label="{{ marketing_text('Like') }}" data-liked-label="{{ marketing_text('Liked') }}">
                <svg viewBox="0 0 24 24"><path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/></svg>
                <span class="lt">{{ marketing_text('Like') }}</span>
                <em class="cn" data-like-count>{{ $post->likesCount() }}</em>
            </button>
            <button class="act share-big" type="button" data-share data-share-url="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}" data-share-title="{{ $post->title }}" data-share-record="{{ route('marketing.blog.share', ['slug' => $post->slug]) }}" data-copy-label="{{ marketing_text('Copy link') }}" data-copied-label="{{ marketing_text('Link copied') }}" data-whatsapp-label="{{ marketing_text('WhatsApp') }}" data-facebook-label="{{ marketing_text('Facebook') }}" data-x-label="{{ marketing_text('X') }}" aria-haspopup="menu">
                <svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                <span>{{ marketing_text('Share') }} <em class="cn" data-share-count-for="{{ route('marketing.blog.share', ['slug' => $post->slug]) }}">{{ $post->sharesCount() }}</em></span>
            </button>
        </div>
    </div>
    @if($post->cover_image)
        <div class="cover"><img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}"></div>
    @endif
    <div class="prose">
        {!! $content !!}
    </div>
    <div class="endcard">
        <div class="et">
            <strong>{{ marketing_text('Liked this article?') }}</strong>
            <div class="meta">{{ marketing_text('Give it a like, or share it with someone who runs a school.') }}</div>
        </div>
        <div class="acts">
            <button class="act like-big" type="button" data-like="{{ route('marketing.blog.like', ['slug' => $post->slug]) }}" aria-pressed="false" data-like-label="{{ marketing_text('Like') }}" data-liked-label="{{ marketing_text('Liked') }}">
                <svg viewBox="0 0 24 24"><path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/></svg>
                <span class="lt">{{ marketing_text('Like') }}</span>
                <em class="cn" data-like-count>{{ $post->likesCount() }}</em>
            </button>
            <button class="act share-big" type="button" data-share data-share-url="{{ route('marketing.blog.show', ['slug' => $post->slug]) }}" data-share-title="{{ $post->title }}" data-share-record="{{ route('marketing.blog.share', ['slug' => $post->slug]) }}" data-copy-label="{{ marketing_text('Copy link') }}" data-copied-label="{{ marketing_text('Link copied') }}" data-whatsapp-label="{{ marketing_text('WhatsApp') }}" data-facebook-label="{{ marketing_text('Facebook') }}" data-x-label="{{ marketing_text('X') }}" aria-haspopup="menu">
                <svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                <span>{{ marketing_text('Share') }} <em class="cn" data-share-count-for="{{ route('marketing.blog.share', ['slug' => $post->slug]) }}">{{ $post->sharesCount() }}</em></span>
            </button>
        </div>
        <div class="endlinks">
            <a class="btn" href="{{ route('marketing.blog') }}">{{ marketing_text('Back to Blog') }}</a>
            <a class="btn pri" href="{{ route('register') }}">{{ marketing_text('Register School') }}</a>
        </div>
    </div>
</div>

<section class="related">
    <h2>{{ marketing_text('Keep reading') }}</h2>
    <div class="g3">
        @foreach(\App\Models\BlogPost::published()->where('id', '!=', $post->id)->limit(3)->get() as $related)
            <article class="card">
                <a class="cover-link" href="{{ route('marketing.blog.show', ['slug' => $related->slug]) }}" aria-label="{{ $related->title }}">
                    <div style="position:relative">
                        @if($related->cover_image)
                            <div class="cover"><img src="{{ $related->coverUrl() }}" alt="{{ $related->title }}"></div>
                        @else
                            <div class="cover"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/></svg></div>
                        @endif
                        <span class="date">{{ optional($related->published_at)->format('d M Y') }}</span>
                    </div>
                </a>
                <div class="cbody">
                    <span class="meta">{{ optional($related->published_at)->format('d M Y') }}</span>
                    <h3><a href="{{ route('marketing.blog.show', ['slug' => $related->slug]) }}">{{ $related->title }}</a></h3>
                    <p>{{ \Illuminate\Support\Str::limit((string) $related->excerpt, 120) }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>
<div id="toast" role="status"></div>
@endsection
