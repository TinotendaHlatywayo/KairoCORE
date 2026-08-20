<style>
    .wcm-shell {
        --sc-ink: #546db8;
        --sc-ink-soft: #6679e3;
        --sc-canvas: #eef0f7;
        --sc-panel: #ffffff;
        --sc-border: #dfe3ef;
        --sc-primary: #5b4fe9;
        --sc-primary-dark: #4636c9;
        --sc-primary-light: #efedff;
        --sc-accent: #22d3ee;
        --sc-success: #0ea768;
        --sc-success-dark: #087a4c;
        --sc-danger: #f0355c;
        --sc-danger-dark: #c81e46;
        --sc-warning: #e9a13a;
        --sc-text: #0d1220;
        --sc-text-muted: #5c6478;
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        background: var(--sc-canvas);
        color: var(--sc-text);
    }

    .wcm-shell h1, .wcm-shell h2, .wcm-shell h3, .wcm-shell h4,
    .wcm-shell .font-black, .wcm-shell .font-bold,
    .wcm-shell button, .wcm-shell select, .wcm-shell label {
        font-family: 'Sora', 'Inter', ui-sans-serif, sans-serif;
    }

    .wcm-shell :is(button, a, select, input, textarea) { min-height: 44px; }

    .wcm-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem;
        border-radius: 0.75rem; padding: 0.375rem 0.875rem; font-size: 0.75rem; font-weight: 700;
        transition: all 0.15s ease; cursor: pointer; white-space: nowrap; line-height: 1.25rem;
        min-height: 36px;
    }
    .wcm-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .wcm-btn-primary { background: var(--sc-primary); color: #fff; box-shadow: 0 6px 14px rgba(91,79,233,0.35); }
    .wcm-btn-primary:hover { background: var(--sc-primary-dark); transform: translateY(-1px); }
    .wcm-btn-success { background: var(--sc-success); color: #fff; box-shadow: 0 6px 14px rgba(14,167,104,0.35); }
    .wcm-btn-success:hover { background: var(--sc-success-dark); transform: translateY(-1px); }
    .wcm-btn-secondary { background: #fff; color: var(--sc-text); border: 1px solid var(--sc-border); }
    .wcm-btn-secondary:hover { border-color: var(--sc-primary); color: var(--sc-primary); }
    .wcm-btn-danger { background: #fff; color: var(--sc-danger); border: 1px solid #fecdd3; }
    .wcm-btn-danger:hover { background: var(--sc-danger); color: #fff; border-color: var(--sc-danger); }
    .wcm-btn-ghost { background: transparent; color: var(--sc-text-muted); }
    .wcm-btn-ghost:hover { background: var(--sc-primary-light); color: var(--sc-primary); }

    .wcm-input, .wcm-textarea {
        width: 100%; border-radius: 0.625rem; border: 1px solid var(--sc-border);
        background: #fff; padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: var(--sc-text);
        outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .wcm-input:focus, .wcm-textarea:focus {
        border-color: var(--sc-primary); box-shadow: 0 0 0 3px rgba(91,79,233,0.15);
    }

    .wcm-card {
        background: var(--sc-panel); border: 1px solid var(--sc-border); border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }

    .wcm-pill {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.03em;
    }
    .wcm-pill-live { background: #d1fae5; color: #087a4c; }
    .wcm-pill-draft { background: #fef3c7; color: #b45309; }

    .wcm-shell .cms-card {
        border-radius: var(--theme-radius, 20px);
        box-shadow: var(--theme-shadow, 0 10px 30px -5px rgba(15,23,42,0.08));
    }
    .wcm-shell .cms-btn {
        border-radius: var(--theme-btn-radius, var(--theme-radius, 20px));
    }

    /* ═══════════════════════════════════════════════════════════════
       CMS workspace (Content Manager) — self-contained component styles.
       Defined as real CSS (not Tailwind utilities) so every class renders
       regardless of which utilities Filament's compiled stylesheet ships.
       ═══════════════════════════════════════════════════════════════ */

    /* Three-pane workspace: pages | content | live preview */
    .wcm-cms-grid {
        display: grid;
        grid-template-columns: 220px minmax(0, 1fr) minmax(0, 1fr);
        flex: 1 1 auto;
        min-height: 0;
    }
    @media (max-width: 1023px) {
        .wcm-cms-grid { grid-template-columns: 1fr; }
    }

    .wcm-pane { border-color: var(--sc-border); }
    .wcm-divider-r { border-right: 1px solid var(--sc-border); }
    .wcm-divider-l { border-left: 1px solid var(--sc-border); }
    .wcm-canvas { background: var(--sc-canvas); }
    .wcm-subtle { background: var(--sc-canvas); }
    .wcm-scroll { overflow-y: auto; max-height: calc(100vh - 120px); }

    /* Text roles */
    .wcm-heading { color: var(--sc-text); font-weight: 800; }
    .wcm-muted { color: var(--sc-text-muted); }
    .wcm-label { color: var(--sc-text-muted); font-weight: 700; font-size: 0.75rem; }
    .wcm-eyebrow {
        color: var(--sc-text-muted); font-size: 0.625rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.04em;
    }
    .wcm-tiny { font-size: 0.6875rem; }
    .wcm-accent { color: var(--sc-primary); }

    /* Selectable nav items (pages rail / sections picker) */
    .wcm-nav-item {
        position: relative;
        display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
        width: 100%; padding: 0.625rem 0.75rem; border-radius: 0.75rem;
        text-align: left; font-size: 0.875rem; font-weight: 700; line-height: 1.25rem;
        color: var(--sc-text); background: transparent; border: 1px solid transparent;
        cursor: pointer; transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        min-height: 44px;
    }
    .wcm-nav-item:hover { background: var(--sc-primary-light); }
    .wcm-nav-main { flex: 1 1 auto; min-width: 0; }
    .wcm-nav-title { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wcm-meta {
        display: block; color: var(--sc-text-muted); font-size: 0.625rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap;
    }
    .wcm-nav-item.is-active {
        background: linear-gradient(135deg, var(--sc-primary), var(--sc-primary-dark));
        color: #fff; border-color: transparent; font-weight: 800;
        box-shadow: inset 3px 0 0 #fff, 0 8px 18px -8px rgba(91, 79, 233, 0.6);
    }
    .wcm-nav-item.is-active .wcm-meta { color: rgba(255, 255, 255, 0.85); }

    /* Toggle chips (preview controls) */
    .wcm-chip {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem;
        padding: 0.375rem 0.75rem; border-radius: 0.625rem;
        font-size: 0.6875rem; font-weight: 800; min-height: 30px;
        color: var(--sc-text-muted); background: transparent; border: 1px solid transparent;
        cursor: pointer; transition: all 0.15s ease; white-space: nowrap;
    }
    .wcm-chip:hover { background: var(--sc-primary-light); color: var(--sc-primary); }
    .wcm-chip.is-active {
        background: var(--sc-primary); color: #fff;
        box-shadow: 0 6px 14px -6px rgba(91, 79, 233, 0.5);
    }

    /* Preview device widths */
    .wcm-preview-full { width: 100%; }
    .wcm-preview-tablet { width: 768px; max-width: 100%; }
    .wcm-preview-mobile { width: 375px; max-width: 100%; }
    @media (max-width: 767px) {
        .wcm-preview-tablet, .wcm-preview-mobile { width: 100%; }
    }
    .wcm-preview-stage { background: var(--sc-canvas); }

    /* Simulated browser frame for the live preview */
    .wcm-device {
        border-radius: 1rem; overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 25px 50px -12px rgba(2, 6, 23, 0.25);
        background: #fff;
    }

    /* Large empty-state block */
    .wcm-empty { padding: 2.5rem 1.5rem; }
    .wcm-empty-icon { font-size: 2.5rem; line-height: 1; margin-bottom: 0.75rem; display: block; }
</style>
