{{-- File: resources/views/components/auth-login-scene.blade.php --}}

@php
    // Walking tracks with staggered durations, delays, depths (scale), and opacity
    $walkers = [
        ['dur' => 26, 'delay' => -4,  'bottom' => 24, 'scale' => 1.05, 'op' => 0.95, 'kind' => 'female'],
        ['dur' => 34, 'delay' => -14, 'bottom' => 31, 'scale' => 0.75, 'op' => 0.70, 'kind' => 'male'],
        ['dur' => 21, 'delay' => -1,  'bottom' => 21, 'scale' => 1.25, 'op' => 1.00, 'kind' => 'male'],
        ['dur' => 29, 'delay' => -19, 'bottom' => 27, 'scale' => 0.82, 'op' => 0.80, 'kind' => 'female'],
        ['dur' => 38, 'delay' => -24, 'bottom' => 34, 'scale' => 0.65, 'op' => 0.60, 'kind' => 'female'],
        ['dur' => 23, 'delay' => -9,  'bottom' => 22, 'scale' => 1.18, 'op' => 1.00, 'kind' => 'female'],
        ['dur' => 31, 'delay' => -11, 'bottom' => 29, 'scale' => 0.78, 'op' => 0.75, 'kind' => 'male'],
    ];
@endphp

