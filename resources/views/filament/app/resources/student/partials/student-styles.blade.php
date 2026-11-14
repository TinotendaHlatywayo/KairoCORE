<style>
    [x-cloak] { display: none !important; }

    .std-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; margin-bottom: 12px; padding: 11px; border: 1px solid var(--std-line); border-radius: 16px; background: var(--std-surface); box-shadow: var(--std-shadow); }
    .std-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 8px 14px; border: 1px solid var(--std-line); border-radius: 10px; background: var(--std-surface); color: var(--std-text); font-size: 13px; font-weight: 700; line-height: 1.2; text-decoration: none; transition: border-color .15s ease, transform .15s ease, background .15s ease; }
    .std-btn svg { width: 16px; height: 16px; }
    .std-btn:hover:not(:disabled) { border-color: var(--std-brand); transform: translateY(-1px); }
    .std-btn:disabled { cursor: not-allowed; opacity: .45; }
    .std-bulk-btn { color: #fff; border-color: transparent; background: linear-gradient(135deg, var(--std-brand), #6d5dfc); }
    .std-count { display: inline-flex; min-width: 20px; height: 20px; align-items: center; justify-content: center; padding: 0 5px; border-radius: 99px; background: rgba(255, 255, 255, .24); font-size: 11px; }
    .std-clear { color: var(--std-bad); }
    .std-bulkwrap { position: relative; }
    .std-menu { position: absolute; top: calc(100% + 8px); left: 0; z-index: 40; min-width: 255px; padding: 6px; border: 1px solid var(--std-line); border-radius: 14px; background: var(--std-surface); box-shadow: 0 18px 45px -18px rgba(15, 23, 42, .35); }
    .std-menu-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; border: 0; border-radius: 9px; background: transparent; color: var(--std-text); font-size: 13px; font-weight: 600; text-align: left; }
    .std-menu-item svg { width: 17px; height: 17px; flex: none; }
    .std-menu-item:hover { background: var(--std-surface-2); }
    .std-menu-item.is-info { color: var(--std-brand); }
    .std-menu-item.is-success { color: var(--std-ok); }
    .std-menu-item.is-primary { color: var(--std-male); }
    .std-menu-item.is-danger { color: var(--std-bad); }
    .std-check { display: inline-flex; align-items: center; gap: 7px; min-height: 38px; padding: 8px 11px; border: 1px solid var(--std-line); border-radius: 10px; background: var(--std-surface); color: var(--std-muted); font-size: 12.5px; font-weight: 600; cursor: pointer; user-select: none; }
    .std-check input { width: 16px; height: 16px; accent-color: var(--std-brand); cursor: pointer; }
    .std-search { position: relative; flex: 1; min-width: 230px; }
    .std-search input { width: 100%; height: 38px; padding: 9px 42px 9px 38px; border: 1px solid var(--std-line); border-radius: 10px; background: var(--std-surface); color: var(--std-text); font-size: 13px; }
    .std-search input::placeholder { color: var(--std-muted); }
    .std-search-icon { position: absolute; left: 13px; top: 50%; width: 16px; height: 16px; transform: translateY(-50%); color: var(--std-muted); pointer-events: none; }
    .std-search kbd { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); padding: 1px 6px; border: 1px solid var(--std-line); border-radius: 6px; color: var(--std-muted); font-size: 11px; line-height: 1.5; }
    .std-search input:focus, .std-select:focus, .std-combobox-trigger:focus-visible, .std-combobox-search:focus { outline: 0; border-color: var(--std-brand); box-shadow: 0 0 0 4px color-mix(in srgb, var(--std-brand) 18%, transparent); }
    .std-select { max-width: 190px; height: 38px; appearance: none; -webkit-appearance: none; padding: 9px 34px 9px 12px; border: 1px solid var(--std-line); border-radius: 10px; background-color: var(--std-surface); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236a7392' stroke-width='2' stroke-linecap='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 14px; color: var(--std-text); font-size: 13px; }
    .std-combobox { position: relative; width: 190px; }
    .std-combobox-trigger { display: flex; width: 100%; height: 38px; align-items: center; justify-content: space-between; gap: 8px; padding: 9px 11px; border: 1px solid var(--std-line); border-radius: 10px; background: var(--std-surface); color: var(--std-text); font-size: 13px; text-align: left; }
    .std-combobox-trigger svg { width: 15px; height: 15px; flex: none; color: var(--std-muted); }
    .std-combobox-value { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .std-combobox-menu { position: absolute; top: calc(100% + 6px); left: 0; z-index: 45; width: 250px; padding: 7px; border: 1px solid var(--std-line); border-radius: 12px; background: var(--std-surface); box-shadow: 0 18px 45px -18px rgba(15, 23, 42, .35); }
    .std-combobox-search { width: 100%; height: 34px; padding: 7px 10px; border: 1px solid var(--std-line); border-radius: 8px; background: var(--std-surface-2); color: var(--std-text); font-size: 12.5px; }
    .std-combobox-list { max-height: 11.25rem; overflow-y: auto; margin-top: 6px; }
    .std-combobox-option { display: block; width: 100%; padding: 8px 10px; border: 0; border-radius: 8px; background: transparent; color: var(--std-text); font-size: 12.5px; font-weight: 600; text-align: left; }
    .std-combobox-option:hover, .std-combobox-option.is-selected { background: color-mix(in srgb, var(--std-brand) 10%, transparent); color: var(--std-brand); }
    .std-combobox-empty { padding: 8px 10px; color: var(--std-muted); font-size: 12px; }
    .std-segwrap { display: inline-flex; align-items: center; gap: 7px; }
    .std-seg { display: inline-flex; overflow: hidden; border: 1px solid var(--std-line); border-radius: 10px; background: var(--std-surface); }
    .std-seg button { display: inline-flex; min-width: 40px; height: 38px; align-items: center; justify-content: center; gap: 6px; padding: 0 11px; border: 0; background: transparent; color: var(--std-muted); font-size: 12.5px; font-weight: 700; }
    .std-seg button svg { width: 15px; height: 15px; }
    .std-seg button:hover { background: var(--std-surface-2); color: var(--std-text); }
    .std-seg button.on { background: var(--std-brand); color: #fff; }
    .std-seg button:focus-visible { outline: 3px solid color-mix(in srgb, var(--std-brand) 45%, transparent); outline-offset: -3px; }
    .std-selection { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px; padding: 9px 12px; border: 1px solid color-mix(in srgb, var(--std-brand) 30%, var(--std-line)); border-radius: 11px; background: color-mix(in srgb, var(--std-brand) 8%, var(--std-surface)); color: var(--std-text); font-size: 12.5px; font-weight: 600; }
    .std-selection b { color: var(--std-brand); }
    .std-selection button { border: 0; background: transparent; color: var(--std-brand); font-size: 12.5px; font-weight: 800; text-decoration: underline; text-underline-offset: 2px; }
    .std-selection button:disabled { opacity: .5; }
    .std-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; margin-bottom: 16px; }
    .std-chip-label { color: var(--std-muted); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .std-chip-label-gap { margin-left: 10px; }
    .std-chip { min-height: 32px; padding: 5px 13px; border: 1px solid var(--std-line); border-radius: 99px; background: var(--std-surface); color: var(--std-muted); font-size: 12.5px; font-weight: 700; transition: border-color .15s ease, background .15s ease, color .15s ease; }
    .std-chip:hover { border-color: var(--std-brand); }
    .std-chip.on { border-color: var(--std-brand); background: color-mix(in srgb, var(--std-brand) 13%, transparent); color: var(--std-brand); }

    .std-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 16px; transition: opacity .15s ease; }
    .std-cards.is-loading { opacity: .55; pointer-events: none; }
    .student-card { position: relative; display: flex; flex-direction: column; gap: 11px; overflow: hidden; padding: 18px; border: 1px solid var(--std-line); border-radius: 18px; background: var(--std-surface); box-shadow: var(--std-shadow); transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
    .student-card::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 4px; background: linear-gradient(90deg, var(--std-brand-2), var(--std-ok), var(--std-warn)); }
    .student-card::after { content: ""; position: absolute; inset: 0; z-index: 0; opacity: 0; background: radial-gradient(180px circle at var(--mx, 50%) var(--my, 50%), color-mix(in srgb, var(--std-warn) 20%, transparent), transparent 70%); transition: opacity .2s ease; pointer-events: none; }
    .student-card:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--std-brand) 45%, var(--std-line)); box-shadow: 0 16px 36px -22px color-mix(in srgb, var(--std-brand) 55%, transparent), var(--std-shadow); }
    .student-card:hover::after { opacity: 1; }
    .student-card > * { position: relative; z-index: 1; }
    .student-card.is-selected { outline: 2px solid var(--std-brand); outline-offset: 0; }
    .student-card .card-status { position: absolute; top: 14px; right: 14px; z-index: 2; padding: 3px 10px; border-radius: 99px; font-size: 11.5px; font-weight: 800; color: var(--status-ink); background: color-mix(in srgb, var(--status) 16%, transparent); }
    .student-card .sc-card-select { position: absolute; top: 14px; left: 14px; z-index: 2; display: flex; }
    .student-card .sc-card-select input { width: 18px; height: 18px; accent-color: var(--std-brand); cursor: pointer; }
    .student-card .photo-wrap { width: 74px; height: 74px; margin: 7px auto 0; border-radius: 50%; padding: 3px; background: conic-gradient(from 180deg, var(--std-brand-2), var(--std-ok), var(--std-warn), var(--std-brand-2)); box-shadow: 0 8px 22px -12px var(--std-brand); transition: transform .2s ease; }
    .student-card:hover .photo-wrap { transform: scale(1.03); }
    .student-card .photo-wrap img { width: 100%; height: 100%; border-radius: 50%; border: 3px solid var(--std-surface); object-fit: cover; background: var(--std-surface-2); }
    .student-card .card-id { min-width: 0; text-align: center; }
    .student-card .card-name { color: var(--std-text); font-size: 16px; font-weight: 800; line-height: 1.25; letter-spacing: -.01em; }
    .student-card .card-class { margin-top: 2px; color: var(--std-muted); font-size: 12px; font-weight: 600; }
    .student-card .sc-gender-pill { display: inline-flex; align-items: center; gap: 4px; margin-top: 5px; padding: 2px 9px; border-radius: 99px; font-size: 10.5px; font-weight: 800; line-height: 1.5; letter-spacing: .04em; text-transform: uppercase; }
    .student-card .sc-gender-pill svg { width: 12px; height: 12px; }
    .student-card .sc-gender-male { color: var(--std-male); background: color-mix(in srgb, var(--std-male) 12%, transparent); }
    .student-card .sc-gender-female { color: var(--std-female); background: color-mix(in srgb, var(--std-female) 12%, transparent); }
    .student-card .sc-gender-other { color: var(--std-muted); background: var(--std-surface-2); }
    .student-card .card-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; padding-top: 11px; border-top: 1px dashed var(--std-line); }
    .student-card .card-meta > div { min-width: 0; }
    .student-card .m-label { display: block; color: var(--std-muted); font-size: 10.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .student-card .m-value { display: block; overflow: hidden; color: var(--std-text); font-size: 12.5px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
    .student-card .card-actions { display: flex; gap: 8px; margin-top: auto; }
    .student-card .card-actions a { display: inline-flex; flex: 1; align-items: center; justify-content: center; padding: 8px 9px; border-radius: 9px; font-size: 12px; font-weight: 800; text-decoration: none; transition: background .15s ease, color .15s ease; }
    .student-card .btn-view { color: #0891b2; background: #ecfeff; }
    .student-card .btn-view:hover { color: #fff; background: #0891b2; }
    .student-card .btn-edit { color: var(--std-brand); background: color-mix(in srgb, var(--std-brand) 10%, transparent); }
    .student-card .btn-edit:hover { color: #fff; background: var(--std-brand); }
    .student-card .btn-print { color: #059669; background: #ecfdf5; }
    .student-card .btn-print:hover { color: #fff; background: #059669; }

    .std-cards.is-list { grid-template-columns: 1fr; }
    .std-cards.is-list .student-card { display: grid; grid-template-columns: 24px 52px minmax(180px, 1.2fr) minmax(0, 3.2fr) auto auto; align-items: center; gap: 14px; padding: 12px 16px; }
    .std-cards.is-list .student-card::before { inset: 0 auto 0 0; width: 4px; height: auto; }
    .std-cards.is-list .student-card .sc-card-select { position: static; order: 1; }
    .std-cards.is-list .student-card .photo-wrap { order: 2; width: 48px; height: 48px; margin: 0; flex: none; }
    .std-cards.is-list .student-card .card-id { order: 3; text-align: left; }
    .std-cards.is-list .student-card .card-name, .std-cards.is-list .student-card .card-class { text-align: left; }
    .std-cards.is-list .student-card .sc-gender-pill { margin-top: 3px; }
    .std-cards.is-list .student-card .card-meta { order: 4; grid-template-columns: repeat(4, minmax(64px, 1fr)); gap: 8px; padding-top: 0; border-top: 0; }
    .std-cards.is-list .student-card .card-status { position: static; order: 5; justify-self: end; }
    .std-cards.is-list .student-card .card-actions { order: 6; flex: none; width: 210px; margin-top: 0; }

    .std-empty { padding: 54px 20px; border: 1px dashed var(--std-line); border-radius: 18px; background: var(--std-surface); color: var(--std-muted); text-align: center; }
    .std-empty b { display: block; margin-bottom: 5px; color: var(--std-text); font-size: 18px; }
    .std-empty .std-btn { margin-top: 14px; }

    body[data-page="students"] .fi-ta-pagination { margin-top: 14px; padding: 10px 14px; border: 1px solid var(--std-line); border-radius: 14px; background: var(--std-surface); box-shadow: var(--std-shadow); }

    .dark .student-card .photo-wrap img { border-color: var(--std-surface); }
    .dark .student-card .btn-view { background: rgba(8, 145, 178, .14); }
    .dark .student-card .btn-print { background: rgba(5, 150, 105, .14); }

    @media (max-width: 1100px) {
        .std-cards.is-list .student-card { grid-template-columns: 24px 52px minmax(0, 1fr) auto; }
        .std-cards.is-list .student-card .card-id { grid-column: 3; }
        .std-cards.is-list .student-card .card-meta { grid-column: 2 / -1; grid-row: 2; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .std-cards.is-list .student-card .card-status { grid-column: 4; grid-row: 1; }
        .std-cards.is-list .student-card .card-actions { grid-column: 2 / -1; grid-row: 3; width: auto; justify-content: flex-end; }
    }

    @media (max-width: 640px) {
        .std-search { min-width: 100%; order: 10; }
        .std-select, .std-combobox { flex: 1; width: auto; max-width: none; }
        .std-segwrap { margin-left: auto; }
        .std-cards { grid-template-columns: 1fr; }
        .std-cards.is-list .student-card { grid-template-columns: 24px 48px minmax(0, 1fr); }
        .std-cards.is-list .student-card .card-status { grid-column: 1 / -1; grid-row: 2; justify-self: start; margin-left: 0; }
        .std-cards.is-list .student-card .card-meta { grid-column: 1 / -1; grid-row: 3; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .std-cards.is-list .student-card .card-actions { grid-column: 1 / -1; grid-row: 4; justify-content: stretch; }
        .std-cards.is-list .student-card .card-actions a { flex: 1; }
        .std-combobox-menu { width: min(250px, calc(100vw - 40px)); }
    }

    @media (prefers-reduced-motion: reduce) {
        .student-card, .student-card .photo-wrap, .std-btn, .std-chip, .std-seg button, .std-cards { transition: none !important; }
        .student-card:hover, .student-card:hover .photo-wrap { transform: none; }
        .student-card::after { display: none; }
    }
</style>
