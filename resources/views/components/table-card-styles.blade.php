{{-- SchoolCore shared table-card styles.
     Include this inside any card-based table content view so the
     card look stays consistent across the whole system (rounded corners,
     soft drop-shadow, light-teal active-indicator edge border and a top
     accent bar driven by --sc-card-color). Design tokens inherit the app
     theme (--theme-primary / --theme-accent / --theme-radius). --}}

<style>
    :root {
        --sc-teal: #14b8a6;
        --sc-teal-soft: rgba(20, 184, 166, 0.22);
    }

    .sc-card-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    @media (min-width: 640px) {
        .sc-card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .sc-card-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 1536px) {
        .sc-card-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .sc-card {
        position: relative;
        display: flex;
        flex-direction: column;
        border-radius: var(--theme-radius, 1rem);
        background: linear-gradient(160deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid var(--sc-teal-soft);
        box-shadow:
            0 10px 34px -16px var(--sc-card-color, var(--sc-teal)),
            0 1px 3px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .sc-card:hover {
        transform: translateY(-5px);
        border-color: var(--sc-teal);
        box-shadow:
            0 0 0 1.5px var(--sc-teal-soft),
            0 0 26px -4px var(--sc-card-color, var(--sc-teal)),
            0 24px 50px -18px var(--sc-card-color, var(--sc-teal));
    }

    .sc-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 5px;
        background: linear-gradient(90deg, var(--sc-card-color, var(--sc-teal)), var(--theme-accent, #2dd4bf));
    }

    .sc-card-avatar {
        margin: 18px auto 6px auto;
        width: 92px;
        height: 92px;
        border-radius: 9999px;
        padding: 3px;
        background: conic-gradient(from 180deg, var(--sc-card-color, var(--sc-teal)), var(--theme-accent, #2dd4bf), var(--sc-card-color, var(--sc-teal)));
        box-shadow: 0 8px 22px -10px var(--sc-card-color, var(--sc-teal));
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sc-card-avatar .sc-avatar-inner {
        width: 100%;
        height: 100%;
        border-radius: 9999px;
        border: 3px solid #ffffff;
        background: #eef2ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: 0.02em;
    }

    .sc-card-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 9999px;
        object-fit: cover;
        border: 3px solid #ffffff;
        background: #f1f5f9;
    }

    .sc-card-name {
        font-weight: 800;
        font-size: 1.05rem;
        color: #0f172a;
        text-align: center;
        padding: 0 12px;
        line-height: 1.25;
    }

    .sc-card-sub {
        font-size: 0.72rem;
        font-weight: 600;
        color: #64748b;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-top: 3px;
    }

    .sc-card-select {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 5;
        display: flex;
        align-items: center;
    }

    .sc-card-select input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--sc-teal);
        cursor: pointer;
    }

    .sc-gender-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 2px 9px;
        border-radius: 9999px;
        line-height: 1.5;
        vertical-align: middle;
    }

    .sc-gender-pill svg {
        width: 0.8rem;
        height: 0.8rem;
    }

    .sc-gender-male {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .sc-gender-female {
        background: #fce7f3;
        color: #be185d;
    }

    .sc-gender-other {
        background: #e2e8f0;
        color: #475569;
    }

    .sc-card-status {
        position: absolute;
        top: 12px;
        right: 12px;
        font-size: 0.62rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #ffffff;
        background: var(--sc-card-color, var(--sc-teal));
        border-radius: 9999px;
        padding: 4px 10px;
        box-shadow: 0 4px 12px -4px var(--sc-card-color, var(--sc-teal));
    }

    .sc-card-meta {
        margin: 12px 16px 10px 16px;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 12px;
    }

    .sc-card-meta .sc-m-label {
        font-size: 0.62rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: block;
    }

    .sc-card-meta .sc-m-value {
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
        word-break: break-word;
    }

    .sc-card-actions {
        display: flex;
        gap: 8px;
        padding: 0 16px 16px 16px;
        margin-top: auto;
    }

    .sc-card-actions a,
    .sc-card-actions button {
        flex: 1;
        text-align: center;
        font-size: 0.74rem;
        font-weight: 700;
        border-radius: 10px;
        padding: 8px 10px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .sc-card-actions .sc-btn-edit {
        background: #eef2ff;
        color: #4f46e5;
    }
    .sc-card-actions .sc-btn-edit:hover {
        background: #4f46e5;
        color: #ffffff;
    }

    .sc-card-actions .sc-btn-view {
        background: #f8fafc;
        color: #334155;
    }
    .sc-card-actions .sc-btn-view:hover {
        background: #334155;
        color: #ffffff;
    }

    .sc-card-actions .sc-btn-docs {
        background: #e0f2fe;
        color: #0369a1;
    }
    .sc-card-actions .sc-btn-docs:hover {
        background: #0369a1;
        color: #ffffff;
    }

    .sc-card-actions .sc-btn-enroll {
        background: #ecfdf5;
        color: #059669;
    }
    .sc-card-actions .sc-btn-enroll:hover {
        background: #059669;
        color: #ffffff;
    }

    .dark .sc-card {
        background: linear-gradient(160deg, #1e293b 0%, #0f172a 100%);
        border-color: rgba(45, 212, 191, 0.18);
        box-shadow:
            0 10px 34px -16px var(--sc-card-color, var(--sc-teal)),
            0 1px 3px rgba(0, 0, 0, 0.35);
    }

    .dark .sc-card:hover {
        border-color: var(--sc-teal);
        box-shadow:
            0 0 0 1.5px rgba(45, 212, 191, 0.25),
            0 0 26px -4px var(--sc-card-color, var(--sc-teal)),
            0 24px 50px -18px var(--sc-card-color, var(--sc-teal));
    }

    .dark .sc-card-name {
        color: #f1f5f9;
    }
    .dark .sc-card-meta {
        border-top-color: #334155;
    }
    .dark .sc-card-meta .sc-m-value {
        color: #cbd5e1;
    }
    .dark .sc-card-avatar .sc-avatar-inner {
        border-color: #1e293b;
    }
</style>