<div class="sc-login-scene" aria-hidden="true">
    <div class="sc-sky"></div>

    {{-- Subtle background atmospheric orbs --}}
    <div class="sc-orb sc-orb-1" :style="'transform: translate(' + (mx * -12) + 'px, ' + (my * -12) + 'px)'"></div>
    <div class="sc-orb sc-orb-2" :style="'transform: translate(' + (mx * 16) + 'px, ' + (my * 16) + 'px)'"></div>
    <div class="sc-orb sc-orb-3" :style="'transform: translate(' + (mx * -8) + 'px, ' + (my * -8) + 'px)'"></div>

    {{-- Floating scholastic elements --}}
    <div class="sc-floaty sc-floaty-cap" :style="'transform: translate(' + (mx * 10) + 'px, ' + (my * 10) + 'px)'">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M24 8 2 18l22 10 22-10-22-10Z" fill="currentColor"/>
            <path d="M42 20v9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
            <circle cx="44" cy="31" r="2.4" fill="currentColor"/>
        </svg>
    </div>
    <div class="sc-floaty sc-floaty-book" :style="'transform: translate(' + (mx * -14) + 'px, ' + (my * -14) + 'px)'">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 12c8-3 16-3 18 2v24c-2-5-10-5-18-2V12Z" fill="currentColor" opacity="0.8"/>
            <path d="M42 12c-8-3-16-3-18 2v24c2-5 10-5 18-2V12Z" fill="currentColor" opacity="0.5"/>
        </svg>
    </div>

    {{-- Tiny floating ambient sparks --}}
    <span class="sc-spark sc-spark-1"></span>
    <span class="sc-spark sc-spark-2"></span>
    <span class="sc-spark sc-spark-3"></span>
    <span class="sc-spark sc-spark-4"></span>

    {{-- Campus architectural layout silhouette --}}
    <div class="sc-campus">
        <svg viewBox="0 0 1440 200" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg">
            <g fill="currentColor">
                <rect x="-10" y="126" width="190" height="80"/>
                <polygon points="15,126 85,84 155,126"/>
                <rect x="212" y="104" width="250" height="100"/>
                <polygon points="232,104 337,56 442,104"/>
                <rect x="296" y="72" width="82" height="32"/>
                <rect x="516" y="114" width="208" height="90"/>
                <polygon points="536,114 620,72 700,114"/>
                <rect x="776" y="92" width="268" height="112"/>
                <polygon points="796,92 910,40 1024,92"/>
                <rect x="856" y="62" width="104" height="30"/>
                <rect x="1096" y="118" width="176" height="86"/>
                <polygon points="1116,118 1184,82 1252,118"/>
                <rect x="1286" y="102" width="154" height="102"/>
                <polygon points="1306,102 1363,64 1420,102"/>
                <rect x="10" y="160" width="120" height="46"/>
                <rect x="1090" y="148" width="180" height="56"/>
            </g>
        </svg>
    </div>

    {{-- Interactive student stage depth --}}
    <div
        class="sc-stage"
        :style="'transform: translate(' + (mx * 8).toFixed(1) + 'px, ' + (my * 6).toFixed(1) + 'px);'"
    >
        @foreach ($walkers as $i => $w)
            <div
                class="sc-walker"
                style="--sc-dur: {{ $w['dur'] }}s; --sc-delay: {{ $w['delay'] }}s; --sc-bottom: {{ $w['bottom'] }}vh; --sc-scale: {{ $w['scale'] }}; --sc-op: {{ $w['op'] }};"
            >
                <div class="sc-figure">
                    {{-- Underclassman Layer --}}
                    <div class="sc-layer sc-student">
                        @if ($w['kind'] === 'female')
                            <svg viewBox="0 0 120 160" xmlns="http://www.w3.org/2000/svg">
                                <ellipse cx="60" cy="152" rx="34" ry="5" fill="rgba(15,23,42,0.12)"/>
                                <rect x="34" y="62" width="22" height="36" rx="7" fill="#1e4fb4"/>
                                <rect x="38" y="72" width="14" height="11" rx="3" fill="#2f6ae0"/>
                                <rect x="34" y="60" width="22" height="10" rx="4" fill="#173f8f"/>
                                <path d="M47 100 L39 126 L33 148" stroke="#e9a883" stroke-width="8" stroke-linecap="round" fill="none"/>
                                <path d="M29 144 L40 149 L43 145 Z" fill="#1e293b"/>
                                <path d="M59 100 L67 124 L75 148" stroke="#f2b8a0" stroke-width="8" stroke-linecap="round" fill="none"/>
                                <path d="M73 144 L82 149 L84 145 Z" fill="#1e293b"/>
                                <path d="M42 92 L78 92 L87 126 L33 126 Z" fill="#7048e8"/>
                                <path d="M44 56 L76 56 L81 94 L39 94 Z" fill="#3659c8"/>
                                <path d="M52 56 L68 56 L60 76 Z" fill="#ffffff"/>
                                <path d="M57 56 L51 64 L60 70 L69 64 Z" fill="#f59f00"/>
                                <path d="M79 64 L94 80 L96 94" stroke="#3659c8" stroke-width="7" stroke-linecap="round" fill="none"/>
                                <circle cx="96" cy="98" r="4" fill="#f2b8a0"/>
                                <rect x="64" y="79" width="30" height="7" rx="2" fill="#f76707"/>
                                <rect x="64" y="72" width="30" height="7" rx="2" fill="#ffd43b"/>
                                <circle cx="60" cy="34" r="17" fill="#f2b8a0"/>
                                <path d="M43 34 A17 17 0 0 1 77 34 L77 25 A17 17 0 0 0 43 25 Z" fill="#2b1a12"/>
                                <circle cx="67" cy="32" r="1.7" fill="#0f172a"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 120 160" xmlns="http://www.w3.org/2000/svg">
                                <ellipse cx="60" cy="152" rx="34" ry="5" fill="rgba(15,23,42,0.12)"/>
                                <rect x="34" y="62" width="22" height="36" rx="7" fill="#173f8f"/>
                                <path d="M47 100 L39 126 L33 148" stroke="#1e293b" stroke-width="8" stroke-linecap="round" fill="none"/>
                                <path d="M29 144 L40 149 L43 145 Z" fill="#0f172a"/>
                                <path d="M59 100 L67 124 L75 148" stroke="#1e293b" stroke-width="8" stroke-linecap="round" fill="none"/>
                                <path d="M73 144 L82 149 L84 145 Z" fill="#0f172a"/>
                                <path d="M44 56 L76 56 L81 94 L39 94 Z" fill="#1e4fb4"/>
                                <path d="M52 56 L68 56 L60 76 Z" fill="#ffffff"/>
                                <path d="M60 56 L57 66 L60 72 L63 66 Z" fill="#e11d48"/>
                                <path d="M79 64 L94 80 L96 94" stroke="#1e4fb4" stroke-width="7" stroke-linecap="round" fill="none"/>
                                <circle cx="96" cy="98" r="4" fill="#f2b8a0"/>
                                <rect x="64" y="79" width="30" height="7" rx="2" fill="#f76707"/>
                                <circle cx="60" cy="34" r="17" fill="#f2b8a0"/>
                                <path d="M43 34 A17 17 0 0 1 77 34 L77 22 A17 17 0 0 0 43 22 Z" fill="#1f120c"/>
                                <circle cx="67" cy="32" r="1.7" fill="#0f172a"/>
                            </svg>
                        @endif
                    </div>

                    {{-- Graduate Layer (Crossfades exactly as it emerges on the right side) --}}
                    <div class="sc-layer sc-grad">
                        <svg viewBox="0 0 120 160" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="60" cy="152" rx="34" ry="5" fill="rgba(15,23,42,0.12)"/>
                            <path d="M47 128 L40 146 L35 152" stroke="#1e293b" stroke-width="8" stroke-linecap="round" fill="none"/>
                            <path d="M31 148 L42 153 L45 149 Z" fill="#0f172a"/>
                            <path d="M61 128 L68 146 L73 152" stroke="#1e293b" stroke-width="8" stroke-linecap="round" fill="none"/>
                            <path d="M71 148 L80 153 L82 149 Z" fill="#0f172a"/>
                            <path d="M42 62 L78 62 L90 132 L30 132 Z" fill="#1a2747"/>
                            <path d="M60 62 L60 132" stroke="#25365f" stroke-width="1.5" fill="none"/>
                            <path d="M50 62 L60 74 L70 62 Z" fill="#ffffff"/>
                            <path d="M53 62 L60 71 L67 62 Z" fill="#0ea5e9"/>
                            <path d="M76 70 L94 92 L96 100" stroke="#1a2747" stroke-width="8" stroke-linecap="round" fill="none"/>
                            <circle cx="97" cy="104" r="4" fill="#f2b8a0"/>
                            <g transform="rotate(-20 84 100)">
                                <rect x="84" y="96" width="26" height="9" rx="3" fill="#f6f1e2"/>
                                <rect x="84" y="100" width="26" height="3" fill="#0ea5e9"/>
                            </g>
                            <circle cx="60" cy="36" r="17" fill="#f2b8a0"/>
                            <ellipse cx="60" cy="28" rx="24" ry="7" fill="#0f172a"/>
                            <rect x="56" y="26" width="8" height="12" rx="2" fill="#0f172a"/>
                            <path d="M82 26 L92 18" stroke="#f59f00" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="93" cy="17" r="2.2" fill="#f59f00"/>
                        </svg>
                    </div>
                </div>
                <span class="sc-shadow"></span>
            </div>
        @endforeach
    </div>

    <div class="sc-ground"></div>
