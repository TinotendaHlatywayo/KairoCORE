{{-- ============================================================
     SchoolCore Website Design System
     A self-contained, template-aware CSS layer for tenant public
     websites. Scoped under `.sc-site` so it never leaks into the
     Filament admin / CMS studio. Tokens are injected inline on the
     `.sc-site` element by the renderer (or the studio canvas).

     Usage: <div class="sc-site" data-sc-template="..." style="--sc-primary: #…; …">
     ============================================================ --}}
<style>
    /* ────────────────────────────────────────────────────────────
       1. TOKENS & RESET
       ──────────────────────────────────────────────────────────── */
    .sc-site {
        --sc-primary: #1e3a8a;
        --sc-secondary: #0284c7;
        --sc-accent: #f59e0b;
        --sc-bg: #ffffff;
        --sc-surface: #f8fafc;
        --sc-text: #1f2937;
        --sc-ink: #ffffff;
        --sc-border: rgba(15, 23, 42, 0.1);
        --sc-radius: 20px;
        --sc-radius-btn: 9999px;
        --sc-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
        --sc-shadow-lg: 0 20px 40px -15px rgba(15, 23, 42, 0.12), 0 8px 16px -4px rgba(15, 23, 42, 0.06);
        --sc-container: 80rem;
        --sc-font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
        --sc-font-display: 'Outfit', ui-sans-serif, system-ui, sans-serif;
        --sc-ease: cubic-bezier(0.22, 1, 0.36, 1);
        --sc-dur: 0.6s;

        font-family: var(--sc-font-sans);
        color: var(--sc-text);
        background-color: var(--sc-bg);
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
    }

    .sc-main { flex: 1 1 auto; }

    .sc-site *, .sc-site *::before, .sc-site *::after { box-sizing: border-box; }

    .sc-site h1, .sc-site h2, .sc-site h3, .sc-site h4, .sc-site h5, .sc-site h6 {
        font-family: var(--sc-font-display);
        line-height: 1.15;
        letter-spacing: -0.02em;
        color: var(--sc-text);
        margin: 0;
    }

    .sc-site p { margin: 0; }
    .sc-site img { max-width: 100%; display: block; }
    .sc-site a { color: var(--sc-primary); text-decoration: none; }
    .sc-site button { font-family: inherit; }
    .sc-site ul, .sc-site ol { margin: 0; padding: 0; }
    .sc-site svg { display: inline-block; vertical-align: middle; }

    /* Accessible focus ring (customizable per template via --sc-focus) */
    .sc-site :focus-visible {
        outline: 3px solid var(--sc-accent);
        outline-offset: 2px;
        border-radius: 4px;
    }

    /* ────────────────────────────────────────────────────────────
       2. LAYOUT PRIMITIVES
       ──────────────────────────────────────────────────────────── */
    .sc-container {
        width: min(var(--sc-container), 100%);
        margin-inline: auto;
        padding-inline: clamp(1.25rem, 4vw, 2.5rem);
    }
    .sc-container-boxed { --sc-container: 64rem; }
    .sc-container-wide { --sc-container: 80rem; }
    .sc-container-full { max-width: none; padding-inline: clamp(1rem, 3vw, 2rem); }

    .sc-section { position: relative; overflow: hidden; }
    .sc-section-bg { position: absolute; inset: 0; background-size: cover; background-position: center; z-index: 0; }
    .sc-section-content { position: relative; z-index: 1; }

    /* ── Per-block section photo controls (styles.image_* → CSS vars on .sc-section) ── */
    .sc-section .sc-media img,
    .sc-section .sc-hero-frame img,
    .sc-section .sc-feature-media img { object-fit: var(--sc-img-fit, cover); object-position: var(--sc-img-pos, center); }
    .sc-section .sc-media,
    .sc-section .sc-hero-frame,
    .sc-section .sc-feature-media { max-width: var(--sc-img-maxw, none); }
    .sc-section .sc-gallery-item img { object-fit: var(--sc-gallery-fit, cover); object-position: var(--sc-gallery-pos, center); }
    .sc-section .sc-tile-media { position: relative; display: block; overflow: hidden; margin-bottom: 0.9rem; border-radius: var(--sc-radius); background: color-mix(in srgb, var(--sc-text) 8%, transparent); aspect-ratio: var(--sc-card-ratio, 4 / 3); }
    .sc-section .sc-tile-media img { width: 100%; height: 100%; object-fit: var(--sc-card-fit, cover); object-position: var(--sc-card-pos, center); }

    .sc-section-head { margin-bottom: clamp(2rem, 4vw, 3.5rem); }
    .sc-section-head.is-center { text-align: center; margin-inline: auto; }
    .sc-section-head.is-center .sc-eyebrow { justify-content: center; }

    .sc-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--sc-secondary);
        margin-bottom: 0.9rem;
    }
    .sc-eyebrow::before {
        content: '';
        width: 2rem;
        height: 2px;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--sc-primary), var(--sc-secondary));
        flex-shrink: 0;
    }

    .sc-section-title { font-size: clamp(1.9rem, 1.5rem + 1.8vw, 2.75rem); font-weight: 800; }
    .sc-section-lead {
        margin-top: 1rem;
        max-width: 46rem;
        color: color-mix(in srgb, var(--sc-text) 72%, transparent);
        font-size: 1.05rem;
    }
    .sc-section-head.is-center .sc-section-lead { margin-inline: auto; }

    /* Grid helpers */
    .sc-grid { display: grid; gap: clamp(1.25rem, 2.5vw, 2rem); }
    .sc-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sc-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .sc-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .sc-grid-items-center { align-items: center; }

    /* ────────────────────────────────────────────────────────────
       3. BUTTONS
       ──────────────────────────────────────────────────────────── */
    .sc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.8rem 1.6rem;
        border: 0;
        border-radius: var(--sc-radius-btn);
        font-weight: 700;
        font-size: 0.95rem;
        line-height: 1.2;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        transition: transform 0.25s var(--sc-ease), box-shadow 0.3s var(--sc-ease), background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease;
    }
    .sc-btn:hover { transform: translateY(-2px); }
    .sc-btn:active { transform: translateY(0); }

    .sc-btn-primary {
        background: linear-gradient(135deg, var(--sc-primary), color-mix(in srgb, var(--sc-primary) 62%, var(--sc-secondary)));
        color: var(--sc-ink);
        box-shadow: 0 14px 30px -12px color-mix(in srgb, var(--sc-primary) 75%, transparent);
    }
    .sc-btn-primary:hover { box-shadow: 0 18px 40px -14px color-mix(in srgb, var(--sc-primary) 85%, transparent); }

    .sc-btn-accent {
        background: linear-gradient(135deg, color-mix(in srgb, var(--sc-accent) 58%, var(--sc-primary)), color-mix(in srgb, var(--sc-accent) 28%, var(--sc-primary)));
        color: #fff;
        box-shadow: 0 14px 30px -12px color-mix(in srgb, var(--sc-accent) 70%, transparent);
    }

    .sc-btn-ghost {
        background: transparent;
        color: var(--sc-primary);
        border: 1.5px solid color-mix(in srgb, var(--sc-primary) 45%, transparent);
    }
    .sc-btn-ghost:hover { background: color-mix(in srgb, var(--sc-primary) 8%, transparent); border-color: var(--sc-primary); }

    .sc-btn-surface {
        background: var(--sc-surface);
        color: var(--sc-text);
        border: 1px solid var(--sc-border);
    }
    .sc-btn-surface:hover { border-color: color-mix(in srgb, var(--sc-text) 30%, transparent); }

    .sc-btn-light {
        background: #fff;
        color: var(--sc-primary);
        box-shadow: 0 16px 40px -16px rgba(2, 6, 23, 0.45);
    }
    .sc-btn-lg { padding: 1rem 2rem; font-size: 1.05rem; }
    .sc-btn-sm { padding: 0.55rem 1.05rem; font-size: 0.82rem; }

    .sc-btn .sc-btn-arrow { transition: transform 0.25s var(--sc-ease); }
    .sc-btn:hover .sc-btn-arrow { transform: translateX(4px); }

    /* ────────────────────────────────────────────────────────────
       4. BADGES, CHIPS, TAGS
       ──────────────────────────────────────────────────────────── */
    .sc-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.32rem 0.8rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        background: color-mix(in srgb, var(--sc-secondary) 14%, transparent);
        color: var(--sc-secondary);
    }
    .sc-badge-solid {
        background: linear-gradient(135deg, var(--sc-secondary), var(--sc-primary));
        color: var(--sc-ink);
    }
    .sc-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.7rem;
        border-radius: var(--sc-radius-btn);
        font-size: 0.72rem;
        font-weight: 700;
        background: color-mix(in srgb, var(--sc-accent) 14%, transparent);
        color: color-mix(in srgb, var(--sc-accent) 80%, #000);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* ────────────────────────────────────────────────────────────
       5. CARDS
       ──────────────────────────────────────────────────────────── */
    .sc-card {
        position: relative;
        background: var(--sc-surface);
        border: 1px solid var(--sc-border);
        border-radius: var(--sc-radius);
        box-shadow: var(--sc-shadow);
        transition: transform 0.35s var(--sc-ease), box-shadow 0.35s var(--sc-ease), border-color 0.35s ease;
    }
    .sc-card-hover:hover {
        transform: translateY(-6px);
        box-shadow: var(--sc-shadow-lg);
        border-color: color-mix(in srgb, var(--sc-primary) 30%, transparent);
    }
    .sc-card-media { overflow: hidden; border-radius: calc(var(--sc-radius) - 2px); }
    .sc-card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s var(--sc-ease); }
    .sc-card-hover:hover .sc-card-media img { transform: scale(1.05); }

    /* Tile (icon + heading + body) */
    .sc-tile { position: relative; padding: clamp(1.5rem, 2.5vw, 2rem); display: flex; flex-direction: column; gap: 0.9rem; }
    .sc-tile-icon {
        width: 3.4rem; height: 3.4rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: calc(var(--sc-radius) * 0.6);
        background: linear-gradient(135deg, color-mix(in srgb, var(--sc-primary) 14%, transparent), color-mix(in srgb, var(--sc-secondary) 14%, transparent));
        color: var(--sc-primary);
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .sc-tile h3 { font-size: 1.2rem; font-weight: 700; }
    .sc-tile p { color: color-mix(in srgb, var(--sc-text) 72%, transparent); font-size: 0.94rem; }

    /* Numbered editorial tile */
    .sc-tile-numbered { position: relative; }
    .sc-tile-index {
        position: absolute;
        top: 1rem;
        right: 1.25rem;
        font-family: var(--sc-font-display);
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        color: color-mix(in srgb, var(--sc-primary) 45%, transparent);
    }

    /* ────────────────────────────────────────────────────────────
       6. FORMS
       ──────────────────────────────────────────────────────────── */
    .sc-field { display: block; }
    .sc-label {
        display: block;
        margin-bottom: 0.4rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: color-mix(in srgb, var(--sc-text) 85%, transparent);
    }
    .sc-required { color: #e11d48; }
    .sc-input, .sc-select, .sc-textarea {
        width: 100%;
        padding: 0.75rem 0.95rem;
        border: 1.5px solid color-mix(in srgb, var(--sc-text) 18%, transparent);
        border-radius: calc(var(--sc-radius) * 0.5);
        background: var(--sc-bg);
        color: var(--sc-text);
        font-size: 0.95rem;
        font-family: inherit;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }
    .sc-input:focus, .sc-select:focus, .sc-textarea:focus {
        outline: none;
        border-color: var(--sc-primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--sc-primary) 18%, transparent);
    }
    .sc-input::placeholder, .sc-textarea::placeholder { color: color-mix(in srgb, var(--sc-text) 45%, transparent); }
    .sc-input.is-error, .sc-select.is-error, .sc-textarea.is-error {
        border-color: #e11d48;
        background: color-mix(in srgb, #e11d48 6%, var(--sc-bg));
        color: #be123c;
    }
    .sc-input::file-selector-button {
        margin-right: 0.75rem;
        border: none;
        border-radius: calc(var(--sc-radius) * 0.4);
        background: color-mix(in srgb, var(--sc-primary) 14%, transparent);
        color: var(--sc-primary);
        padding: 0.45rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .sc-input::file-selector-button:hover { background: color-mix(in srgb, var(--sc-primary) 24%, transparent); }
    .sc-form-hint { margin-top: 0.35rem; font-size: 0.72rem; color: color-mix(in srgb, var(--sc-text) 55%, transparent); }
    .sc-form-hint.is-error { color: #be123c; font-weight: 700; }

    /* ────────────────────────────────────────────────────────────
       7. HEADER / NAVIGATION
       ──────────────────────────────────────────────────────────── */
    .sc-nav {
        position: sticky;
        top: 0;
        z-index: 60;
        background: color-mix(in srgb, var(--sc-surface) 92%, transparent);
        -webkit-backdrop-filter: blur(14px) saturate(160%);
        backdrop-filter: blur(14px) saturate(160%);
        border-bottom: 1px solid var(--sc-border);
        transition: box-shadow 0.3s ease, background-color 0.3s ease;
    }
    .sc-nav.is-scrolled { box-shadow: 0 10px 30px -18px rgba(15, 23, 42, 0.35); }
    .sc-nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: clamp(1.25rem, 2.5vw, 2.5rem);
        min-height: 4.75rem;
    }
    .sc-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: var(--sc-text);
        min-width: 0;
        max-width: 38%;
        flex-shrink: 0;
    }
    .sc-brand-logo {
        width: 2.75rem; height: 2.75rem;
        object-fit: contain;
        border-radius: 50%;
        box-shadow: 0 4px 12px -4px rgba(15, 23, 42, 0.25);
        flex-shrink: 0;
        background: #fff;
    }
    .sc-brand-name {
        font-family: var(--sc-font-display);
        font-weight: 800;
        font-size: clamp(0.95rem, 1.3vw, 1.25rem);
        letter-spacing: -0.02em;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sc-nav-menu {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        list-style: none;
        flex: 1 1 auto;
        min-width: 0;
    }
    .sc-nav-link {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.55rem 0.9rem;
        border-radius: var(--sc-radius-btn);
        color: color-mix(in srgb, var(--sc-text) 92%, transparent);
        font-weight: 600;
        font-size: 0.9rem;
        white-space: nowrap;
        text-decoration: none;
        transition: color 0.2s ease, background-color 0.2s ease;
    }
    .sc-nav-link:hover { color: var(--sc-primary); background-color: color-mix(in srgb, var(--sc-primary) 8%, transparent); }
    .sc-nav-link.is-active {
        color: var(--sc-primary);
        background-color: color-mix(in srgb, var(--sc-primary) 12%, transparent);
    }
    .sc-nav-link .sc-caret { transition: transform 0.25s var(--sc-ease); font-size: 0.65rem; }
    .sc-nav-item.has-children:hover .sc-caret { transform: rotate(180deg); }

    .sc-nav-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-left: auto;
        flex-shrink: 0;
    }
    .sc-nav-divider { width: 1px; height: 1.6rem; background: var(--sc-border); }
    .sc-nav-actions .sc-btn-ghost {
        background: color-mix(in srgb, var(--sc-surface) 90%, #fff);
        color: var(--sc-primary);
        border-color: color-mix(in srgb, var(--sc-primary) 50%, transparent);
    }

    .sc-hamburger {
        display: none;
        width: 2.75rem; height: 2.75rem;
        align-items: center; justify-content: center;
        border: 1px solid var(--sc-border);
        border-radius: var(--sc-radius-btn);
        background: transparent;
        color: var(--sc-text);
        cursor: pointer;
    }

    /* Dropdown (desktop) */
    .sc-nav-dropdown {
        position: absolute;
        top: calc(100% + 0.5rem);
        left: 0;
        min-width: 14rem;
        padding: 0.5rem;
        background: var(--sc-surface);
        border: 1px solid var(--sc-border);
        border-radius: calc(var(--sc-radius) * 0.6);
        box-shadow: var(--sc-shadow-lg);
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px);
        transition: opacity 0.25s ease, transform 0.25s var(--sc-ease), visibility 0.25s;
        z-index: 70;
    }
    .sc-nav-item.has-children { position: relative; }
    .sc-nav-item.has-children:hover .sc-nav-dropdown,
    .sc-nav-item.has-children:focus-within .sc-nav-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .sc-nav-dropdown-link {
        display: block;
        padding: 0.6rem 0.85rem;
        border-radius: calc(var(--sc-radius) * 0.4);
        color: var(--sc-text);
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease, padding-left 0.2s ease;
    }
    .sc-nav-dropdown-link:hover { background-color: color-mix(in srgb, var(--sc-primary) 10%, transparent); color: var(--sc-primary); padding-left: 1.1rem; }

    /* Mobile panel */
    .sc-mobile-panel {
        display: none;
        border-top: 1px solid var(--sc-border);
        background: var(--sc-surface);
        max-height: calc(100dvh - 4.5rem);
        overflow-y: auto;
    }
    .sc-mobile-panel.is-open { display: block; }
    .sc-mobile-links { list-style: none; padding: 0.75rem 0; display: flex; flex-direction: column; gap: 0.15rem; }
    .sc-mobile-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.8rem 1rem;
        border-radius: calc(var(--sc-radius) * 0.5);
        color: var(--sc-text);
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .sc-mobile-link:hover, .sc-mobile-link.is-active { background-color: color-mix(in srgb, var(--sc-primary) 10%, transparent); color: var(--sc-primary); }
    .sc-mobile-actions { display: flex; flex-direction: column; gap: 0.6rem; padding: 0.75rem 1rem 1.25rem; }
    .sc-mobile-panel .sc-btn { width: 100%; }

    /* Announcement banner */
    .sc-announce {
        background: linear-gradient(90deg, var(--sc-primary), var(--sc-secondary));
        color: var(--sc-ink);
        text-align: center;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.55rem 1rem;
    }
    .sc-announce a { color: inherit; text-decoration: underline; text-underline-offset: 2px; }

    /* Alpine transition helpers (used by nav panel + wizard modal) */
    .sc-transition { transition-property: opacity, transform; transition-duration: 0.28s; transition-timing-function: var(--sc-ease); }
    .sc-transition-start { opacity: 0; transform: translateY(-8px); }
    .sc-transition-end { opacity: 1; transform: translateY(0); }
    .sc-transition-enter, .sc-transition-leave { }

    /* ────────────────────────────────────────────────────────────
       8. HERO
       ──────────────────────────────────────────────────────────── */
    .sc-hero { position: relative; overflow: hidden; }
    .sc-hero-grid {
        display: grid;
        grid-template-columns: 1.05fr 0.95fr;
        gap: clamp(2rem, 5vw, 4.5rem);
        align-items: center;
        padding-block: clamp(3.5rem, 8vw, 7rem);
    }
    .sc-hero-copy { display: flex; flex-direction: column; gap: 1.4rem; align-items: flex-start; }
    .sc-hero-title {
        font-size: clamp(2.4rem, 1.6rem + 4.4vw, 4.25rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.04;
    }
    .sc-hero-lead {
        font-size: clamp(1.05rem, 1rem + 0.4vw, 1.25rem);
        color: color-mix(in srgb, var(--sc-text) 74%, transparent);
        max-width: 34rem;
    }
    .sc-hero-actions { display: flex; flex-wrap: wrap; gap: 0.9rem; margin-top: 0.4rem; }

    .sc-hero-media { position: relative; }
    .sc-hero-frame {
        position: relative;
        border-radius: calc(var(--sc-radius) * 1.4);
        overflow: hidden;
        box-shadow: var(--sc-shadow-lg);
        background: color-mix(in srgb, var(--sc-text) 8%, transparent);
    }
    .sc-hero-frame img { width: 100%; height: 100%; object-fit: cover; }
    .sc-hero-frame-ring {
        position: absolute;
        inset: -1rem;
        border: 1.5px dashed color-mix(in srgb, var(--sc-secondary) 45%, transparent);
        border-radius: calc(var(--sc-radius) * 1.8);
        z-index: -1;
    }
    .sc-hero-float {
        position: absolute;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.7rem 1rem;
        background: var(--sc-surface);
        border: 1px solid var(--sc-border);
        border-radius: var(--sc-radius);
        box-shadow: var(--sc-shadow-lg);
        font-size: 0.82rem;
        font-weight: 700;
    }
    .sc-hero-float-1 { bottom: 1.5rem; left: -1rem; }
    .sc-hero-float-2 { top: 1.5rem; right: -0.5rem; }
    .sc-hero-float .sc-dot {
        width: 0.6rem; height: 0.6rem; border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px color-mix(in srgb, #10b981 22%, transparent);
    }

    /* Hero background flourishes */
    .sc-hero-backdrop { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
    .sc-hero-backdrop .sc-orb {
        position: absolute; border-radius: 50%;
        filter: blur(70px); opacity: 0.5;
    }
    .sc-hero-content { position: relative; z-index: 1; }

    /* ────────────────────────────────────────────────────────────
       9. STATS
       ──────────────────────────────────────────────────────────── */
    .sc-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: clamp(1rem, 2vw, 1.5rem); }
    .sc-stat {
        padding: clamp(1.4rem, 2.5vw, 2rem);
        border-radius: var(--sc-radius);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.55rem;
    }
    .sc-stat-num {
        font-family: var(--sc-font-display);
        font-size: clamp(2rem, 1.4rem + 2.4vw, 3.25rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1;
        color: var(--sc-primary);
    }
    .sc-stat-label {
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: color-mix(in srgb, var(--sc-text) 62%, transparent);
    }
    .sc-stat-icon { font-size: 1.3rem; }

    /* ────────────────────────────────────────────────────────────
       10. ABOUT / PRINCIPAL / IMAGE FRAMES
       ──────────────────────────────────────────────────────────── */
    .sc-split { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: clamp(2rem, 5vw, 4rem); align-items: center; }
    .sc-media {
        position: relative;
        border-radius: var(--sc-radius);
        overflow: hidden;
        background: color-mix(in srgb, var(--sc-text) 8%, transparent);
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-media img { width: 100%; height: 100%; object-fit: cover; }
    .sc-media-arch { border-radius: calc(var(--sc-radius) * 2) calc(var(--sc-radius) * 2) var(--sc-radius) var(--sc-radius); }

    .sc-fact { padding: 1.1rem 1.25rem; }
    .sc-fact h4 {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--sc-secondary);
        margin-bottom: 0.35rem;
    }
    .sc-fact p { font-size: 0.85rem; color: color-mix(in srgb, var(--sc-text) 70%, transparent); }

    .sc-avatar {
        border-radius: 50%;
        object-fit: cover;
        background: color-mix(in srgb, var(--sc-text) 10%, transparent);
        box-shadow: 0 0 0 4px var(--sc-surface), 0 0 0 5px var(--sc-border);
    }

    /* ────────────────────────────────────────────────────────────
       11. NEWS / EVENTS
       ──────────────────────────────────────────────────────────── */
    .sc-news-card { padding: clamp(1.3rem, 2.2vw, 1.8rem); display: flex; flex-direction: column; gap: 0.7rem; }
    .sc-news-card h3 { font-size: 1.1rem; font-weight: 700; line-height: 1.3; }
    .sc-news-card p { font-size: 0.85rem; color: color-mix(in srgb, var(--sc-text) 60%, transparent); }
    .sc-date {
        font-size: 0.74rem;
        font-weight: 700;
        color: color-mix(in srgb, var(--sc-text) 55%, transparent);
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .sc-event { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; }
    .sc-date-chip {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 3.4rem;
        padding: 0.5rem 0;
        border-radius: calc(var(--sc-radius) * 0.6);
        background: linear-gradient(160deg, var(--sc-primary), var(--sc-secondary));
        color: var(--sc-ink);
        line-height: 1.1;
    }
    .sc-date-chip-day { font-size: 1.15rem; font-weight: 800; font-family: var(--sc-font-display); }
    .sc-date-chip-mon { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    .sc-event-body h4 { font-size: 0.98rem; font-weight: 700; }
    .sc-event-body p { font-size: 0.8rem; color: color-mix(in srgb, var(--sc-text) 58%, transparent); margin-top: 0.15rem; }

    /* ────────────────────────────────────────────────────────────
       12. GALLERY
       ──────────────────────────────────────────────────────────── */
    .sc-gallery { display: grid; gap: clamp(0.9rem, 2vw, 1.25rem); }
    .sc-gallery-item {
        position: relative;
        border-radius: var(--sc-radius);
        overflow: hidden;
        aspect-ratio: 1;
        background: color-mix(in srgb, var(--sc-text) 8%, transparent);
        cursor: pointer;
    }
    .sc-gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s var(--sc-ease); }
    .sc-gallery-item:hover img { transform: scale(1.06); }
    .sc-gallery-caption {
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        padding: 1.5rem 0.9rem 0.7rem;
        background: linear-gradient(to top, rgba(2, 6, 23, 0.82), transparent);
        color: #fff;
        font-size: 0.76rem;
        font-weight: 700;
        text-align: center;
        transform: translateY(100%);
        transition: transform 0.35s var(--sc-ease);
    }
    .sc-gallery-item:hover .sc-gallery-caption { transform: translateY(0); }

    /* ────────────────────────────────────────────────────────────
       13. TESTIMONIALS
       ──────────────────────────────────────────────────────────── */
    .sc-quote {
        padding: clamp(1.5rem, 2.5vw, 2rem);
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }
    .sc-quote-mark {
        font-family: var(--sc-font-display);
        font-size: 3.2rem;
        line-height: 0.7;
        color: color-mix(in srgb, var(--sc-secondary) 45%, transparent);
    }
    .sc-quote-body { font-style: italic; font-size: 0.98rem; color: color-mix(in srgb, var(--sc-text) 82%, transparent); }
    .sc-attribution { display: flex; align-items: center; gap: 0.8rem; }
    .sc-attribution-name { display: block; font-weight: 800; font-size: 0.88rem; }
    .sc-attribution-role { display: block; font-size: 0.76rem; color: color-mix(in srgb, var(--sc-text) 55%, transparent); font-weight: 600; }

    /* ────────────────────────────────────────────────────────────
       14. TEAM
       ──────────────────────────────────────────────────────────── */
    .sc-team-card { padding: clamp(1.3rem, 2vw, 1.7rem); text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.65rem; }
    .sc-team-avatar { width: 5rem; height: 5rem; border-radius: 50%; object-fit: cover; }
    .sc-team-card h4 { font-size: 0.98rem; font-weight: 700; }
    .sc-team-card p { font-size: 0.78rem; color: color-mix(in srgb, var(--sc-text) 55%, transparent); font-weight: 600; }

    /* ────────────────────────────────────────────────────────────
       15. FAQ
       ──────────────────────────────────────────────────────────── */
    .sc-faq { max-width: 46rem; margin-inline: auto; display: flex; flex-direction: column; gap: 0.9rem; }
    .sc-faq-item {
        border: 1px solid var(--sc-border);
        border-radius: var(--sc-radius);
        background: var(--sc-surface);
        overflow: hidden;
        transition: box-shadow 0.3s ease, border-color 0.3s ease;
    }
    .sc-faq-item.is-open { border-color: color-mix(in srgb, var(--sc-primary) 35%, transparent); box-shadow: var(--sc-shadow); }
    .sc-faq-q {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.05rem 1.25rem;
        border: 0;
        background: transparent;
        color: var(--sc-text);
        font-size: 0.98rem;
        font-weight: 700;
        text-align: left;
        cursor: pointer;
        font-family: inherit;
    }
    .sc-faq-icon {
        flex-shrink: 0;
        width: 1.6rem; height: 1.6rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%;
        background: color-mix(in srgb, var(--sc-secondary) 14%, transparent);
        color: var(--sc-secondary);
        font-size: 1rem;
        transition: transform 0.3s var(--sc-ease), background-color 0.3s ease;
    }
    .sc-faq-item.is-open .sc-faq-icon { transform: rotate(45deg); background: var(--sc-primary); color: var(--sc-ink); }
    .sc-faq-a {
        padding: 0 1.25rem 1.15rem;
        color: color-mix(in srgb, var(--sc-text) 72%, transparent);
        font-size: 0.92rem;
        line-height: 1.65;
    }

    /* ────────────────────────────────────────────────────────────
       16. CONTACT
       ──────────────────────────────────────────────────────────── */
    .sc-contact-list { display: flex; flex-direction: column; gap: 1rem; }
    .sc-contact-row { display: flex; align-items: flex-start; gap: 0.9rem; font-size: 0.94rem; }
    .sc-contact-icon {
        flex-shrink: 0;
        width: 2.4rem; height: 2.4rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: calc(var(--sc-radius) * 0.5);
        background: color-mix(in srgb, var(--sc-primary) 12%, transparent);
        color: var(--sc-primary);
        font-size: 1.05rem;
    }
    .sc-contact-row strong { font-weight: 700; color: var(--sc-text); }
    .sc-contact-row span { display: block; color: color-mix(in srgb, var(--sc-text) 72%, transparent); }
    .sc-contact-note { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: color-mix(in srgb, var(--sc-text) 45%, transparent); }
    .sc-map {
        width: 100%;
        border: 1px solid var(--sc-border);
        border-radius: var(--sc-radius);
        box-shadow: var(--sc-shadow);
        overflow: hidden;
        background: color-mix(in srgb, var(--sc-text) 6%, transparent);
    }
    .sc-map iframe { display: block; width: 100%; height: 100%; border: 0; }
    .sc-alert { padding: 0.9rem 1.1rem; border-radius: var(--sc-radius); font-size: 0.9rem; font-weight: 600; }
    .sc-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .sc-alert-error { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; }

    /* ────────────────────────────────────────────────────────────
       16.5 CYLINDER CAROUSEL
       ──────────────────────────────────────────────────────────── */
    .sc-cylinder-arrow {
        flex-shrink: 0;
        width: 2.75rem; height: 2.75rem;
        display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid var(--sc-border);
        border-radius: 50%;
        background: var(--sc-surface);
        color: var(--sc-text);
        font-size: 1.1rem;
        cursor: pointer;
        box-shadow: var(--sc-shadow);
        transition: transform 0.25s var(--sc-ease), background-color 0.25s ease, color 0.25s ease;
    }
    .sc-cylinder-arrow:hover { transform: translateY(-2px); background: var(--sc-primary); color: var(--sc-ink); border-color: var(--sc-primary); }
    .sc-cylinder-card { opacity: 0.45; transform-style: preserve-3d; transition: opacity 0.4s ease, filter 0.4s ease; }
    .sc-cylinder-card--active { opacity: 1; }
    .sc-cylinder-icon svg { display: inline-block; }

    /* ────────────────────────────────────────────────────────────
       17. CTA BAND
       ──────────────────────────────────────────────────────────── */
    .sc-cta-band {
        position: relative;
        overflow: hidden;
        border-radius: calc(var(--sc-radius) * 1.4);
        padding: clamp(2.5rem, 6vw, 4.5rem);
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-secondary));
        color: var(--sc-ink);
        text-align: center;
    }
    .sc-cta-band h2 { color: var(--sc-ink); font-size: clamp(1.7rem, 1.3rem + 1.6vw, 2.5rem); font-weight: 800; }
    .sc-cta-band p { color: color-mix(in srgb, var(--sc-ink) 80%, transparent); margin-top: 0.8rem; max-width: 40rem; margin-inline: auto; }
    .sc-cta-band .sc-btn { margin-top: 1.6rem; }
    .sc-cta-orb { position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.12); pointer-events: none; }
    .sc-cta-orb-1 { width: 14rem; height: 14rem; top: -5rem; right: -4rem; }
    .sc-cta-orb-2 { width: 10rem; height: 10rem; bottom: -4rem; left: -3rem; }

    /* ────────────────────────────────────────────────────────────
       18. VIDEO / LOGO CLOUD / DIVIDER
       ──────────────────────────────────────────────────────────── */
    .sc-video { position: relative; border-radius: var(--sc-radius); overflow: hidden; box-shadow: var(--sc-shadow-lg); background: #0b1220; }
    .sc-video iframe { display: block; width: 100%; height: 100%; border: 0; }
    .sc-video-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.85rem; font-weight: 700; }

    .sc-logos { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; }
    .sc-logo-chip {
        padding: 0.7rem 1.3rem;
        border-radius: var(--sc-radius-btn);
        border: 1px solid var(--sc-border);
        background: var(--sc-surface);
        font-size: 0.85rem;
        font-weight: 700;
        color: color-mix(in srgb, var(--sc-text) 75%, transparent);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    }

    .sc-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--sc-border) 20%, var(--sc-border) 80%, transparent);
        border: 0;
        margin: 0;
    }

    /* ────────────────────────────────────────────────────────────
       19. FOOTER
       ──────────────────────────────────────────────────────────── */
    .sc-footer {
        margin-top: auto;
        background: color-mix(in srgb, var(--sc-primary) 6%, var(--sc-bg));
        border-top: 1px solid var(--sc-border);
        color: color-mix(in srgb, var(--sc-text) 75%, transparent);
    }
    .sc-footer-grid {
        display: grid;
        grid-template-columns: 1.6fr 1fr 1fr 1.2fr;
        gap: clamp(1.5rem, 4vw, 3rem);
        padding-block: clamp(2.5rem, 5vw, 4rem);
    }
    .sc-footer-brand { display: inline-flex; align-items: center; gap: 0.7rem; font-family: var(--sc-font-display); font-weight: 800; color: var(--sc-text); font-size: 1.1rem; }
    .sc-footer-about { margin-top: 0.9rem; font-size: 0.88rem; color: color-mix(in srgb, var(--sc-text) 62%, transparent); max-width: 22rem; }
    .sc-footer h4 { font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--sc-text); margin-bottom: 1rem; }
    .sc-footer-links { list-style: none; display: flex; flex-direction: column; gap: 0.55rem; }
    .sc-footer-links a {
        color: color-mix(in srgb, var(--sc-text) 68%, transparent);
        font-size: 0.9rem;
        text-decoration: none;
        transition: color 0.15s ease, padding-left 0.2s ease;
    }
    .sc-footer-links a:hover { color: var(--sc-primary); padding-left: 4px; }
    .sc-social { display: flex; gap: 0.6rem; margin-top: 1.1rem; }
    .sc-social a {
        width: 2.3rem; height: 2.3rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%;
        border: 1px solid var(--sc-border);
        background: var(--sc-surface);
        color: var(--sc-text);
        transition: transform 0.25s var(--sc-ease), background-color 0.25s ease, color 0.25s ease;
    }
    .sc-social a:hover { transform: translateY(-3px); background: var(--sc-primary); color: var(--sc-ink); border-color: var(--sc-primary); }
    .sc-footer-bottom {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        padding-block: 1.2rem;
        border-top: 1px solid var(--sc-border);
        font-size: 0.8rem;
        color: color-mix(in srgb, var(--sc-text) 55%, transparent);
    }

    /* ────────────────────────────────────────────────────────────
       20. MOTION & REVEAL
       ──────────────────────────────────────────────────────────── */
    .sc-site [data-sc-reveal] {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity 0.7s var(--sc-ease), transform 0.7s var(--sc-ease);
        transition-delay: var(--sc-delay, 0s);
        will-change: opacity, transform;
    }
    .sc-site [data-sc-reveal="left"] { transform: translateX(-28px); }
    .sc-site [data-sc-reveal="right"] { transform: translateX(28px); }
    .sc-site [data-sc-reveal="zoom"] { transform: scale(0.92); }
    .sc-site [data-sc-reveal="fade"] { transform: none; }
    .sc-site [data-sc-reveal="bounce-subtle"] { transform: scale(0.96) translateY(18px); }
    .sc-site [data-sc-reveal].is-in { opacity: 1; transform: none; }

    .sc-parallax { will-change: transform; }

    .sc-tilt { transition: transform 0.25s var(--sc-ease); transform-style: preserve-3d; }

    /* ────────────────────────────────────────────────────────────
       21. DEPTH TEXT (adapted from React Bits DepthText)
       ──────────────────────────────────────────────────────────── */
    .sc-depth {
        position: relative;
        display: inline-block;
        perspective: 700px;
        transform-style: preserve-3d;
    }
    .sc-depth-face { position: relative; z-index: 2; display: block; transform: translateZ(30px); }
    .sc-depth-layer {
        position: absolute;
        inset: 0;
        z-index: 1;
        display: block;
        color: transparent;
        -webkit-text-stroke: 0;
        background: transparent;
        pointer-events: none;
        transform: translateZ(0);
    }
    .sc-depth.is-tilting { transform-style: preserve-3d; }

    /* ────────────────────────────────────────────────────────────
       22. ADMISSIONS WIZARD / MODAL
       ──────────────────────────────────────────────────────────── */
    .sc-wizard-steps { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.75rem; }
    .sc-wizard-step { display: flex; align-items: center; gap: 0.55rem; font-size: 0.76rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: color-mix(in srgb, var(--sc-text) 45%, transparent); }
    .sc-wizard-dot {
        width: 1.7rem; height: 1.7rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%;
        background: color-mix(in srgb, var(--sc-text) 12%, transparent);
        color: color-mix(in srgb, var(--sc-text) 55%, transparent);
        font-size: 0.78rem;
        font-weight: 800;
        transition: all 0.3s var(--sc-ease);
    }
    .sc-wizard-step.is-active .sc-wizard-dot { background: var(--sc-primary); color: var(--sc-ink); }
    .sc-wizard-step.is-done .sc-wizard-dot { background: color-mix(in srgb, var(--sc-primary) 16%, transparent); color: var(--sc-primary); }
    .sc-wizard-line { flex: 1; height: 1px; background: color-mix(in srgb, var(--sc-text) 14%, transparent); }
    .sc-wizard-line.is-done { background: color-mix(in srgb, var(--sc-primary) 45%, transparent); }

    .sc-step-heading { font-size: 0.86rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: color-mix(in srgb, var(--sc-text) 55%, transparent); margin-bottom: 1.1rem; }

    .sc-upload-row { border: 1px dashed color-mix(in srgb, var(--sc-primary) 35%, transparent); background: color-mix(in srgb, var(--sc-primary) 4%, transparent); }
    .sc-file-btn { font-weight: 800; font-size: 0.8rem; color: var(--sc-primary); }

    .sc-modal { position: fixed; inset: 0; z-index: 90; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .sc-modal-backdrop { position: absolute; inset: 0; background: rgba(2, 6, 23, 0.6); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
    .sc-modal-card { position: relative; width: min(100%, 26rem); background: var(--sc-bg); border-radius: calc(var(--sc-radius) * 1.2); box-shadow: var(--sc-shadow-lg); padding: clamp(1.5rem, 4vw, 2.25rem); text-align: center; }
    .sc-modal-check {
        width: 4.5rem; height: 4.5rem;
        margin: 0 auto 1.2rem;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        background: #d1fae5;
        border: 2px solid #6ee7b7;
        color: #059669;
    }
    .sc-ref-box { background: color-mix(in srgb, var(--sc-text) 5%, transparent); border: 1px solid var(--sc-border); border-radius: var(--sc-radius); padding: 1rem; margin-top: 1.2rem; }

    /* ────────────────────────────────────────────────────────────
       23. SKIP LINK & UTILITIES
       ──────────────────────────────────────────────────────────── */
    .sc-skip {
        position: absolute;
        left: 1rem;
        top: -100px;
        z-index: 100;
        background: var(--sc-primary);
        color: var(--sc-ink);
        padding: 0.7rem 1.2rem;
        border-radius: var(--sc-radius-btn);
        font-weight: 700;
        font-size: 0.9rem;
        transition: top 0.2s ease;
    }
    .sc-skip:focus { top: 1rem; }
    .sc-muted { color: color-mix(in srgb, var(--sc-text) 60%, transparent); }
    .sc-text-sm { font-size: 0.85rem; }
    .sc-text-center { text-align: center; }
    .sc-text-left { text-align: left; }
    .sc-text-right { text-align: right; }

    .sc-site [x-cloak] { display: none !important; }

    /* ────────────────────────────────────────────────────────────
       24. INTERACTIVE REFERENCE COMPONENTS
       Coverflow, orbit, marquee, kinetic heading, scroll highlight.
       Shared behavior layer; templates differentiate via identity
       overrides (section 26).
       ──────────────────────────────────────────────────────────── */

    /* 24a. Coverflow Carousel */
    .sc-coverflow-stage { position: relative; padding-block: clamp(3rem, 7vw, 5.5rem); }
    .sc-coverflow-wrap { position: relative; margin-top: clamp(2rem, 4vw, 3rem); }
    .sc-coverflow {
        position: relative;
        perspective: 1600px;
        min-height: clamp(220px, 36vw, 380px);
        outline: none;
        user-select: none;
    }
    .sc-coverflow-slides { position: relative; height: 100%; transform-style: preserve-3d; }
    .sc-coverflow-card {
        position: absolute;
        top: 50%;
        left: 50%;
        overflow: hidden;
        transform-style: preserve-3d;
        transform-origin: center center;
        background: #15181f;
        box-shadow: 0 30px 60px -24px rgba(2, 6, 23, 0.55);
    }
    .sc-coverflow-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; user-select: none; }
    .sc-coverflow-shade {
        position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(180deg, rgba(0,0,0,0) 35%, rgba(0,0,0,0.72) 100%);
    }
    .sc-coverflow-shade.is-top { background: linear-gradient(0deg, rgba(0,0,0,0) 35%, rgba(0,0,0,0.72) 100%); }
    .sc-coverflow-title {
        position: absolute; pointer-events: none;
    }
    .sc-coverflow-title > span {
        color: #fff;
        font-family: var(--sc-font-display);
        font-size: clamp(1.05rem, 2vw, 1.45rem);
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -0.02em;
        white-space: pre-line;
        text-shadow: 0 2px 12px rgba(0,0,0,0.5);
    }
    .sc-coverflow-title.is-right { text-align: right; }
    .sc-coverflow-dim { position: absolute; inset: 0; pointer-events: none; }
    .sc-coverflow-controls {
        display: flex; align-items: center; justify-content: center; gap: 1rem;
        margin-top: clamp(1.5rem, 3vw, 2.5rem);
    }
    .sc-coverflow-counter { font-variant-numeric: tabular-nums; font-weight: 700; color: color-mix(in srgb, var(--sc-text) 60%, transparent); min-width: 4.5rem; text-align: center; }

    /* 24b. Orbit Gallery */
    .sc-orbit-section { padding-block: clamp(3rem, 7vw, 5.5rem); }
    .sc-orbit-stage {
        position: relative;
        margin: 0 auto;
        perspective: 900px;
    }
    .sc-orbit-center {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: clamp(84px, 12vw, 120px); height: clamp(84px, 12vw, 120px);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: var(--sc-primary); color: var(--sc-ink);
        font-weight: 800; font-size: 0.85rem; text-align: center;
        z-index: 2; box-shadow: var(--sc-shadow);
    }
    .sc-orbit-node { position: absolute; top: 50%; left: 50%; margin: 0; z-index: 1; will-change: transform; }
    .sc-orbit-node img {
        border-radius: 50%; object-fit: cover;
        border: 3px solid var(--sc-bg);
        box-shadow: 0 10px 24px -10px rgba(2, 6, 23, 0.4);
    }
    .sc-orbit-node .sc-orbit-label {
        display: block; text-align: center; margin-top: 0.4rem;
        font-size: 0.78rem; font-weight: 700;
        color: color-mix(in srgb, var(--sc-text) 70%, transparent);
        max-width: 8.5rem; margin-inline: auto;
    }

    /* 24c. Marquee Ticker (two rows, opposite directions, edge fade) */
    .sc-marquee { position: relative; overflow: hidden; border-block: 1px solid var(--sc-border); padding-block: 1.15rem; }
    .sc-marquee-section { overflow: hidden; }
    .sc-marquee::before, .sc-marquee::after {
        content: '';
        position: absolute; inset: 0 0; width: var(--sc-fade, 18%); z-index: 2; pointer-events: none;
    }
    .sc-marquee::before { left: 0; background: linear-gradient(90deg, var(--sc-bg) 0%, transparent 100%); }
    .sc-marquee::after { right: 0; background: linear-gradient(-90deg, var(--sc-bg) 0%, transparent 100%); }
    .sc-marquee-track { display: flex; gap: clamp(1rem, 3vw, 2.5rem); align-items: center; white-space: nowrap; width: max-content; animation: scMarquee var(--sc-marquee-speed, 28s) linear infinite; }
    .sc-marquee-track.is-reverse { animation-direction: reverse; }
    .sc-marquee-item { display: inline-flex; align-items: center; gap: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; }
    .sc-marquee-item .sc-marquee-dot { color: var(--sc-accent); font-size: 1.1rem; }
    @keyframes scMarquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

    /* 24d. Kinetic Reveal Heading (smoke/rise word entrance) */
    .sc-kinetic-heading { position: relative; padding-block: clamp(2rem, 5vw, 3.5rem); }
    .sc-kinetic-heading .sc-kinetic-text { display: inline-block; }
    .sc-kinetic-word {
        display: inline-block;
        opacity: 0;
        transform: translateY(calc(0.7em * var(--sc-smoke, 1))) scale(1.04);
        filter: blur(calc(0.35em * var(--sc-smoke, 1)));
        transition: opacity 0.85s var(--sc-ease), transform 0.85s var(--sc-ease), filter 0.85s var(--sc-ease);
        transition-delay: var(--sc-delay, 0s);
        will-change: opacity, transform, filter;
    }
    .sc-kinetic-heading[data-variant="rise"] .sc-kinetic-word {
        filter: blur(calc(0.12em * var(--sc-smoke, 1)));
        transform: translateY(calc(1.1em * var(--sc-smoke, 1))) scale(1.03);
    }
    .sc-kinetic-word.is-in { opacity: 1; transform: none; filter: blur(0); }
    .sc-kinetic-heading[data-trigger="hover"]:hover .sc-kinetic-word { opacity: 1; transform: none; filter: blur(0); }

    /* 24e. Scroll Highlight Text (progressive word/char lighting) */
    .sc-scroll-highlight { padding-block: clamp(3rem, 7vw, 5rem); }
    .sc-scroll-highlight .sc-sh-body {
        font-family: var(--sc-font-display);
        font-size: clamp(1.35rem, 1rem + 1.6vw, 2.1rem);
        line-height: 1.6;
        font-weight: 500;
        color: var(--sc-sh-dim, color-mix(in srgb, var(--sc-text) 32%, transparent));
    }
    .sc-scroll-highlight .sc-sh-word { transition: color 0.5s ease; }
    .sc-scroll-highlight .sc-sh-word.is-lit { color: var(--sc-sh-lit, var(--sc-text)); }
    .sc-scroll-highlight.is-accent .sc-sh-word.is-lit { color: var(--sc-accent); }

    /* 24f. Multi-variant plain components */
    .sc-stats.sc-stats-large .sc-stat { text-align: center; align-items: center; }
    .sc-stats.sc-stats-large .sc-stat-num { font-size: clamp(2.75rem, 5vw, 4.5rem); line-height: 1; }
    .sc-stats.sc-stats-large .sc-stat-icon { display: none; }
    .sc-stats-editorial { border-top: 1px solid var(--sc-border); }
    .sc-stat-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: baseline;
        gap: clamp(1rem, 3vw, 2rem);
        padding-block: 1.2rem;
        border-bottom: 1px solid var(--sc-border);
    }
    .sc-stat-idx {
        font-size: 0.8rem; font-weight: 800; letter-spacing: 0.14em;
        color: color-mix(in srgb, var(--sc-text) 42%, transparent);
        font-variant-numeric: tabular-nums;
    }
    .sc-stat-row .sc-stat-label { font-weight: 700; }
    .sc-stat-row .sc-stat-num { font-family: var(--sc-font-display); font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 800; color: var(--sc-primary); }
    .sc-stat-cinematic {
        -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
        border: 1px solid color-mix(in srgb, var(--sc-primary) 26%, transparent);
    }
    .sc-stat-cinematic .sc-stat-num {
        background: linear-gradient(110deg, var(--sc-primary), var(--sc-secondary) 55%, var(--sc-accent));
        -webkit-background-clip: text; background-clip: text; color: transparent;
    }
    .sc-stats-marquee .sc-marquee { border-block: 0; padding-block: 0.4rem; }
    .sc-stat-marquee-item { display: inline-flex; align-items: baseline; gap: 1rem; }
    .sc-stat-marquee-item .sc-stat-num { font-family: var(--sc-font-display); font-size: clamp(1.7rem, 3vw, 2.5rem); font-weight: 800; color: var(--sc-primary); }

    .sc-quote-feature { max-width: 46rem; margin-inline: auto; text-align: center; }
    .sc-quote-mark-lg { font-family: Georgia, serif; font-size: clamp(3.5rem, 8vw, 6rem); line-height: 0.5; display: block; color: var(--sc-accent); }
    .sc-quote-body-lg {
        font-family: var(--sc-font-display);
        font-size: clamp(1.4rem, 1.1rem + 1.4vw, 2rem);
        font-weight: 600; line-height: 1.45; margin-block: 1.5rem;
    }
    .sc-quote-split {
        display: grid; grid-template-columns: 1.5fr 1fr;
        gap: clamp(1.5rem, 4vw, 3rem); align-items: start;
        padding-block: 1.9rem; border-bottom: 1px solid var(--sc-border);
    }
    .sc-quote-split:first-child { padding-top: 0; }
    .sc-quote-split .sc-quote-body {
        font-family: var(--sc-font-display); font-style: normal;
        font-size: clamp(1.15rem, 1rem + 0.8vw, 1.5rem);
        font-weight: 600; line-height: 1.5; margin: 0;
    }
    .sc-quote-split .sc-attribution { padding-top: 0.3rem; }
    .sc-quote-carousel { position: relative; max-width: 52rem; margin-inline: auto; text-align: center; }
    .sc-quote-carousel .sc-quote-slide { min-height: 13rem; }
    .sc-quote-carousel .sc-quote-controls { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1.75rem; }
    .sc-quote-dot { width: 8px; height: 8px; border-radius: 50%; padding: 0; border: 0; background: color-mix(in srgb, var(--sc-text) 22%, transparent); cursor: pointer; transition: background 0.3s var(--sc-ease); }
    .sc-quote-dot.is-active { background: var(--sc-primary); }
    .sc-quote-img { width: 3.25rem; height: 3.25rem; border-radius: 50%; object-fit: cover; }

    .sc-gallery-hscroll {
        display: flex; gap: 1rem; overflow-x: auto;
        padding: 0.25rem 0.25rem 0.75rem;
        scroll-snap-type: x mandatory;
    }
    .sc-gallery-hscroll .sc-gallery-item {
        flex: 0 0 clamp(260px, 42vw, 420px);
        height: clamp(220px, 34vw, 320px);
        margin: 0; scroll-snap-align: center;
    }
    .sc-gallery-hscroll .sc-gallery-caption { transform: none; }
    .sc-gallery-immersive { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; grid-auto-rows: clamp(150px, 20vw, 210px); }
    .sc-gallery-immersive .sc-gallery-item { aspect-ratio: auto; }
    .sc-gallery-immersive .sc-gallery-item:nth-child(6n + 2), .sc-gallery-immersive .sc-gallery-item:nth-child(6n + 5) { grid-row: span 2; }
    .sc-gallery-featured { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
    .sc-gallery-featured .sc-gallery-item { aspect-ratio: auto; }
    .sc-gallery-featured .sc-gallery-item:first-child { grid-row: span 2; grid-column: span 2; }
    .sc-gallery-featured .sc-gallery-item:first-child img { height: 100%; }

    .sc-features-list { display: flex; flex-direction: column; }
    .sc-feature-row {
        display: grid; grid-template-columns: auto minmax(0, 1fr);
        column-gap: 1.75rem; padding-block: 1.35rem;
        border-bottom: 1px solid var(--sc-border);
    }
    .sc-feature-row:first-child { border-top: 1px solid var(--sc-border); }
    .sc-feature-row h3 { grid-column: 2; margin: 0 0 0.35rem; font-family: var(--sc-font-display); font-size: 1.15rem; }
    .sc-feature-row p { grid-column: 2; margin: 0; }
    .sc-feature-idx {
        grid-row: 1 / span 2; padding-top: 0.18rem;
        font-size: 0.8rem; font-weight: 800; letter-spacing: 0.14em;
        color: color-mix(in srgb, var(--sc-text) 42%, transparent);
        font-variant-numeric: tabular-nums;
    }
    .sc-feature-split { display: grid; grid-template-columns: 1fr 1fr; gap: clamp(1.5rem, 4vw, 3rem); align-items: center; }
    .sc-feature-split .sc-feature-media { border-radius: var(--sc-radius); overflow: hidden; }
    .sc-feature-split .sc-feature-media img { width: 100%; height: 100%; object-fit: cover; aspect-ratio: 4 / 3; }

    /* 24g. Living Aurora Hero (replaces legacy frame-sequence scrub) */
    .sc-aurora-hero {
        position: relative;
        min-height: clamp(620px, 94svh, 960px);
        display: flex;
        align-items: center;
        overflow: hidden;
        color: #fff;
        background: #0b0f19;
    }
    .sc-aurora-canvas { position: absolute; inset: 0; width: 100%; height: 100%; display: block; }
    .sc-aurora-vignette {
        position: absolute; inset: 0;
        background:
            radial-gradient(120% 90% at 50% 10%, transparent 40%, rgba(2, 6, 23, 0.55) 100%),
            linear-gradient(180deg, rgba(2, 6, 23, 0.28) 0%, transparent 30%, transparent 62%, rgba(2, 6, 23, 0.72) 100%);
        pointer-events: none;
    }
    .sc-aurora-content { position: relative; z-index: 2; width: 100%; }
    .sc-aurora-inner { max-width: 58rem; will-change: transform, opacity; transition: opacity 0.25s ease; }
    .sc-aurora-hero .sc-eyebrow { color: rgba(255, 255, 255, 0.85); }
    .sc-aurora-hero .sc-eyebrow::before { background: rgba(255, 255, 255, 0.85); }
    .sc-aurora-title {
        margin: 0;
        color: #fff;
        font-family: var(--sc-font-display);
        font-size: clamp(2.4rem, 1.4rem + 4.2vw, 4.6rem);
        font-weight: 800;
        line-height: 1.04;
        letter-spacing: -0.025em;
        text-wrap: balance;
    }
    .sc-aurora-subtitle {
        margin: clamp(1.1rem, 2vw, 1.5rem) 0 0;
        max-width: 40rem;
        color: rgba(255, 255, 255, 0.82);
        font-size: clamp(1rem, 0.95rem + 0.4vw, 1.2rem);
        line-height: 1.6;
    }
    .sc-aurora-actions { display: flex; flex-wrap: wrap; gap: 0.9rem; margin-top: clamp(1.6rem, 3vw, 2.2rem); }
    .sc-btn-outline-light {
        background: transparent;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.55);
    }
    .sc-btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: #fff;
        box-shadow: none;
    }
    .sc-aurora-chips {
        position: absolute;
        z-index: 2;
        inset-inline: clamp(1rem, 5vw, 4rem);
        bottom: clamp(1.4rem, 4vh, 3rem);
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
    }
    .sc-aurora-chip {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        padding: 0.8rem 1.15rem;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.16);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        box-shadow: 0 14px 34px -18px rgba(0, 0, 0, 0.6);
    }
    .sc-aurora-chip-num {
        font-family: var(--sc-font-display);
        font-size: clamp(1.25rem, 1.1rem + 0.8vw, 1.7rem);
        font-weight: 800;
        color: #fff;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }
    .sc-aurora-chip-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.7);
    }
    .sc-aurora-progress {
        position: absolute;
        z-index: 3;
        inset-inline: 0;
        bottom: 0;
        height: 3px;
        background: rgba(255, 255, 255, 0.14);
    }
    .sc-aurora-progress-bar {
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, var(--sc-secondary), var(--sc-accent));
    }


    /* ────────────────────────────────────────────────────────────
       25. RESPONSIVE
       ──────────────────────────────────────────────────────────── */
    @media (max-width: 1023px) {
        .sc-nav-menu, .sc-nav-divider, .sc-nav-login { display: none; }
        .sc-hamburger { display: inline-flex; }
        .sc-mobile-panel { display: block; }
        .sc-hero-grid { grid-template-columns: 1fr; }
        .sc-hero-media { max-width: 34rem; }
        .sc-split { grid-template-columns: 1fr; }
        .sc-feature-split, .sc-quote-split { grid-template-columns: 1fr; }
        .sc-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sc-grid-3, .sc-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sc-footer-grid { grid-template-columns: 1fr 1fr; }
        .sc-hero-float-1 { left: 0.5rem; }
        .sc-hero-float-2 { right: 0.5rem; }
    }
    @media (max-width: 639px) {
        .sc-grid-3, .sc-grid-4 { grid-template-columns: 1fr; }
        .sc-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sc-footer-grid { grid-template-columns: 1fr; }
        .sc-nav-actions .sc-btn { display: none; }
        .sc-footer-bottom { flex-direction: column; text-align: center; }
        .sc-cylinder-arrow { display: none; }
        .sc-hero-float { display: none; }
    }

    /* ────────────────────────────────────────────────────────────
       26. REDUCED MOTION
       ──────────────────────────────────────────────────────────── */
    @media (prefers-reduced-motion: reduce) {
        .sc-site *, .sc-site *::before, .sc-site *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
        .sc-site [data-sc-reveal] { opacity: 1; transform: none; }
        .sc-site .sc-depth-layer { display: none !important; }
        .sc-site .sc-card-hover:hover, .sc-site .sc-tile:hover, .sc-site .sc-news-card:hover { transform: none; }
        .sc-site .sc-coverflow { perspective: none; }
        .sc-site .sc-coverflow-card { transform: translate(-50%, -50%) translateX(0) !important; z-index: 1; }
        .sc-site .sc-coverflow-card.is-center { z-index: 2; }
        .sc-site .sc-coverflow-dim { display: none; }
        .sc-site .sc-coverflow-controls { display: none; }
        .sc-site .sc-marquee-track { animation: none; }
        .sc-site .sc-kinetic-word { opacity: 1; transform: none; filter: none; }
        .sc-site .sc-scroll-highlight .sc-sh-word.is-lit { color: var(--sc-text); }
    }

    /* ────────────────────────────────────────────────────────────
       26b. STUDIO CANVAS (motion off)
       The builder preview renders every section inside .sc-site with
       data-sc-motion="off". Livewire morphs re-apply the server HTML,
       which never carries the is-in / is-lit classes that scroll-driven
       animations add at runtime — so after a morph (selecting a section,
       editing a style) those sections would otherwise snap back to their
       hidden (opacity: 0 / dim) resting state and appear empty. These
       overrides force motion content fully visible inside the canvas.
       ──────────────────────────────────────────────────────────── */
    .sc-site[data-sc-motion="off"] [data-sc-reveal] {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }
    .sc-site[data-sc-motion="off"] .sc-kinetic-word {
        opacity: 1 !important;
        transform: none !important;
        filter: none !important;
    }
    .sc-site[data-sc-motion="off"] .sc-scroll-highlight .sc-sh-word {
        color: var(--sc-sh-lit, var(--sc-text)) !important;
    }
    .sc-site[data-sc-motion="off"] .sc-scroll-highlight .sc-sh-body {
        color: var(--sc-sh-lit, var(--sc-text)) !important;
    }

    /* ════════════════════════════════════════════════════════════════
       27. TEMPLATE IDENTITIES
       Each template gets a distinct design language through targeted
       overrides. Shared component layer above stays identical.
       ════════════════════════════════════════════════════════════════ */

    /* ── 27a. Heritage Editorial — navy / cream / gold, serif editorial ──
       A century-old institution's prospectus. Paper-cream canvas, serif
       display faces, small caps, double rules, corner ornaments. Formal,
       symmetric, unhurried. No rounded corners, no gimmicks — typographic
       and quietly expensive. */
    .sc-site[data-sc-template="heritage-editorial"] {
        --sc-radius: 0px;
        --sc-radius-btn: 0px;
        --sc-border: rgba(31, 58, 95, 0.22);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-main {
        background:
            linear-gradient(rgba(250, 246, 236, 0.55), rgba(250, 246, 236, 0.55)),
            radial-gradient(120% 60% at 0% 0%, color-mix(in srgb, var(--sc-primary) 5%, transparent), transparent 60%);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-nav {
        border-bottom: 1px solid var(--sc-border);
        background: linear-gradient(180deg, rgba(255, 253, 247, 0.98), rgba(250, 246, 236, 0.96));
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-nav::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 2px;
        background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--sc-accent) 60%, transparent), transparent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-brand-name {
        font-family: var(--sc-font-display);
        letter-spacing: 0.02em;
        text-transform: none;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-nav-link {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        position: relative;
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -0.5rem;
        height: 2px;
        background: var(--sc-accent);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.25s ease;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-nav-link:hover::after,
    .sc-site[data-sc-template="heritage-editorial"] .sc-nav-link.is-active::after { transform: scaleX(1); }
    .sc-site[data-sc-template="heritage-editorial"] .sc-eyebrow {
        font-weight: 700;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        color: var(--sc-accent);
        font-size: 0.66rem;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-eyebrow::before {
        content: '';
        width: 3rem;
        height: 1px;
        background: var(--sc-accent);
        box-shadow: 0 3px 0 color-mix(in srgb, var(--sc-accent) 45%, transparent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-section-head.is-center .sc-eyebrow::before { display: none; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-section-head.is-center .sc-eyebrow::after {
        content: '';
        width: 3rem;
        height: 1px;
        background: var(--sc-accent);
        box-shadow: 0 3px 0 color-mix(in srgb, var(--sc-accent) 45%, transparent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-section-title {
        font-weight: 600;
        letter-spacing: -0.015em;
        line-height: 1.12;
        font-family: var(--sc-font-display);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-section-title::after {
        content: '';
        display: block;
        width: 4.5rem;
        height: 2px;
        margin-top: 1.15rem;
        background: linear-gradient(90deg, var(--sc-accent) 0%, var(--sc-accent) 38%, transparent 38%, transparent 44%, var(--sc-accent) 44%);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-section-head.is-center .sc-section-title::after { margin-inline: auto; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-section-lead {
        font-family: var(--sc-font-display);
        font-style: italic;
        color: color-mix(in srgb, var(--sc-text) 70%, transparent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-btn {
        text-transform: uppercase;
        letter-spacing: 0.14em;
        font-weight: 700;
        font-size: 0.82rem;
        border-radius: 0;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-btn-primary {
        background: var(--sc-primary);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--sc-accent) 60%, transparent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-btn-primary:hover {
        background: color-mix(in srgb, var(--sc-primary) 82%, #000);
        box-shadow: inset 0 0 0 1px var(--sc-accent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-btn-ghost { border-radius: 0; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-card,
    .sc-site[data-sc-template="heritage-editorial"] .sc-tile,
    .sc-site[data-sc-template="heritage-editorial"] .sc-faq-item {
        border-radius: 0;
        border: 1px solid var(--sc-border);
        box-shadow: none;
        background: var(--sc-surface);
        transition: border-color 0.25s ease, transform 0.25s ease;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-card:hover,
    .sc-site[data-sc-template="heritage-editorial"] .sc-tile:hover {
        border-color: color-mix(in srgb, var(--sc-accent) 65%, transparent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-tile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--sc-accent), transparent);
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-tile:hover::before { opacity: 1; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-hero-title {
        font-weight: 600;
        letter-spacing: -0.02em;
        line-height: 1.06;
        font-family: var(--sc-font-display);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-hero-lead {
        font-size: 1.08rem;
        line-height: 1.7;
        max-width: 36rem;
        color: color-mix(in srgb, var(--sc-text) 84%, transparent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-hero-frame {
        border-radius: 0;
        box-shadow: none;
        border: 1px solid var(--sc-accent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-hero-frame::before {
        content: '✦';
        position: absolute;
        top: 0.2rem;
        left: 0.2rem;
        color: var(--sc-accent);
        font-size: 0.8rem;
        z-index: 2;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-hero-frame::after {
        content: '';
        position: absolute;
        inset: 0.6rem;
        border: 1px solid color-mix(in srgb, var(--sc-accent) 55%, transparent);
        pointer-events: none;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-hero-frame-ring { display: none; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-hero-float {
        border-radius: 0;
        font-family: var(--sc-font-display);
        background: var(--sc-surface);
        border: 1px solid var(--sc-border);
        box-shadow: none;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-stat {
        border-radius: 0;
        border-top: 2px solid color-mix(in srgb, var(--sc-primary) 80%, transparent);
        padding-top: 1.4rem;
        background: transparent;
        box-shadow: none;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-stat-num {
        font-family: var(--sc-font-display);
        font-weight: 600;
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-stat-label {
        text-transform: uppercase;
        letter-spacing: 0.14em;
        font-size: 0.68rem;
        color: var(--sc-accent);
        font-weight: 600;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-tile-index {
        font-family: var(--sc-font-display);
        color: var(--sc-accent);
        font-weight: 600;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-media { border-radius: 0; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-quote-mark {
        font-family: var(--sc-font-display);
        color: var(--sc-accent);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-quote-body {
        font-family: var(--sc-font-display);
        font-style: italic;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-faq-item {
        border-left: 3px solid var(--sc-accent);
        padding-left: 1.25rem;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-cta-band {
        border-radius: 0;
        border: 1px solid color-mix(in srgb, var(--sc-accent) 50%, transparent);
        background:
            linear-gradient(rgba(250, 246, 236, 0.97), rgba(250, 246, 236, 0.97)),
            radial-gradient(90% 150% at 80% 0%, color-mix(in srgb, var(--sc-accent) 16%, transparent), transparent);
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-cta-band h2 { color: var(--sc-text); }
    .sc-site[data-sc-template="heritage-editorial"] .sc-cta-band p { color: color-mix(in srgb, var(--sc-text) 78%, transparent); }
    .sc-site[data-sc-template="heritage-editorial"] .sc-cta-band .sc-btn-light {
        background: var(--sc-primary);
        color: var(--sc-ink);
        box-shadow: none;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-cta-band .sc-section-title {
        font-family: var(--sc-font-display);
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-footer {
        border-top: 3px double color-mix(in srgb, var(--sc-accent) 70%, transparent);
        background: var(--sc-primary);
        color: #f4efe4;
    }
    .sc-site[data-sc-template="heritage-editorial"] .sc-footer h4 { color: #e8d9ae; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-footer-about,
    .sc-site[data-sc-template="heritage-editorial"] .sc-footer-bottom,
    .sc-site[data-sc-template="heritage-editorial"] .sc-footer-links a { color: rgba(244, 239, 228, 0.72); }
    .sc-site[data-sc-template="heritage-editorial"] .sc-footer-links a:hover { color: #e8d9ae; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-social a { border-color: rgba(244, 239, 228, 0.24); background: transparent; color: #f4efe4; }
    .sc-site[data-sc-template="heritage-editorial"] .sc-social a:hover { background: var(--sc-accent); border-color: var(--sc-accent); }
    .sc-site[data-sc-template="heritage-editorial"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.14);
    }

    /* ── 27b. Cinematic Immersive — near-black / cyan / rose ──
       A film-studio site. Near-black canvas with an aurora gradient wash,
       glass surfaces, gradient ink, scene-numbered chapters, film grain,
       glowing buttons. Motion-forward; the aurora hero is its showpiece. */
    .sc-site[data-sc-template="cinematic-immersive"] {
        --sc-border: rgba(255, 255, 255, 0.12);
        --sc-shadow: 0 18px 40px -20px rgba(0, 0, 0, 0.65);
        --sc-shadow-lg: 0 30px 60px -24px rgba(0, 0, 0, 0.7);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-main {
        background:
            radial-gradient(90% 70% at 15% 0%, color-mix(in srgb, var(--sc-primary) 55%, transparent), transparent 55%),
            radial-gradient(70% 60% at 90% 25%, color-mix(in srgb, var(--sc-secondary) 40%, transparent), transparent 50%),
            radial-gradient(80% 60% at 50% 100%, color-mix(in srgb, var(--sc-accent) 26%, transparent), transparent 55%),
            #070a12;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-main::after {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 60;
        opacity: 0.05;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)'/%3E%3C/svg%3E");
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-nav {
        background: color-mix(in srgb, #0b0f19 78%, transparent);
        border-bottom-color: rgba(255, 255, 255, 0.08);
        -webkit-backdrop-filter: blur(12px);
        backdrop-filter: blur(12px);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-nav::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 1px;
        background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--sc-secondary) 55%, transparent), transparent);
        opacity: 0.7;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-brand-name {
        font-family: var(--sc-font-display);
        font-weight: 800;
        letter-spacing: 0.06em;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-nav-link {
        color: rgba(255, 255, 255, 0.82);
        letter-spacing: 0.02em;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-nav-link:hover {
        color: var(--sc-secondary);
        text-shadow: 0 0 18px color-mix(in srgb, var(--sc-secondary) 60%, transparent);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-eyebrow {
        font-family: var(--sc-font-display);
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: var(--sc-secondary);
        font-size: 0.68rem;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-eyebrow::before {
        content: '';
        width: 2.2rem;
        height: 1px;
        background: linear-gradient(90deg, var(--sc-secondary), transparent);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-section-head.is-center .sc-eyebrow::before {
        content: '✦';
        width: auto;
        height: auto;
        background: none;
        color: var(--sc-secondary);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-section-title {
        font-weight: 700;
        letter-spacing: -0.02em;
        font-family: var(--sc-font-display);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-section { counter-increment: sc-chapter; }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-section-title::before {
        content: 'CHAPTER ' counter(sc-chapter, decimal-leading-zero);
        display: block;
        font-family: var(--sc-font-display);
        font-weight: 800;
        font-size: 0.8rem;
        letter-spacing: 0.32em;
        background: linear-gradient(100deg, var(--sc-secondary), var(--sc-accent));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 0.7rem;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-section-title::after {
        content: '';
        display: block;
        width: 4.5rem;
        height: 2px;
        margin-top: 1.1rem;
        background: linear-gradient(90deg, var(--sc-secondary), var(--sc-accent));
        box-shadow: 0 0 14px color-mix(in srgb, var(--sc-secondary) 55%, transparent);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-section-head.is-center .sc-section-title::after { margin-inline: auto; }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-section-lead {
        color: color-mix(in srgb, var(--sc-text) 72%, transparent);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-btn-primary {
        background: linear-gradient(100deg, color-mix(in srgb, var(--sc-secondary) 40%, var(--sc-primary)), var(--sc-primary));
        box-shadow: 0 14px 32px -12px color-mix(in srgb, var(--sc-secondary) 70%, transparent), 0 0 0 1px rgba(255, 255, 255, 0.14) inset;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-btn-accent {
        background: linear-gradient(100deg, color-mix(in srgb, var(--sc-accent) 40%, var(--sc-primary)), var(--sc-primary));
        box-shadow: 0 14px 32px -12px color-mix(in srgb, var(--sc-accent) 70%, transparent), 0 0 0 1px rgba(255, 255, 255, 0.14) inset;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-btn-ghost {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.18);
        color: rgba(255, 255, 255, 0.85);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-card,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-tile,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-faq-item,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-hero-frame,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-media,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-stat {
        background: color-mix(in srgb, #111827 80%, transparent);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 26%, transparent);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-card:hover,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-tile:hover {
        border-color: color-mix(in srgb, var(--sc-secondary) 55%, transparent);
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--sc-secondary) 30%, transparent), 0 20px 44px -18px rgba(0, 0, 0, 0.8);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-tile-icon {
        background: linear-gradient(135deg, color-mix(in srgb, var(--sc-secondary) 22%, transparent), color-mix(in srgb, var(--sc-accent) 16%, transparent));
        color: var(--sc-secondary);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 32%, transparent);
        box-shadow: 0 0 22px color-mix(in srgb, var(--sc-secondary) 22%, transparent);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-tile-media img {
        transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.4s ease;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-tile:hover .sc-tile-media img { transform: scale(1.06); }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-tile-media::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(2, 6, 23, 0.7), transparent 55%);
        pointer-events: none;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-hero-frame-ring {
        border-style: solid;
        border-color: color-mix(in srgb, var(--sc-secondary) 34%, transparent);
        animation: sc-hero-pulse 5s ease-in-out infinite;
    }
    @keyframes sc-hero-pulse {
        0%, 100% { transform: translate(0.8rem, -0.8rem) rotate(1deg); opacity: 0.7; }
        50% { transform: translate(1.2rem, -1.2rem) rotate(2deg); opacity: 1; }
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-stat-num,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-quote-mark {
        background: linear-gradient(100deg, var(--sc-secondary), var(--sc-accent));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-stat-num {
        font-weight: 800;
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-stat-label {
        color: rgba(255, 255, 255, 0.6);
        letter-spacing: 0.08em;
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-quote-body {
        color: rgba(255, 255, 255, 0.88);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-cta-band {
        background:
            radial-gradient(80% 140% at 20% 0%, color-mix(in srgb, var(--sc-secondary) 34%, transparent), transparent),
            radial-gradient(70% 120% at 90% 100%, color-mix(in srgb, var(--sc-accent) 30%, transparent), transparent),
            linear-gradient(120deg, #0b0f19, #151b31 55%, #0b0f19);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 32%, transparent);
        box-shadow: 0 0 60px color-mix(in srgb, var(--sc-secondary) 16%, transparent);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-hero-float {
        background: color-mix(in srgb, #111827 82%, transparent);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-footer {
        background: color-mix(in srgb, #0b0f19 92%, transparent);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.75);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-footer h4 { color: rgba(255, 255, 255, 0.92); }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-footer-about,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-footer-bottom,
    .sc-site[data-sc-template="cinematic-immersive"] .sc-footer-links a { color: rgba(255, 255, 255, 0.6); }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-footer-links a:hover { color: var(--sc-secondary); }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-social a { border-color: rgba(255, 255, 255, 0.18); background: rgba(255, 255, 255, 0.04); color: rgba(255, 255, 255, 0.75); }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-social a:hover { background: var(--sc-secondary); border-color: var(--sc-secondary); }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-date-chip {
        background: color-mix(in srgb, var(--sc-secondary) 16%, transparent);
        color: var(--sc-secondary);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 30%, transparent);
    }
    .sc-site[data-sc-template="cinematic-immersive"] .sc-badge {
        background: color-mix(in srgb, var(--sc-accent) 16%, transparent);
        color: var(--sc-accent);
        border: 1px solid color-mix(in srgb, var(--sc-accent) 34%, transparent);
        -webkit-backdrop-filter: blur(6px);
        backdrop-filter: blur(6px);
    }

    /* ── 27c. Modern Vibrant — violet / cyan / amber, bold & youthful ──
       A creative-agency site. Color-blocked, big rounded surfaces, gradient
       ink, pill eyebrows, playful floating orbs, tilt-on-hover cards.
       Confident, bright, energetic — designed for the TikTok generation. */
    .sc-site[data-sc-template="modern-vibrant"] {
        --sc-radius: 20px;
        --sc-radius-btn: 9999px;
        --sc-shadow: 0 16px 40px -18px rgba(124, 58, 237, 0.32);
        --sc-shadow-lg: 0 30px 64px -24px rgba(124, 58, 237, 0.38);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-main {
        background:
            radial-gradient(70% 50% at 100% 0%, color-mix(in srgb, var(--sc-accent) 9%, transparent), transparent 60%),
            radial-gradient(60% 45% at 0% 100%, color-mix(in srgb, var(--sc-primary) 8%, transparent), transparent 60%),
            var(--sc-bg);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-nav {
        background: rgba(255, 255, 255, 0.86);
        -webkit-backdrop-filter: blur(12px);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid color-mix(in srgb, var(--sc-primary) 12%, transparent);
        box-shadow: 0 10px 30px -18px rgba(124, 58, 237, 0.22);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-brand-name {
        background: linear-gradient(120deg, var(--sc-primary), var(--sc-accent));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 800;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-nav-link {
        font-weight: 700;
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-nav-link:hover {
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-eyebrow {
        padding: 0.45rem 1rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-accent) 16%, transparent);
        color: var(--sc-accent);
        letter-spacing: 0.12em;
        font-size: 0.68rem;
        font-weight: 800;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-eyebrow::before { display: none; }
    .sc-site[data-sc-template="modern-vibrant"] .sc-eyebrow::after {
        content: '✦';
        margin-left: 0.5rem;
        font-size: 0.7rem;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-section-title {
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--sc-primary);
        line-height: 1.05;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-section-title strong {
        background: linear-gradient(120deg, var(--sc-primary), var(--sc-accent));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-section-head.is-center .sc-eyebrow { margin-inline: auto; }
    .sc-site[data-sc-template="modern-vibrant"] .sc-btn-primary {
        background: linear-gradient(135deg, var(--sc-primary), color-mix(in srgb, var(--sc-primary) 62%, var(--sc-secondary)));
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-primary) 65%, transparent);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-btn-primary:hover { transform: translateY(-2px); }
    .sc-site[data-sc-template="modern-vibrant"] .sc-btn-accent {
        background: linear-gradient(135deg, var(--sc-accent), color-mix(in srgb, var(--sc-accent) 60%, var(--sc-secondary)));
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-accent) 60%, transparent);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-btn-ghost {
        border-color: var(--sc-primary);
        color: var(--sc-primary);
        border-radius: 9999px;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-card,
    .sc-site[data-sc-template="modern-vibrant"] .sc-tile,
    .sc-site[data-sc-template="modern-vibrant"] .sc-faq-item {
        border-radius: 22px;
        border: 1px solid color-mix(in srgb, var(--sc-primary) 14%, transparent);
        box-shadow: var(--sc-shadow);
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-card:hover,
    .sc-site[data-sc-template="modern-vibrant"] .sc-tile:hover {
        transform: translateY(-6px);
        box-shadow: var(--sc-shadow-lg);
        border-color: color-mix(in srgb, var(--sc-primary) 34%, transparent);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-tile::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 22px;
        background: linear-gradient(135deg, color-mix(in srgb, var(--sc-primary) 14%, transparent), transparent 50%, color-mix(in srgb, var(--sc-accent) 14%, transparent));
        pointer-events: none;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-hero-frame {
        border-radius: 30px;
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-hero-frame-ring {
        border-style: dashed;
        border-color: color-mix(in srgb, var(--sc-accent) 55%, transparent);
        border-radius: 36px;
        inset: -1.1rem;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-hero-backdrop .sc-orb-1 { background: color-mix(in srgb, var(--sc-primary) 60%, transparent); }
    .sc-site[data-sc-template="modern-vibrant"] .sc-hero-backdrop .sc-orb-2 { background: color-mix(in srgb, var(--sc-accent) 55%, transparent); }
    .sc-site[data-sc-template="modern-vibrant"] .sc-hero-title {
        font-weight: 800;
        letter-spacing: -0.035em;
        line-height: 1.02;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-hero-lead {
        color: color-mix(in srgb, var(--sc-text) 72%, transparent);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-stat-num {
        color: var(--sc-primary);
        font-weight: 800;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-stat-label {
        font-weight: 700;
        color: color-mix(in srgb, var(--sc-text) 62%, transparent);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-tile-icon {
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-secondary));
        color: #fff;
        box-shadow: 0 10px 24px -10px color-mix(in srgb, var(--sc-primary) 55%, transparent);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-tile-media {
        border-radius: 22px;
        overflow: hidden;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-tile-media img { transition: transform 0.5s ease; }
    .sc-site[data-sc-template="modern-vibrant"] .sc-tile:hover .sc-tile-media img { transform: scale(1.08); }
    .sc-site[data-sc-template="modern-vibrant"] .sc-media { border-radius: 26px; }
    .sc-site[data-sc-template="modern-vibrant"] .sc-cta-band {
        background: linear-gradient(120deg, var(--sc-primary), color-mix(in srgb, var(--sc-primary) 45%, var(--sc-secondary)) 55%, color-mix(in srgb, var(--sc-accent) 45%, var(--sc-primary)));
        border-radius: 30px;
        box-shadow: 0 30px 70px -24px color-mix(in srgb, var(--sc-primary) 55%, transparent);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-quote-mark { color: var(--sc-accent); }
    .sc-site[data-sc-template="modern-vibrant"] .sc-avatar {
        border: 3px solid color-mix(in srgb, var(--sc-accent) 60%, transparent);
        box-shadow: var(--sc-shadow);
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-date-chip {
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-primary) 10%, transparent);
        color: var(--sc-primary);
        font-weight: 700;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-footer {
        border-top: 4px solid transparent;
        border-image: linear-gradient(90deg, var(--sc-primary), var(--sc-secondary), var(--sc-accent)) 1;
    }
    .sc-site[data-sc-template="modern-vibrant"] .sc-faq-item {
        border-radius: 18px;
    }

    /* ── 27d. Minimalist Academic — ink / amber, restrained prestige ──
       A research institute's quiet confidence. Generous whitespace, hairlines
       only, no shadows, one amber accent, micro-labels, numbered lists.
       Swiss-adjacent and type-driven; decoration is limited to a rule. */
    .sc-site[data-sc-template="minimalist-academic"] {
        --sc-radius: 2px;
        --sc-radius-btn: 2px;
        --sc-shadow: none;
        --sc-shadow-lg: 0 18px 36px -24px rgba(15, 23, 42, 0.18);
        --sc-border: rgba(15, 23, 42, 0.16);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-main {
        background: var(--sc-bg);
    }
    .sc-site[data-sc-template="minimalist-academic"] h1,
    .sc-site[data-sc-template="minimalist-academic"] h2,
    .sc-site[data-sc-template="minimalist-academic"] h3 {
        letter-spacing: -0.01em;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-nav {
        background: transparent;
        border-bottom: 1px solid var(--sc-border);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-nav-link {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: var(--sc-text);
        position: relative;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -0.55rem;
        height: 1px;
        background: var(--sc-accent);
        transform: scaleX(0);
        transition: transform 0.22s ease;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-nav-link:hover::after,
    .sc-site[data-sc-template="minimalist-academic"] .sc-nav-link.is-active::after { transform: scaleX(1); }
    .sc-site[data-sc-template="minimalist-academic"] .sc-brand-name {
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-size: 0.95rem;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-eyebrow {
        color: var(--sc-text);
        letter-spacing: 0.22em;
        font-size: 0.68rem;
        font-weight: 600;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-eyebrow::before {
        content: '';
        width: 1.4rem;
        background: var(--sc-accent);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-section-title {
        font-weight: 500;
        font-size: clamp(1.7rem, 1.3rem + 1.6vw, 2.4rem);
        letter-spacing: -0.02em;
        line-height: 1.15;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-section { counter-increment: sc-item; }
    .sc-site[data-sc-template="minimalist-academic"] .sc-section-title::before {
        content: counter(sc-item, decimal-leading-zero);
        display: block;
        font-family: var(--sc-font-display);
        font-weight: 500;
        font-size: 0.7rem;
        letter-spacing: 0.24em;
        color: var(--sc-accent);
        margin-bottom: 0.9rem;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-section-head.is-center .sc-section-title::before { text-align: center; }
    .sc-site[data-sc-template="minimalist-academic"] .sc-section-lead {
        font-size: 1.02rem;
        color: color-mix(in srgb, var(--sc-text) 66%, transparent);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-btn {
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 700;
        font-size: 0.76rem;
        border-radius: 0;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-btn-primary {
        background: var(--sc-primary);
        color: #fff;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-btn-primary:hover {
        background: var(--sc-accent);
        box-shadow: none;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-btn-ghost {
        border-radius: 0;
        border: 1px solid var(--sc-text);
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-card,
    .sc-site[data-sc-template="minimalist-academic"] .sc-tile,
    .sc-site[data-sc-template="minimalist-academic"] .sc-faq-item {
        border-radius: 0;
        border: 1px solid var(--sc-border);
        box-shadow: none;
        background: transparent;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-card:hover,
    .sc-site[data-sc-template="minimalist-academic"] .sc-tile:hover {
        border-color: var(--sc-accent);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-tile-icon {
        background: transparent;
        border: 1px solid var(--sc-border);
        color: var(--sc-primary);
        border-radius: 2px;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-hero-title {
        font-weight: 500;
        letter-spacing: -0.025em;
        line-height: 1.05;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-hero-lead {
        font-size: 1.02rem;
        line-height: 1.7;
        max-width: 40rem;
        color: color-mix(in srgb, var(--sc-text) 72%, transparent);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-hero-frame {
        border-radius: 2px;
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-hero-frame-ring {
        display: none;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-stat {
        border-top: 1px solid var(--sc-border);
        background: transparent;
        box-shadow: none;
        padding-top: 1.25rem;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-stat-num {
        font-weight: 500;
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-stat-label {
        color: var(--sc-accent);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 0.68rem;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-tile-numbered {
        border-left: 1px solid var(--sc-border);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-tile-index {
        color: var(--sc-accent);
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.1em;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-media { border-radius: 2px; }
    .sc-site[data-sc-template="minimalist-academic"] .sc-cta-band {
        border-radius: 0;
        background: transparent;
        border: 1px solid var(--sc-border);
        border-top: 2px solid var(--sc-accent);
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-cta-band h2 { color: var(--sc-text); }
    .sc-site[data-sc-template="minimalist-academic"] .sc-cta-band p { color: color-mix(in srgb, var(--sc-text) 78%, transparent); }
    .sc-site[data-sc-template="minimalist-academic"] .sc-cta-band .sc-btn {
        border: 1px solid var(--sc-text);
        color: var(--sc-text);
        background: transparent;
        border-radius: 0;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-quote-mark {
        font-family: var(--sc-font-display);
        color: var(--sc-accent);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-quote-body {
        font-size: 1.25rem;
        line-height: 1.6;
        color: color-mix(in srgb, var(--sc-text) 88%, transparent);
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-faq-item {
        border: none;
        border-bottom: 1px solid var(--sc-border);
        background: transparent;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-date-chip {
        border: 1px solid var(--sc-border);
        border-radius: 0;
        background: transparent;
        color: var(--sc-accent);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 0.68rem;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-footer {
        border-top: 1px solid var(--sc-border);
        background: transparent;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-footer-bottom {
        border-top: 1px solid var(--sc-border);
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 0.7rem;
    }
    .sc-site[data-sc-template="minimalist-academic"] .sc-marquee-section {
        border-top: 1px solid var(--sc-border);
        border-bottom: 1px solid var(--sc-border);
    }

    /* ── 27e. Community Warm — rust / olive / cream, family-friendly ──
       A neighbourhood school's handcrafted warmth. Rounded, photo-heavy,
       earthy. Arched media, soft shadows, circle badges and a cream-tinted
       canvas — like a welcoming brochure from the head of the primary. */
    .sc-site[data-sc-template="community-warm"] {
        --sc-radius: 24px;
        --sc-radius-btn: 9999px;
        --sc-shadow: 0 14px 34px -18px rgba(154, 52, 18, 0.28);
        --sc-shadow-lg: 0 26px 60px -28px rgba(154, 52, 18, 0.32);
    }
    .sc-site[data-sc-template="community-warm"] .sc-main {
        background:
            linear-gradient(rgba(255, 252, 246, 0.7), rgba(255, 252, 246, 0.7)),
            radial-gradient(90% 55% at 90% 0%, color-mix(in srgb, var(--sc-secondary) 8%, transparent), transparent 60%),
            var(--sc-bg);
    }
    .sc-site[data-sc-template="community-warm"] .sc-nav {
        background: color-mix(in srgb, #fffdf7 94%, transparent);
        border-bottom: 1px solid color-mix(in srgb, var(--sc-secondary) 16%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-brand-name {
        font-family: var(--sc-font-display);
        font-weight: 700;
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="community-warm"] .sc-nav-link {
        font-weight: 700;
        color: color-mix(in srgb, var(--sc-text) 85%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-nav-link:hover {
        color: var(--sc-secondary);
        background: color-mix(in srgb, var(--sc-secondary) 8%, transparent);
        border-radius: 999px;
    }
    .sc-site[data-sc-template="community-warm"] .sc-eyebrow {
        padding: 0.4rem 1rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-secondary) 14%, transparent);
        color: var(--sc-secondary);
        letter-spacing: 0.1em;
        font-weight: 700;
    }
    .sc-site[data-sc-template="community-warm"] .sc-eyebrow::before { display: none; }
    .sc-site[data-sc-template="community-warm"] .sc-section-title {
        font-weight: 700;
        letter-spacing: -0.01em;
        font-family: var(--sc-font-display);
        line-height: 1.1;
    }
    .sc-site[data-sc-template="community-warm"] .sc-section-title::after {
        content: '';
        display: block;
        width: 3.2rem;
        height: 0.4rem;
        margin-top: 1rem;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--sc-secondary), var(--sc-accent));
    }
    .sc-site[data-sc-template="community-warm"] .sc-section-head.is-center .sc-section-title::after { margin-inline: auto; }
    .sc-site[data-sc-template="community-warm"] .sc-section-lead {
        color: color-mix(in srgb, var(--sc-text) 72%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-btn-primary {
        background: linear-gradient(135deg, var(--sc-primary), color-mix(in srgb, var(--sc-primary) 60%, var(--sc-accent)));
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-primary) 60%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-btn-accent {
        background: linear-gradient(135deg, var(--sc-secondary), var(--sc-accent));
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-secondary) 60%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-card,
    .sc-site[data-sc-template="community-warm"] .sc-tile,
    .sc-site[data-sc-template="community-warm"] .sc-faq-item {
        border-radius: 26px;
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 20%, transparent);
        box-shadow: var(--sc-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .sc-site[data-sc-template="community-warm"] .sc-card:hover,
    .sc-site[data-sc-template="community-warm"] .sc-tile:hover {
        transform: translateY(-4px);
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-site[data-sc-template="community-warm"] .sc-hero-frame,
    .sc-site[data-sc-template="community-warm"] .sc-media {
        border-radius: 999px 999px 26px 26px;
    }
    .sc-site[data-sc-template="community-warm"] .sc-hero-frame {
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-site[data-sc-template="community-warm"] .sc-hero-frame-ring {
        border-style: solid;
        border-color: color-mix(in srgb, var(--sc-accent) 40%, transparent);
        border-radius: 999px 999px 30px 30px;
    }
    .sc-site[data-sc-template="community-warm"] .sc-hero-float {
        border-radius: 999px;
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 24%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-stat-num {
        color: var(--sc-primary);
        font-weight: 800;
    }
    .sc-site[data-sc-template="community-warm"] .sc-stat {
        background: #fff;
        border-radius: 24px;
        box-shadow: var(--sc-shadow);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 14%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-tile-icon {
        border-radius: 50%;
        background: color-mix(in srgb, var(--sc-secondary) 16%, transparent);
        color: var(--sc-secondary);
    }
    .sc-site[data-sc-template="community-warm"] .sc-tile-media {
        border-radius: 22px 22px 999px 999px;
        overflow: hidden;
    }
    .sc-site[data-sc-template="community-warm"] .sc-cta-band {
        border-radius: 34px;
        background:
            linear-gradient(rgba(254, 247, 237, 0.94), rgba(254, 247, 237, 0.94)),
            radial-gradient(90% 160% at 20% 0%, color-mix(in srgb, var(--sc-secondary) 30%, transparent), transparent);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 30%, transparent);
        color: var(--sc-text);
    }
    .sc-site[data-sc-template="community-warm"] .sc-cta-band h2 { color: var(--sc-text); }
    .sc-site[data-sc-template="community-warm"] .sc-cta-band p { color: color-mix(in srgb, var(--sc-text) 78%, transparent); }
    .sc-site[data-sc-template="community-warm"] .sc-cta-band .sc-btn-light {
        background: var(--sc-primary);
        color: var(--sc-ink);
    }
    .sc-site[data-sc-template="community-warm"] .sc-cta-band .sc-section-title::after {
        background: linear-gradient(90deg, var(--sc-secondary), var(--sc-accent));
    }
    .sc-site[data-sc-template="community-warm"] .sc-quote-mark {
        color: color-mix(in srgb, var(--sc-secondary) 55%, transparent);
    }
    .sc-site[data-sc-template="community-warm"] .sc-avatar {
        border: 3px solid #fff;
        box-shadow: var(--sc-shadow);
    }
    .sc-site[data-sc-template="community-warm"] .sc-date-chip {
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-primary) 10%, transparent);
        color: var(--sc-primary);
        font-weight: 700;
    }
    .sc-site[data-sc-template="community-warm"] .sc-nav-link {
        font-weight: 700;
    }
    .sc-site[data-sc-template="community-warm"] .sc-footer {
        background: color-mix(in srgb, var(--sc-primary) 96%, #000);
        border-radius: 34px 34px 0 0;
        color: #f7e8d7;
    }
    .sc-site[data-sc-template="community-warm"] .sc-footer h4 { color: #e8c9a0; }
    .sc-site[data-sc-template="community-warm"] .sc-footer-about,
    .sc-site[data-sc-template="community-warm"] .sc-footer-bottom,
    .sc-site[data-sc-template="community-warm"] .sc-footer-links a { color: rgba(247, 232, 215, 0.72); }
    .sc-site[data-sc-template="community-warm"] .sc-footer-links a:hover { color: #e8c9a0; }
    .sc-site[data-sc-template="community-warm"] .sc-social a { border-color: rgba(247, 232, 215, 0.24); background: transparent; color: #f7e8d7; }
    .sc-site[data-sc-template="community-warm"] .sc-social a:hover { background: var(--sc-secondary); border-color: var(--sc-secondary); }
    .sc-site[data-sc-template="community-warm"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.14);
    }

    /* ── 28. Coastal Fresh — sky & seafoam, breezy and open ──
       A light, airy coastal campus. Pill buttons, rounded friendly cards,
       soft cyan shadows and a wave-to-sand accent. Feels like a brochure
       printed on a sunny day by the harbour. */
    .sc-site[data-sc-template="coastal-fresh"] {
        --sc-radius: 18px;
        --sc-radius-btn: 9999px;
        --sc-border: rgba(14, 116, 144, 0.16);
        --sc-shadow: 0 14px 34px -18px rgba(14, 116, 144, 0.24);
        --sc-shadow-lg: 0 26px 60px -26px rgba(14, 116, 144, 0.3);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-main {
        background:
            radial-gradient(80% 55% at 100% 0%, color-mix(in srgb, var(--sc-secondary) 9%, transparent), transparent 60%),
            radial-gradient(70% 50% at 0% 90%, color-mix(in srgb, var(--sc-accent) 7%, transparent), transparent 55%),
            var(--sc-bg);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-nav {
        background: rgba(255, 255, 255, 0.82);
        -webkit-backdrop-filter: blur(12px) saturate(150%);
        backdrop-filter: blur(12px) saturate(150%);
        border-bottom: 1px solid color-mix(in srgb, var(--sc-secondary) 20%, transparent);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-brand-name {
        font-weight: 700;
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-nav-link {
        font-weight: 600;
        color: color-mix(in srgb, var(--sc-text) 82%, transparent);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-nav-link:hover {
        color: var(--sc-primary);
        background: color-mix(in srgb, var(--sc-secondary) 10%, transparent);
        border-radius: 999px;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-eyebrow {
        padding: 0.35rem 1rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-secondary) 12%, transparent);
        color: var(--sc-primary);
        letter-spacing: 0.12em;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-eyebrow::before { display: none; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-eyebrow::after {
        content: '〰';
        margin-left: 0.4rem;
        font-size: 0.75rem;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-section-head.is-center .sc-eyebrow { margin-inline: auto; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-section-title {
        font-family: var(--sc-font-display);
        font-weight: 600;
        letter-spacing: -0.01em;
        line-height: 1.08;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-section-title::after {
        content: '';
        display: block;
        width: 4rem;
        height: 0.35rem;
        margin-top: 1rem;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--sc-primary), var(--sc-secondary), var(--sc-accent));
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-section-head.is-center .sc-section-title::after { margin-inline: auto; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-btn-primary {
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-secondary));
        box-shadow: 0 16px 32px -14px color-mix(in srgb, var(--sc-secondary) 60%, transparent);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-btn-accent {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        color: var(--sc-primary);
        box-shadow: 0 16px 32px -14px color-mix(in srgb, var(--sc-accent) 60%, transparent);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-btn-ghost {
        border-color: var(--sc-secondary);
        color: var(--sc-primary);
        border-radius: 9999px;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-card,
    .sc-site[data-sc-template="coastal-fresh"] .sc-tile,
    .sc-site[data-sc-template="coastal-fresh"] .sc-faq-item {
        border-radius: 22px;
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 18%, transparent);
        box-shadow: var(--sc-shadow);
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-card:hover,
    .sc-site[data-sc-template="coastal-fresh"] .sc-tile:hover {
        transform: translateY(-4px);
        box-shadow: var(--sc-shadow-lg);
        border-color: color-mix(in srgb, var(--sc-secondary) 40%, transparent);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-hero-frame,
    .sc-site[data-sc-template="coastal-fresh"] .sc-media {
        border-radius: 26px;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-hero-frame {
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-hero-frame-ring {
        border-style: dashed;
        border-color: color-mix(in srgb, var(--sc-secondary) 45%, transparent);
        border-radius: 32px;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-stat-num {
        color: var(--sc-primary);
        font-weight: 800;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-stat-label {
        color: color-mix(in srgb, var(--sc-secondary) 90%, transparent);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-tile-icon {
        border-radius: 50%;
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-secondary));
        color: #fff;
        box-shadow: 0 10px 24px -10px color-mix(in srgb, var(--sc-secondary) 55%, transparent);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-tile-media { border-radius: 20px; overflow: hidden; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-tile-media img { transition: transform 0.5s ease; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-tile:hover .sc-tile-media img { transform: scale(1.07); }
    .sc-site[data-sc-template="coastal-fresh"] .sc-cta-band {
        background:
            radial-gradient(80% 150% at 15% 0%, color-mix(in srgb, var(--sc-secondary) 45%, transparent), transparent 60%),
            radial-gradient(70% 140% at 90% 100%, color-mix(in srgb, var(--sc-accent) 26%, transparent), transparent 55%),
            linear-gradient(120deg, #0b3b49, #0e7490 60%, #0b5569);
        border-radius: 28px;
        box-shadow: 0 30px 70px -24px rgba(14, 116, 144, 0.5);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-quote-mark { color: var(--sc-secondary); }
    .sc-site[data-sc-template="coastal-fresh"] .sc-avatar {
        border: 3px solid color-mix(in srgb, var(--sc-secondary) 55%, transparent);
        box-shadow: var(--sc-shadow);
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-date-chip {
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-secondary) 12%, transparent);
        color: var(--sc-primary);
        font-weight: 700;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-footer {
        background: linear-gradient(180deg, #0e4b5c, #0a3646);
        color: #d7ecee;
    }
    .sc-site[data-sc-template="coastal-fresh"] .sc-footer h4 { color: #7dd3fc; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-footer-about,
    .sc-site[data-sc-template="coastal-fresh"] .sc-footer-bottom,
    .sc-site[data-sc-template="coastal-fresh"] .sc-footer-links a { color: rgba(215, 236, 238, 0.7); }
    .sc-site[data-sc-template="coastal-fresh"] .sc-footer-links a:hover { color: #7dd3fc; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-social a { border-color: rgba(215, 236, 238, 0.24); background: transparent; color: #d7ecee; }
    .sc-site[data-sc-template="coastal-fresh"] .sc-social a:hover { background: var(--sc-accent); border-color: var(--sc-accent); }
    .sc-site[data-sc-template="coastal-fresh"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.12);
    }

    /* ── 29. Playful Garden — coral / teal / honey, bubbly junior-school ──
       Cheerful primary-school warmth. Oversized rounded shapes, gradient
       blobs, bouncy hover lift and honey-comb accents. Friendly enough to
       belong in a picture book. */
    .sc-site[data-sc-template="playful-garden"] {
        --sc-radius: 28px;
        --sc-radius-btn: 9999px;
        --sc-border: rgba(244, 63, 94, 0.16);
        --sc-shadow: 0 16px 36px -18px rgba(244, 63, 94, 0.26);
        --sc-shadow-lg: 0 30px 64px -26px rgba(244, 63, 94, 0.32);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-main {
        background:
            radial-gradient(60% 40% at 8% 6%, color-mix(in srgb, var(--sc-accent) 12%, transparent), transparent 55%),
            radial-gradient(55% 45% at 96% 20%, color-mix(in srgb, var(--sc-secondary) 10%, transparent), transparent 55%),
            radial-gradient(70% 55% at 50% 100%, color-mix(in srgb, var(--sc-primary) 7%, transparent), transparent 55%),
            var(--sc-bg);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-nav {
        background: rgba(255, 254, 249, 0.88);
        -webkit-backdrop-filter: blur(12px) saturate(150%);
        backdrop-filter: blur(12px) saturate(150%);
        border-bottom: 2px solid color-mix(in srgb, var(--sc-primary) 16%, transparent);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-brand-name {
        font-family: var(--sc-font-display);
        font-weight: 700;
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-nav-link {
        font-weight: 800;
        color: color-mix(in srgb, var(--sc-text) 84%, transparent);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-nav-link:hover {
        color: var(--sc-primary);
        background: color-mix(in srgb, var(--sc-primary) 10%, transparent);
        border-radius: 999px;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-eyebrow {
        padding: 0.4rem 1.1rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-primary) 12%, transparent);
        color: var(--sc-primary);
        letter-spacing: 0.1em;
        font-weight: 800;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-eyebrow::before { display: none; }
    .sc-site[data-sc-template="playful-garden"] .sc-eyebrow::after { content: '●'; margin-left: 0.45rem; font-size: 0.6rem; color: var(--sc-accent); }
    .sc-site[data-sc-template="playful-garden"] .sc-section-head.is-center .sc-eyebrow { margin-inline: auto; }
    .sc-site[data-sc-template="playful-garden"] .sc-section-title {
        font-family: var(--sc-font-display);
        font-weight: 700;
        letter-spacing: -0.01em;
        line-height: 1.06;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-section-title::after {
        content: '';
        display: block;
        width: 4.4rem;
        height: 0.7rem;
        margin-top: 1rem;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--sc-primary), var(--sc-accent), var(--sc-secondary));
    }
    .sc-site[data-sc-template="playful-garden"] .sc-section-head.is-center .sc-section-title::after { margin-inline: auto; }
    .sc-site[data-sc-template="playful-garden"] .sc-btn-primary {
        background: linear-gradient(135deg, var(--sc-primary), #fb923c);
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-primary) 65%, transparent);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-btn-accent {
        background: linear-gradient(135deg, var(--sc-secondary), var(--sc-accent));
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-secondary) 60%, transparent);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-btn-ghost {
        border-width: 2px;
        border-color: var(--sc-secondary);
        color: var(--sc-primary);
        border-radius: 9999px;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-card,
    .sc-site[data-sc-template="playful-garden"] .sc-tile,
    .sc-site[data-sc-template="playful-garden"] .sc-faq-item {
        border-radius: 30px;
        border: 1px solid color-mix(in srgb, var(--sc-primary) 16%, transparent);
        box-shadow: var(--sc-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-card:hover,
    .sc-site[data-sc-template="playful-garden"] .sc-tile:hover {
        transform: translateY(-6px) rotate(-0.6deg);
        box-shadow: var(--sc-shadow-lg);
        border-color: color-mix(in srgb, var(--sc-primary) 36%, transparent);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-tile::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 30px;
        background: linear-gradient(135deg, color-mix(in srgb, var(--sc-primary) 10%, transparent), transparent 45%, color-mix(in srgb, var(--sc-secondary) 10%, transparent));
        pointer-events: none;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-hero-frame,
    .sc-site[data-sc-template="playful-garden"] .sc-media {
        border-radius: 36px 36px 22px 22px;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-hero-frame {
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-hero-frame-ring {
        border-style: solid;
        border-color: color-mix(in srgb, var(--sc-accent) 50%, transparent);
        border-radius: 42px 42px 28px 28px;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-stat-num {
        color: var(--sc-primary);
        font-weight: 800;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-stat-label {
        color: color-mix(in srgb, var(--sc-primary) 82%, transparent);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-tile-icon {
        border-radius: 50%;
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-accent));
        color: #fff;
        box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--sc-primary) 60%, transparent);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-tile-media { border-radius: 26px; overflow: hidden; }
    .sc-site[data-sc-template="playful-garden"] .sc-tile-media img { transition: transform 0.5s ease; }
    .sc-site[data-sc-template="playful-garden"] .sc-tile:hover .sc-tile-media img { transform: scale(1.08); }
    .sc-site[data-sc-template="playful-garden"] .sc-cta-band {
        background:
            radial-gradient(70% 140% at 12% 0%, color-mix(in srgb, var(--sc-accent) 40%, transparent), transparent 60%),
            radial-gradient(70% 140% at 90% 100%, color-mix(in srgb, var(--sc-secondary) 42%, transparent), transparent 60%),
            linear-gradient(120deg, #8f2140, #d9365a 50%, #0e8574);
        border-radius: 34px;
        box-shadow: 0 30px 70px -24px rgba(244, 63, 94, 0.45);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-cta-band .sc-btn-light {
        background: var(--sc-accent);
        color: #6b2d1a;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-quote-mark { color: var(--sc-secondary); }
    .sc-site[data-sc-template="playful-garden"] .sc-avatar {
        border: 4px solid color-mix(in srgb, var(--sc-accent) 60%, transparent);
        box-shadow: var(--sc-shadow);
    }
    .sc-site[data-sc-template="playful-garden"] .sc-date-chip {
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-primary) 12%, transparent);
        color: var(--sc-primary);
        font-weight: 800;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-footer {
        background: linear-gradient(180deg, #8f2140, #701a33);
        color: #ffe9ef;
    }
    .sc-site[data-sc-template="playful-garden"] .sc-footer h4 { color: #fcd34d; }
    .sc-site[data-sc-template="playful-garden"] .sc-footer-about,
    .sc-site[data-sc-template="playful-garden"] .sc-footer-bottom,
    .sc-site[data-sc-template="playful-garden"] .sc-footer-links a { color: rgba(255, 233, 239, 0.72); }
    .sc-site[data-sc-template="playful-garden"] .sc-footer-links a:hover { color: #fcd34d; }
    .sc-site[data-sc-template="playful-garden"] .sc-social a { border-color: rgba(255, 233, 239, 0.24); background: transparent; color: #ffe9ef; }
    .sc-site[data-sc-template="playful-garden"] .sc-social a:hover { background: var(--sc-secondary); border-color: var(--sc-secondary); }
    .sc-site[data-sc-template="playful-garden"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.14);
    }

    /* ── 30. Emerald Heritage — forest & antique gold, prestige ──
       A grand old forest campus dressed in deep green and gilt. Serif
       display type, sharp corners, hairline gold rules and restrained
       shadows. The prospectus of a distinguished institution. */
    .sc-site[data-sc-template="emerald-heritage"] {
        --sc-radius: 4px;
        --sc-radius-btn: 0px;
        --sc-border: rgba(201, 162, 75, 0.4);
        --sc-shadow: 0 10px 26px -20px rgba(20, 83, 45, 0.35);
        --sc-shadow-lg: 0 24px 48px -28px rgba(20, 83, 45, 0.4);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-main {
        background:
            radial-gradient(70% 45% at 100% 0%, rgba(201, 162, 75, 0.07), transparent 55%),
            radial-gradient(60% 45% at 0% 100%, rgba(20, 83, 45, 0.05), transparent 55%),
            var(--sc-bg);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-nav {
        background: rgba(255, 255, 255, 0.9);
        border-bottom: 1px solid rgba(201, 162, 75, 0.55);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-brand-name {
        font-family: var(--sc-font-display);
        font-weight: 700;
        letter-spacing: 0.04em;
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-nav-link {
        font-weight: 600;
        letter-spacing: 0.04em;
        color: color-mix(in srgb, var(--sc-text) 82%, transparent);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0.3rem;
        height: 1px;
        background: var(--sc-accent);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-nav-link:hover::after,
    .sc-site[data-sc-template="emerald-heritage"] .sc-nav-link.is-active::after { transform: scaleX(1); }
    .sc-site[data-sc-template="emerald-heritage"] .sc-nav-link:hover { color: var(--sc-primary); }
    .sc-site[data-sc-template="emerald-heritage"] .sc-eyebrow {
        letter-spacing: 0.26em;
        text-transform: uppercase;
        color: var(--sc-accent);
        font-size: 0.66rem;
        font-weight: 600;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-eyebrow::before {
        width: 2.4rem;
        height: 1px;
        background: var(--sc-accent);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-section-head.is-center .sc-eyebrow::before { display: none; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-section-head.is-center .sc-eyebrow::after {
        content: '◆';
        margin-left: 0.6rem;
        font-size: 0.55rem;
        color: var(--sc-accent);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-section-title {
        font-family: var(--sc-font-display);
        font-weight: 600;
        letter-spacing: -0.01em;
        color: var(--sc-primary);
        line-height: 1.12;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-section-title::after {
        content: '';
        display: block;
        width: 4.5rem;
        height: 1px;
        margin-top: 1.1rem;
        background: linear-gradient(90deg, var(--sc-accent), transparent);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-section-title::after { position: relative; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-section-head.is-center .sc-section-title::after {
        margin-inline: auto;
        background: linear-gradient(90deg, transparent, var(--sc-accent), transparent);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-btn-primary {
        background: linear-gradient(135deg, var(--sc-primary), color-mix(in srgb, var(--sc-primary) 72%, #000));
        box-shadow: 0 14px 30px -14px rgba(20, 83, 45, 0.5);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-btn-accent {
        background: linear-gradient(135deg, var(--sc-accent), #a67c1e);
        color: #1f2d24;
        box-shadow: 0 14px 30px -14px rgba(201, 162, 75, 0.55);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-btn-ghost {
        border-color: var(--sc-accent);
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-card,
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile,
    .sc-site[data-sc-template="emerald-heritage"] .sc-faq-item {
        border-radius: 4px;
        border: 1px solid rgba(201, 162, 75, 0.35);
        box-shadow: var(--sc-shadow);
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-card:hover,
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile:hover {
        transform: translateY(-3px);
        box-shadow: var(--sc-shadow-lg);
        border-color: var(--sc-accent);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--sc-accent), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile:hover::before { opacity: 1; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-hero-frame,
    .sc-site[data-sc-template="emerald-heritage"] .sc-media {
        border-radius: 4px;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-hero-frame::after {
        content: '';
        position: absolute;
        inset: 1rem;
        border: 1px solid rgba(201, 162, 75, 0.55);
        pointer-events: none;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-hero-frame-ring { display: none; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-hero-title {
        font-family: var(--sc-font-display);
        font-weight: 600;
        letter-spacing: -0.01em;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-stat-num {
        color: var(--sc-accent);
        font-weight: 600;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-stat-label {
        color: var(--sc-primary);
        letter-spacing: 0.12em;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile-icon {
        border-radius: 4px;
        background: var(--sc-primary);
        color: #f3ecd8;
        border: 1px solid rgba(201, 162, 75, 0.6);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile-media { border-radius: 4px; overflow: hidden; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile-media img { transition: transform 0.6s ease; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-tile:hover .sc-tile-media img { transform: scale(1.05); }
    .sc-site[data-sc-template="emerald-heritage"] .sc-cta-band {
        background:
            radial-gradient(80% 150% at 20% 0%, rgba(201, 162, 75, 0.2), transparent 60%),
            linear-gradient(120deg, #0c3320, var(--sc-primary) 60%, #0a2a1a);
        border-radius: 4px;
        border: 1px solid rgba(201, 162, 75, 0.4);
        box-shadow: 0 30px 70px -26px rgba(20, 83, 45, 0.6);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-cta-band .sc-btn-light {
        background: linear-gradient(135deg, var(--sc-accent), #a67c1e);
        color: #1f2d24;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-quote-mark { color: var(--sc-accent); }
    .sc-site[data-sc-template="emerald-heritage"] .sc-avatar {
        border: 2px solid var(--sc-accent);
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-date-chip {
        border-radius: 2px;
        background: color-mix(in srgb, var(--sc-primary) 8%, transparent);
        color: var(--sc-primary);
        font-weight: 600;
        letter-spacing: 0.06em;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-footer {
        background: linear-gradient(180deg, #0c3320, #082116);
        border-top: 2px solid rgba(201, 162, 75, 0.6);
        color: #e9e2cf;
    }
    .sc-site[data-sc-template="emerald-heritage"] .sc-footer h4 { color: #d9b96a; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-footer-about,
    .sc-site[data-sc-template="emerald-heritage"] .sc-footer-bottom,
    .sc-site[data-sc-template="emerald-heritage"] .sc-footer-links a { color: rgba(233, 226, 207, 0.7); }
    .sc-site[data-sc-template="emerald-heritage"] .sc-footer-links a:hover { color: #d9b96a; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-social a { border-color: rgba(217, 185, 106, 0.35); background: transparent; color: #e9e2cf; }
    .sc-site[data-sc-template="emerald-heritage"] .sc-social a:hover { background: var(--sc-accent); border-color: var(--sc-accent); }
    .sc-site[data-sc-template="emerald-heritage"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ── 31. Neon Frontier — indigo night, electric cyan & lime ──
       A STEM academy after dark. Deep indigo canvas, glassmorphism
       surfaces, mono kickers and neon glows. Everything is light-on-dark:
       stats, section titles and footers all stay readable. */
    .sc-site[data-sc-template="neon-frontier"] {
        --sc-radius: 14px;
        --sc-radius-btn: 9999px;
        --sc-border: rgba(34, 211, 238, 0.18);
        --sc-shadow: 0 18px 42px -20px rgba(0, 0, 0, 0.7);
        --sc-shadow-lg: 0 32px 64px -24px rgba(0, 0, 0, 0.8);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-main {
        background:
            radial-gradient(90% 60% at 12% 0%, color-mix(in srgb, var(--sc-primary) 60%, transparent), transparent 55%),
            radial-gradient(70% 55% at 92% 22%, color-mix(in srgb, var(--sc-secondary) 34%, transparent), transparent 50%),
            radial-gradient(80% 60% at 50% 100%, color-mix(in srgb, var(--sc-accent) 20%, transparent), transparent 55%),
            #0b1021;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-main::after {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 60;
        opacity: 0.35;
        background-image:
            linear-gradient(rgba(34, 211, 238, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(34, 211, 238, 0.05) 1px, transparent 1px);
        background-size: 56px 56px;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-nav {
        background: color-mix(in srgb, #111735 82%, transparent);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        border-bottom: 1px solid color-mix(in srgb, var(--sc-secondary) 30%, transparent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-nav::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--sc-secondary), transparent);
        opacity: 0.7;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-brand-name {
        background: linear-gradient(100deg, var(--sc-secondary), var(--sc-accent));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 800;
        letter-spacing: 0.04em;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-nav-link {
        color: rgba(255, 255, 255, 0.82);
        font-weight: 600;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-nav-link:hover {
        color: var(--sc-secondary);
        text-shadow: 0 0 18px color-mix(in srgb, var(--sc-secondary) 65%, transparent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-eyebrow {
        font-family: var(--sc-font-display);
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--sc-secondary);
        font-size: 0.66rem;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-eyebrow::before {
        content: '//';
        width: auto;
        height: auto;
        border-radius: 0;
        background: none;
        color: var(--sc-accent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-section-head.is-center .sc-eyebrow::before {
        content: '//';
        width: auto;
        height: auto;
        background: none;
        color: var(--sc-accent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-section-title {
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.08;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-section-title::after {
        content: '';
        display: block;
        width: 4.5rem;
        height: 2px;
        margin-top: 1.1rem;
        background: linear-gradient(90deg, var(--sc-secondary), var(--sc-accent));
        box-shadow: 0 0 14px color-mix(in srgb, var(--sc-secondary) 60%, transparent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-section-head.is-center .sc-section-title::after { margin-inline: auto; }
    .sc-site[data-sc-template="neon-frontier"] .sc-section-lead {
        color: rgba(255, 255, 255, 0.68);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-btn-primary {
        background: linear-gradient(100deg, color-mix(in srgb, var(--sc-secondary) 35%, var(--sc-primary)), var(--sc-primary));
        box-shadow: 0 14px 34px -12px color-mix(in srgb, var(--sc-secondary) 70%, transparent), 0 0 0 1px rgba(255, 255, 255, 0.14) inset;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-btn-accent {
        background: linear-gradient(100deg, var(--sc-secondary), var(--sc-accent));
        color: #0b1021;
        box-shadow: 0 14px 34px -12px color-mix(in srgb, var(--sc-accent) 65%, transparent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-btn-ghost {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(34, 211, 238, 0.45);
        color: rgba(255, 255, 255, 0.88);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-card,
    .sc-site[data-sc-template="neon-frontier"] .sc-tile,
    .sc-site[data-sc-template="neon-frontier"] .sc-faq-item,
    .sc-site[data-sc-template="neon-frontier"] .sc-hero-frame,
    .sc-site[data-sc-template="neon-frontier"] .sc-media,
    .sc-site[data-sc-template="neon-frontier"] .sc-stat {
        background: color-mix(in srgb, #151d38 82%, transparent);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 28%, transparent);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-card:hover,
    .sc-site[data-sc-template="neon-frontier"] .sc-tile:hover {
        border-color: color-mix(in srgb, var(--sc-secondary) 60%, transparent);
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--sc-secondary) 30%, transparent), 0 22px 48px -18px rgba(0, 0, 0, 0.8);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-tile-icon {
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-secondary));
        color: #fff;
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 40%, transparent);
        box-shadow: 0 0 22px color-mix(in srgb, var(--sc-secondary) 26%, transparent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-tile-media img {
        transition: transform 0.6s ease, opacity 0.4s ease;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-tile:hover .sc-tile-media img { transform: scale(1.06); }
    .sc-site[data-sc-template="neon-frontier"] .sc-tile-media::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(11, 16, 33, 0.72), transparent 55%);
        pointer-events: none;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-hero-frame-ring {
        border-style: solid;
        border-color: color-mix(in srgb, var(--sc-secondary) 40%, transparent);
        box-shadow: 0 0 30px color-mix(in srgb, var(--sc-secondary) 22%, transparent) inset;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-hero-float {
        background: color-mix(in srgb, #151d38 84%, transparent);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-stat-num {
        background: linear-gradient(110deg, var(--sc-secondary), var(--sc-accent));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 800;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-stat-label {
        color: rgba(255, 255, 255, 0.62);
        letter-spacing: 0.1em;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-quote-mark { color: var(--sc-secondary); }
    .sc-site[data-sc-template="neon-frontier"] .sc-quote-body {
        color: rgba(255, 255, 255, 0.88);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-cta-band {
        background:
            radial-gradient(80% 140% at 18% 0%, color-mix(in srgb, var(--sc-primary) 70%, transparent), transparent),
            radial-gradient(70% 120% at 90% 100%, color-mix(in srgb, var(--sc-accent) 34%, transparent), transparent),
            linear-gradient(120deg, #0b1021, #1a2350 55%, #0b1021);
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 35%, transparent);
        box-shadow: 0 0 60px color-mix(in srgb, var(--sc-secondary) 18%, transparent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-cta-band .sc-btn-light {
        background: linear-gradient(100deg, var(--sc-secondary), var(--sc-accent));
        color: #0b1021;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-avatar {
        border: 2px solid var(--sc-secondary);
        box-shadow: 0 0 18px color-mix(in srgb, var(--sc-secondary) 40%, transparent);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-date-chip {
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-secondary) 14%, transparent);
        color: var(--sc-secondary);
        font-weight: 700;
        letter-spacing: 0.08em;
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-footer {
        background: color-mix(in srgb, #0b1021 94%, transparent);
        border-top: 1px solid color-mix(in srgb, var(--sc-secondary) 28%, transparent);
        color: rgba(255, 255, 255, 0.72);
    }
    .sc-site[data-sc-template="neon-frontier"] .sc-footer h4 { color: var(--sc-secondary); }
    .sc-site[data-sc-template="neon-frontier"] .sc-footer-about,
    .sc-site[data-sc-template="neon-frontier"] .sc-footer-bottom,
    .sc-site[data-sc-template="neon-frontier"] .sc-footer-links a { color: rgba(255, 255, 255, 0.56); }
    .sc-site[data-sc-template="neon-frontier"] .sc-footer-links a:hover { color: var(--sc-secondary); }
    .sc-site[data-sc-template="neon-frontier"] .sc-social a { border-color: rgba(34, 211, 238, 0.3); background: transparent; color: rgba(255, 255, 255, 0.8); }
    .sc-site[data-sc-template="neon-frontier"] .sc-social a:hover { background: var(--sc-accent); border-color: var(--sc-accent); }
    .sc-site[data-sc-template="neon-frontier"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ── 32. Sunset International — teal & terracotta, global warmth ──
       An international school's sun-warmed palette. Deep teal anchoring
       terracotta-and-amber sunsets, Playfair serif headlines, rounded
       surfaces and an accreditation-friendly, photo-led layout. */
    .sc-site[data-sc-template="sunset-international"] {
        --sc-radius: 20px;
        --sc-radius-btn: 9999px;
        --sc-border: rgba(15, 118, 110, 0.18);
        --sc-shadow: 0 14px 34px -18px rgba(15, 118, 110, 0.24);
        --sc-shadow-lg: 0 28px 60px -26px rgba(15, 118, 110, 0.3);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-main {
        background:
            radial-gradient(75% 50% at 100% 0%, color-mix(in srgb, var(--sc-secondary) 9%, transparent), transparent 60%),
            radial-gradient(65% 50% at 0% 90%, color-mix(in srgb, var(--sc-accent) 8%, transparent), transparent 55%),
            var(--sc-bg);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-nav {
        background: rgba(255, 255, 255, 0.84);
        -webkit-backdrop-filter: blur(12px) saturate(150%);
        backdrop-filter: blur(12px) saturate(150%);
        border-bottom: 1px solid color-mix(in srgb, var(--sc-secondary) 26%, transparent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-brand-name {
        font-family: var(--sc-font-display);
        font-weight: 700;
        letter-spacing: 0.01em;
        color: var(--sc-primary);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-nav-link {
        font-weight: 600;
        color: color-mix(in srgb, var(--sc-text) 82%, transparent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-nav-link:hover {
        color: var(--sc-secondary);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-eyebrow {
        padding: 0.35rem 1rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-secondary) 14%, transparent);
        color: var(--sc-secondary);
        letter-spacing: 0.12em;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-eyebrow::before { display: none; }
    .sc-site[data-sc-template="sunset-international"] .sc-eyebrow::after {
        content: '✦';
        margin-left: 0.45rem;
        font-size: 0.7rem;
        color: var(--sc-accent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-section-head.is-center .sc-eyebrow { margin-inline: auto; }
    .sc-site[data-sc-template="sunset-international"] .sc-section-title {
        font-family: var(--sc-font-display);
        font-weight: 600;
        letter-spacing: -0.01em;
        color: var(--sc-primary);
        line-height: 1.1;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-section-title::after {
        content: '';
        display: block;
        width: 4.2rem;
        height: 0.32rem;
        margin-top: 1rem;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--sc-secondary), var(--sc-accent));
    }
    .sc-site[data-sc-template="sunset-international"] .sc-section-head.is-center .sc-section-title::after { margin-inline: auto; }
    .sc-site[data-sc-template="sunset-international"] .sc-btn-primary {
        background: linear-gradient(135deg, var(--sc-primary), color-mix(in srgb, var(--sc-primary) 62%, #000));
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-primary) 55%, transparent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-btn-accent {
        background: linear-gradient(135deg, var(--sc-secondary), var(--sc-accent));
        box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--sc-accent) 55%, transparent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-btn-ghost {
        border-color: var(--sc-secondary);
        color: var(--sc-primary);
        border-radius: 9999px;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-card,
    .sc-site[data-sc-template="sunset-international"] .sc-tile,
    .sc-site[data-sc-template="sunset-international"] .sc-faq-item {
        border-radius: 22px;
        border: 1px solid color-mix(in srgb, var(--sc-secondary) 18%, transparent);
        box-shadow: var(--sc-shadow);
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-card:hover,
    .sc-site[data-sc-template="sunset-international"] .sc-tile:hover {
        transform: translateY(-4px);
        box-shadow: var(--sc-shadow-lg);
        border-color: color-mix(in srgb, var(--sc-secondary) 40%, transparent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-hero-frame,
    .sc-site[data-sc-template="sunset-international"] .sc-media {
        border-radius: 26px;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-hero-frame {
        box-shadow: var(--sc-shadow-lg);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-hero-frame-ring {
        border-style: dashed;
        border-color: color-mix(in srgb, var(--sc-accent) 50%, transparent);
        border-radius: 32px;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-stat-num {
        color: var(--sc-primary);
        font-weight: 800;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-stat-label {
        color: color-mix(in srgb, var(--sc-secondary) 90%, transparent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-tile-icon {
        border-radius: 50%;
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-secondary));
        color: #fff;
        box-shadow: 0 10px 24px -10px color-mix(in srgb, var(--sc-primary) 50%, transparent);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-tile-media { border-radius: 20px; overflow: hidden; }
    .sc-site[data-sc-template="sunset-international"] .sc-tile-media img { transition: transform 0.5s ease; }
    .sc-site[data-sc-template="sunset-international"] .sc-tile:hover .sc-tile-media img { transform: scale(1.07); }
    .sc-site[data-sc-template="sunset-international"] .sc-cta-band {
        background:
            radial-gradient(80% 150% at 16% 0%, color-mix(in srgb, var(--sc-accent) 40%, transparent), transparent 60%),
            radial-gradient(70% 140% at 90% 100%, color-mix(in srgb, var(--sc-primary) 55%, transparent), transparent 55%),
            linear-gradient(120deg, #0b3b37, #0f6b60 60%, #09322f);
        border-radius: 28px;
        box-shadow: 0 30px 70px -24px rgba(15, 118, 110, 0.5);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-cta-band .sc-btn-light {
        background: linear-gradient(135deg, var(--sc-secondary), var(--sc-accent));
        color: #fff;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-quote-mark { color: var(--sc-secondary); }
    .sc-site[data-sc-template="sunset-international"] .sc-avatar {
        border: 3px solid color-mix(in srgb, var(--sc-accent) 55%, transparent);
        box-shadow: var(--sc-shadow);
    }
    .sc-site[data-sc-template="sunset-international"] .sc-date-chip {
        border-radius: 999px;
        background: color-mix(in srgb, var(--sc-secondary) 12%, transparent);
        color: var(--sc-primary);
        font-weight: 700;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-footer {
        background: linear-gradient(180deg, #0f4e47, #09332f);
        color: #dff0ec;
    }
    .sc-site[data-sc-template="sunset-international"] .sc-footer h4 { color: #f2b04c; }
    .sc-site[data-sc-template="sunset-international"] .sc-footer-about,
    .sc-site[data-sc-template="sunset-international"] .sc-footer-bottom,
    .sc-site[data-sc-template="sunset-international"] .sc-footer-links a { color: rgba(223, 240, 236, 0.7); }
    .sc-site[data-sc-template="sunset-international"] .sc-footer-links a:hover { color: #f2b04c; }
    .sc-site[data-sc-template="sunset-international"] .sc-social a { border-color: rgba(223, 240, 236, 0.24); background: transparent; color: #dff0ec; }
    .sc-site[data-sc-template="sunset-international"] .sc-social a:hover { background: var(--sc-accent); border-color: var(--sc-accent); }
    .sc-site[data-sc-template="sunset-international"] .sc-footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.12);
    }
</style>
