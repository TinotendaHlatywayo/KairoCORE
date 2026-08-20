{{-- File: resources/views/filament/auth/login-layout.blade.php --}}

@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div
        class="sc-login-scene-wrap"
        x-data="{
            mx: 0,
            my: 0,
            init() {
                const el = this.$el;
                const stage = el.querySelector('.sc-login-stage');
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                let raf = null;
                const onMove = (e) => {
                    const r = el.getBoundingClientRect();
                    const nx = ((e.clientX - r.left) / Math.max(r.width, 1) - 0.5) * 2;
                    const ny = ((e.clientY - r.top) / Math.max(r.height, 1) - 0.5) * 2;
                    stage?.classList.add('sc-login-stage-moving');
                    if (raf) return;
                    raf = requestAnimationFrame(() => {
                        raf = null;
                        this.mx = nx;
                        this.my = ny;
                    });
                };
                const onLeave = () => {
                    if (raf) { cancelAnimationFrame(raf); raf = null; }
                    this.mx = 0;
                    this.my = 0;
                    stage?.classList.remove('sc-login-stage-moving');
                };
                el.addEventListener('mousemove', onMove, { passive: true });
                el.addEventListener('mouseleave', onLeave, { passive: true });
            }
        }"
    >
        {{-- Background student and graduate scene --}}
        <x-auth-login-scene />

        {{-- Center stage with a soft, limited-tilt perspective responsive to cursor --}}
        <div
            class="sc-login-stage"
            :style="'transform: perspective(1600px) rotateX(' + (-my * 1.2).toFixed(3) + 'deg) rotateY(' + (mx * 1.2).toFixed(3) + 'deg);'"
        >
            <main class="sc-login-main w-full">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-filament-panels::layout.base>

<style>
    .sc-login-scene-wrap {
        min-height: 100vh;
        overflow-x: hidden;
        position: relative;
    }

    .sc-login-stage {
        position: relative;
        z-index: 10;
        display: flex;
        min-height: 100vh;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1rem;
        will-change: transform;
        transition: transform 500ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    /* While the cursor is moving the tilt tracks it 1:1 (no transition lag);
       the smooth transition only plays when the cursor leaves and the stage
       eases back to centre. */
    .sc-login-stage-moving {
        transition: none !important;
    }

    .sc-login-main {
        max-width: 64rem;
        width: 100%;
    }

    @media (max-width: 768px) {
        .sc-login-stage {
            padding: 1.5rem 1rem;
            transform: none !important; /* Disable parallax tilt on tablets/mobile for stability */
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .sc-login-stage {
            transform: none !important;
            transition: none !important;
        }
    }
</style>