</div>

<style>
    .sc-login-scene {
        position: fixed;
        inset: 0;
        z-index: 1;
        overflow: hidden;
        pointer-events: none;
        user-select: none;
    }

    .sc-sky {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(1200px 800px at 15% 15%, rgba(91, 79, 233, 0.12), transparent 60%),
            radial-gradient(1000px 700px at 85% 10%, rgba(16, 185, 129, 0.10), transparent 65%),
            linear-gradient(180deg, #f3f6ff 0%, #eefcff 60%, #f8fafc 100%);
    }

    .dark .sc-sky {
        background:
            radial-gradient(1200px 800px at 15% 15%, rgba(99, 102, 241, 0.18), transparent 50%),
            radial-gradient(1000px 700px at 85% 10%, rgba(16, 185, 129, 0.12), transparent 55%),
            linear-gradient(180deg, #090e1a 0%, #0f1527 50%, #090e1a 100%);
    }

    .sc-orb {
        position: absolute;
        border-radius: 9999px;
        filter: blur(80px);
        opacity: 0.4;
        will-change: transform;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .sc-orb-1 { width: 420px; height: 420px; top: -140px; left: -140px; background: rgba(99, 102, 241, 0.45); }
    .sc-orb-2 { width: 380px; height: 380px; bottom: -120px; right: -80px; background: rgba(16, 185, 129, 0.35); }
    .sc-orb-3 { width: 280px; height: 280px; top: 35%; right: 20%; background: rgba(56, 189, 248, 0.3); }

    .sc-floaty {
        position: absolute;
        color: rgba(91, 79, 233, 0.3);
        opacity: 0.7;
        will-change: transform;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .sc-floaty svg { width: 100%; height: 100%; }
    .sc-floaty-cap { width: 36px; height: 36px; top: 20%; left: 8%; animation: sc-float 10s ease-in-out infinite; }
    .sc-floaty-book { width: 42px; height: 42px; top: 15%; right: 10%; color: rgba(20, 184, 166, 0.35); animation: sc-float 12s ease-in-out infinite 1.5s; }

    @keyframes sc-float {
        0%, 100% { transform: translateY(0) rotate(-4deg); }
        50% { transform: translateY(-12px) rotate(4deg); }
    }

    .sc-stage {
        position: absolute;
        inset: 0;
        will-change: transform;
    }

    .sc-campus {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 38vh;
        color: rgba(15, 23, 42, 0.05);
        z-index: 2;
    }
    .sc-campus svg { width: 100%; height: 100%; display: block; }
    .dark .sc-campus { color: rgba(2, 6, 23, 0.35); }

    .sc-spark {
        position: absolute;
        width: 4px; height: 4px;
        border-radius: 9999px;
        background: rgba(99, 102, 241, 0.4);
        box-shadow: 0 0 6px 2px rgba(99, 102, 241, 0.2);
        animation: sc-spark-drift 14s ease-in-out infinite;
    }
    .sc-spark-1 { top: 22%; left: 14%; }
    .sc-spark-2 { top: 40%; left: 45%; animation-delay: 2s; background: rgba(16, 185, 129, 0.4); }
    .sc-spark-3 { top: 18%; left: 72%; animation-delay: 4s; background: rgba(245, 158, 11, 0.45); }
    .sc-spark-4 { top: 32%; left: 88%; animation-delay: 6s; background: rgba(56, 189, 248, 0.4); }

    @keyframes sc-spark-drift {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
        50% { transform: translate(20px, -24px) scale(1.1); opacity: 0.7; }
    }

    .sc-walker {
        position: absolute;
        left: 0;
        bottom: var(--sc-bottom);
        transform: scale(var(--sc-scale));
        transform-origin: 50% 100%;
        opacity: var(--sc-op);
        animation: sc-walk var(--sc-dur) linear var(--sc-delay) infinite;
        z-index: 5;
    }

    @keyframes sc-walk {
        0% { left: -12%; }
        100% { left: 112%; }
    }

    .sc-figure {
        position: relative;
        width: 120px;
        height: 160px;
        animation: sc-bob 1.2s ease-in-out infinite;
    }

    @keyframes sc-bob {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-5px) rotate(1deg); }
    }

    .sc-layer {
        position: absolute;
        inset: 0;
    }
    .sc-layer svg { width: 100%; height: 100%; display: block; }

    .sc-student { animation: sc-student-fade var(--sc-dur) linear var(--sc-delay) infinite; }
    .sc-grad { animation: sc-grad-fade var(--sc-dur) linear var(--sc-delay) infinite; }

    /*
     * Narrative swap transition timed with the viewport center:
     * - Student fades out exactly as they travel behind the central frosted login card.
     * - Graduate fades in during the exact same interval, emerging as a graduate on the right.
     */
    @keyframes sc-student-fade {
        0%, 43% { opacity: 1; }
        49%, 100% { opacity: 0; }
    }
    @keyframes sc-grad-fade {
        0%, 51% { opacity: 0; }
        57%, 100% { opacity: 1; }
    }

    .sc-shadow {
        position: absolute;
        bottom: -4px;
        left: 50%;
        width: 72px; height: 10px;
        transform: translateX(-50%);
        border-radius: 50%;
        background: radial-gradient(ellipse, rgba(15, 23, 42, 0.16), transparent 70%);
    }

    .sc-ground {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 28vh;
        background: linear-gradient(180deg, transparent 0%, rgba(15, 23, 42, 0.02) 40%, rgba(15, 23, 42, 0.05) 100%);
        z-index: 1;
    }
    .dark .sc-ground {
        background: linear-gradient(180deg, transparent 0%, rgba(2, 6, 23, 0.18) 45%, rgba(2, 6, 23, 0.35) 100%);
    }

    @media (max-width: 768px) {
        .sc-walker {
            animation-duration: calc(var(--sc-dur) * 1.5) !important; /* Move slower on smaller viewports */
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .sc-walker, .sc-student, .sc-grad, .sc-figure, .sc-orb, .sc-floaty, .sc-spark {
            animation: none !important;
            transition: none !important;
        }
        .sc-walker { opacity: 0.35; left: 50% !important; transform: translate(-50%, 0) scale(0.95); }
        .sc-student { opacity: 0.8; }
        .sc-grad { display: none; }
    }
</style>
