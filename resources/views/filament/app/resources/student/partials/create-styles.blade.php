<style>
    body[data-page="student-create"] {
        --std-brand: var(--sc-brand, var(--primary-600, #4f46e5));
        --std-brand-2: #06b6d4;
        --std-ok: #10b981;
        --std-warn: #f59e0b;
        --std-bad: #ef4444;
        --std-surface: #ffffff;
        --std-surface-2: #f0f3fa;
        --std-text: #141a33;
        --std-muted: #6a7392;
        --std-line: #e4e8f3;
        --std-shadow: 0 1px 2px rgba(20, 26, 51, .05), 0 8px 24px rgba(20, 26, 51, .06);
    }

    .dark body[data-page="student-create"] {
        --std-surface: #161a30;
        --std-surface-2: #1d2240;
        --std-text: #eef0fb;
        --std-muted: #99a1c4;
        --std-line: #272d50;
        --std-shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 8px 24px rgba(0, 0, 0, .35);
    }

    body[data-page="student-create"] .fi-fo-tabs > nav {
        position: sticky;
        top: 4.5rem;
        z-index: 25;
        margin-bottom: 4px;
        padding: 5px;
        border: 1px solid var(--std-line);
        border-radius: 14px;
        background: color-mix(in srgb, var(--std-surface) 92%, transparent);
        box-shadow: var(--std-shadow);
        backdrop-filter: blur(10px);
    }

    body[data-page="student-create"] .fi-fo-tabs > nav .fi-tabs-item {
        min-height: 38px;
        border-radius: 10px;
        font-weight: 700;
    }

    body[data-page="student-create"] .fi-fo-tabs > nav .fi-tabs-item.fi-active {
        color: #fff;
        background: linear-gradient(135deg, var(--std-brand), #6d5dfc);
        box-shadow: 0 8px 18px -10px var(--std-brand);
    }

    body[data-page="student-create"] .fi-fo-tabs > nav .fi-tabs-item.fi-active * {
        color: #fff !important;
    }

    body[data-page="student-create"] .fi-section {
        border: 1px solid var(--std-line);
        border-left: 3px solid var(--std-brand);
        border-radius: 14px;
        background: var(--std-surface);
        box-shadow: var(--std-shadow);
    }

    body[data-page="student-create"] .fi-section-header {
        gap: 4px;
    }

    body[data-page="student-create"] .fi-form-actions {
        z-index: 30;
    }

    body[data-page="student-create"] .fi-form-actions.fi-sticky {
        background: color-mix(in srgb, var(--std-surface) 94%, transparent);
        border: 1px solid var(--std-line);
        backdrop-filter: blur(10px);
    }

    body[data-page="student-create"] .fi-fo-file-upload .filepond--panel-root {
        border: 2px dashed var(--std-line);
        border-radius: 14px;
        background: var(--std-surface-2);
    }

    body[data-page="student-create"] .fi-fo-file-upload .filepond--drop-label {
        min-height: 112px;
        color: var(--std-muted);
    }

    .std-form-progress {
        margin-bottom: 4px;
        padding: 13px 15px;
        border: 1px solid var(--std-line);
        border-radius: 14px;
        background: var(--std-surface);
        box-shadow: var(--std-shadow);
    }

    .std-form-progress-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: var(--std-muted);
        font-size: 12.5px;
        font-weight: 700;
    }

    .std-form-progress-top b {
        color: var(--std-brand);
        font-size: 13px;
        font-variant-numeric: tabular-nums;
    }

    .std-form-progress-track {
        height: 7px;
        overflow: hidden;
        margin-top: 8px;
        border-radius: 99px;
        background: var(--std-surface-2);
    }

    .std-form-progress-track i {
        display: block;
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, var(--std-brand), var(--std-brand-2));
        transition: width .25s ease;
    }

    .std-form-progress p {
        margin: 7px 0 0;
        color: var(--std-muted);
        font-size: 11.5px;
    }

    .std-photo-preview {
        display: flex;
        align-items: center;
        gap: 13px;
        margin-bottom: 10px;
        padding: 11px;
        border: 1px dashed var(--std-line);
        border-radius: 13px;
        background: var(--std-surface-2);
    }

    .std-photo-preview img {
        width: 64px;
        height: 64px;
        flex: none;
        border: 3px solid var(--std-surface);
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 8px 18px -12px var(--std-brand);
    }

    .std-photo-preview b {
        display: block;
        color: var(--std-text);
        font-size: 12.5px;
    }

    .std-photo-preview span {
        display: block;
        margin-top: 2px;
        color: var(--std-muted);
        font-size: 11.5px;
    }

    .std-id-preview {
        padding: 15px;
        border: 1px solid var(--std-line);
        border-radius: 15px;
        background: var(--std-surface);
    }

    .std-id-preview-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 11px;
    }

    .std-id-preview-head b {
        color: var(--std-text);
        font-size: 13px;
    }

    .std-id-preview-head span {
        padding: 3px 9px;
        border-radius: 99px;
        color: var(--std-ok);
        background: color-mix(in srgb, var(--std-ok) 13%, transparent);
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .std-id-preview-stage {
        display: grid;
        place-items: center;
        min-height: 220px;
        overflow: hidden;
        padding: 12px;
        border-radius: 12px;
        background: radial-gradient(circle at 30% 20%, color-mix(in srgb, var(--std-brand) 12%, var(--std-surface-2)), var(--std-surface-2));
    }

    .std-id-preview-stage .id-card {
        flex: none;
        transform: scale(.62);
        transform-origin: center center;
        box-shadow: 0 14px 34px rgba(20, 26, 51, .22);
    }

    .std-id-preview-stage.is-portrait {
        min-height: 250px;
    }

    .std-id-preview-stage.is-portrait .id-card {
        transform: scale(.46);
    }

    .std-id-preview-note {
        margin: 9px 0 0;
        color: var(--std-muted);
        font-size: 11.5px;
    }

    @media (max-width: 1024px) {
        body[data-page="student-create"] .fi-fo-tabs > nav {
            top: .5rem;
        }
    }

    @media (max-width: 480px) {
        .std-id-preview-stage .id-card { transform: scale(.48); }
        .std-id-preview-stage.is-portrait .id-card { transform: scale(.36); }
    }

    @media (prefers-reduced-motion: reduce) {
        .std-form-progress-track i { transition: none; }
    }
</style>
