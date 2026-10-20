@props(['src', 'poster' => '', 'alt' => '', 'class' => '', 'aspectRatio' => '4/3'])
@php
    $mediaData = is_string($src) ? json_decode($src, true) : $src;
    if (!is_array($mediaData)) {
        $mediaData = ['type' => 'legacy', 'desktop' => $src, 'mobile' => $src, 'poster' => $poster];
    }
    $type = $mediaData['type'] ?? 'legacy';
    $isVideo = in_array($type, ['video', 'mp4'], true);
    $desktopUrl = marketing_resolve_image($mediaData['desktop'] ?? ($mediaData['url'] ?? $src));
    $mobileUrl = marketing_resolve_image($mediaData['mobile'] ?? $desktopUrl);
    $posterUrl = marketing_resolve_image($mediaData['poster'] ?? $poster);
    if ($posterUrl === '') {
        $posterUrl = $desktopUrl;
    }
@endphp

<div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-100 shadow-xl {{ $class }}" style="aspect-ratio: {{ $aspectRatio }};" @if($isVideo) x-data="mediaVideoPlayer({ desktop: '{{ $desktopUrl }}', mobile: '{{ $mobileUrl }}', type: '{{ $type }}' })" x-init="init()" @endif>
    {{-- Poster LCP image --}}
    <img src="{{ $posterUrl }}" alt="{{ $alt }}" class="absolute inset-0 h-full w-full object-cover transition-opacity duration-500" @if($isVideo) :class="{ 'opacity-0': playing }" @endif fetchpriority="high" loading="eager" width="1280" height="720">

    @if($isVideo)
    {{-- Video element --}}
    <template x-if="loadVideo">
        <video x-ref="video" class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-700" :class="{ 'opacity-100': playing }" muted loop playsinline preload="metadata" aria-hidden="true" x-on:canplaythrough="onCanPlay" x-on:error="onError">
            <source :src="isMobile ? mobileSrc : desktopSrc" type="video/mp4">
        </video>
    </template>

    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/25 via-transparent to-transparent pointer-events-none"></div>

    <button type="button" x-show="showPlayButton" x-cloak @click="manualPlay()" class="absolute inset-0 m-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-900/70 text-white shadow-2xl backdrop-blur-sm transition-transform hover:scale-110 focus:outline-none" aria-label="{{ __('Play video') }}">
        <svg class="h-8 w-8 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
    </button>
    @endif
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mediaVideoPlayer', (config) => ({
        desktopSrc: config.desktop,
        mobileSrc: config.mobile,
        mediaType: config.type,
        loadVideo: false,
        playing: false,
        showPlayButton: false,
        isMobile: window.matchMedia('(max-width: 768px)').matches,
        init() {
            const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            const saveData = conn && conn.saveData;
            const slowConn = conn && (conn.effectiveType === '2g' || conn.effectiveType === 'slow-2g');
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (saveData || slowConn || reducedMotion) {
                return;
            }

            const loadHandler = () => {
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(() => { this.loadVideo = true; }, { timeout: 2000 });
                } else {
                    setTimeout(() => { this.loadVideo = true; }, 500);
                }
                this.setupIntersectionObserver();
            };

            if (document.readyState === 'complete') {
                loadHandler();
            } else {
                window.addEventListener('load', loadHandler, { once: true });
            }
        },
        setupIntersectionObserver() {
            this.$nextTick(() => {
                const el = this.$el;
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && entry.intersectionRatio >= 0.25) {
                            this.tryPlay();
                        } else {
                            this.pauseVideo();
                        }
                    });
                }, { threshold: [0, 0.25, 0.5] });
                observer.observe(el);
            });
        },
        tryPlay() {
            if (!this.loadVideo) {
                this.loadVideo = true;
            }
            this.$nextTick(() => {
                const vid = this.$refs.video;
                if (!vid) return;
                vid.muted = true;
                const p = vid.play();
                if (p !== undefined) {
                    p.then(() => {
                        this.playing = true;
                        this.showPlayButton = false;
                    }).catch(() => {
                        this.showPlayButton = true;
                    });
                }
            });
        },
        pauseVideo() {
            const vid = this.$refs.video;
            if (vid && !vid.paused) {
                vid.pause();
                this.playing = false;
            }
        },
        manualPlay() {
            const vid = this.$refs.video;
            if (!vid) return;
            vid.muted = true;
            vid.play().then(() => {
                this.playing = true;
                this.showPlayButton = false;
            }).catch(() => {
                this.showPlayButton = true;
            });
        },
        onCanPlay() {
            this.tryPlay();
        },
        onError() {
            this.showPlayButton = true;
            this.loadVideo = false;
        }
    }));
});
</script>
@endpush
@endonce
