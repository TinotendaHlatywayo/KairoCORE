@php
    $sample = $this->sampleStudentForPreview();
@endphp

@include('components.id-card-styles')

<div
    class="tpl-content"
    x-data
    x-on:keydown.window.slash.prevent="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && ! $event.target.isContentEditable) { $refs.search.focus(); $refs.search.select(); }"
>
    <style>
        .tpl-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; margin-bottom: 16px; padding: 11px; border: 1px solid var(--tpl-line); border-radius: 16px; background: var(--tpl-surface); box-shadow: var(--tpl-shadow); }
        .tpl-search { position: relative; flex: 1; min-width: 220px; }
        .tpl-search input { width: 100%; padding: 10px 14px 10px 38px; border: 1px solid var(--tpl-line); border-radius: 10px; background: var(--tpl-surface); color: var(--tpl-text); font-size: 13px; }
        .tpl-search input::placeholder { color: var(--tpl-muted); }
        .tpl-search input:focus { outline: 0; border-color: var(--tpl-brand); box-shadow: 0 0 0 4px color-mix(in srgb, var(--tpl-brand) 18%, transparent); }
        .tpl-search svg { position: absolute; left: 13px; top: 50%; width: 16px; height: 16px; transform: translateY(-50%); color: var(--tpl-muted); pointer-events: none; }
        .tpl-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 15px; border: 1px solid var(--tpl-line); border-radius: 10px; background: var(--tpl-surface); color: var(--tpl-text); font-size: 13px; font-weight: 700; line-height: 1.2; text-decoration: none; transition: border-color .15s ease, transform .15s ease; }
        .tpl-btn svg { width: 16px; height: 16px; }
        .tpl-btn:hover { border-color: var(--tpl-brand); transform: translateY(-1px); }
        .tpl-btn.is-primary { color: #fff; border-color: transparent; background: linear-gradient(135deg, var(--tpl-brand), #6d5dfc); }

        .tpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(330px, 100%), 1fr)); gap: 18px; }
        .tpl-card { position: relative; display: flex; flex-direction: column; gap: 13px; padding: 16px; border: 1px solid var(--tpl-line); border-radius: 18px; background: var(--tpl-surface); box-shadow: var(--tpl-shadow); transition: transform .22s ease, border-color .22s ease; transform-style: preserve-3d; perspective: 900px; }
        .tpl-card:hover { transform: translateY(-3px) rotateX(var(--tilt-y, 0deg)) rotateY(var(--tilt-x, 0deg)); border-color: color-mix(in srgb, var(--tpl-brand) 45%, var(--tpl-line)); }
        .tpl-card .tpl-stage { transform: translateZ(14px); }
        .tpl-stage { display: flex; align-items: center; justify-content: center; min-height: 218px; overflow: hidden; padding: 16px; border-radius: 14px; background: radial-gradient(circle at 30% 20%, color-mix(in srgb, var(--tpl-brand) 13%, var(--tpl-surface-2)), var(--tpl-surface-2)); }
        .tpl-stage .id-card { flex: none; max-width: 100%; object-fit: contain; zoom: 0.52; transform: none; transform-origin: center center; box-shadow: 0 14px 34px rgba(20, 26, 51, .22); }
        .tpl-stage.is-portrait { min-height: 290px; }
        .tpl-stage.is-portrait .id-card { zoom: 0.42; }
        @media (max-width: 420px) {
            .tpl-stage { min-height: 180px; padding: 10px; }
            .tpl-stage .id-card { zoom: 0.42; }
            .tpl-stage.is-portrait { min-height: 210px; }
            .tpl-stage.is-portrait .id-card { zoom: 0.34; }
        }
        .tpl-stage-empty { max-width: 220px; color: var(--tpl-muted); font-size: 12.5px; font-weight: 600; text-align: center; }
        .tpl-meta { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .tpl-name { min-width: 0; }
        .tpl-name b { display: block; overflow: hidden; color: var(--tpl-text); font-size: 15px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
        .tpl-name small { display: block; margin-top: 3px; color: var(--tpl-muted); font-size: 12px; font-weight: 600; }
        .tpl-switch { position: relative; flex: none; width: 42px; height: 24px; border: 0; border-radius: 99px; background: var(--tpl-line); transition: background .2s ease; }
        .tpl-switch::after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, .3); transition: left .2s ease; }
        .tpl-switch.on { background: var(--tpl-ok); }
        .tpl-switch.on::after { left: 21px; }
        .tpl-actions { display: flex; flex-wrap: wrap; gap: 7px; margin-top: auto; padding-top: 3px; }
        .tpl-actions a, .tpl-actions button { display: inline-flex; flex: 1; align-items: center; justify-content: center; gap: 5px; min-width: 84px; padding: 8px 10px; border: 1px solid var(--tpl-line); border-radius: 9px; background: var(--tpl-surface); color: var(--tpl-text); font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background .15s ease, border-color .15s ease, color .15s ease; }
        .tpl-actions a svg, .tpl-actions button svg { width: 14px; height: 14px; }
        .tpl-actions a:hover, .tpl-actions button:hover { border-color: var(--tpl-brand); color: var(--tpl-brand); }
        .tpl-actions .is-danger { color: var(--std-bad, #ef4444); }
        .tpl-actions .is-danger:hover { border-color: #ef4444; color: #ef4444; }
        .tpl-active { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 99px; color: #047857; background: rgba(16, 185, 129, .13); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .tpl-results { margin-top: 14px; color: var(--tpl-muted); font-size: 12.5px; text-align: center; }
        .tpl-empty { padding: 54px 20px; border: 1px dashed var(--tpl-line); border-radius: 18px; background: var(--tpl-surface); color: var(--tpl-muted); text-align: center; }
        .tpl-empty b { display: block; margin-bottom: 5px; color: var(--tpl-text); font-size: 18px; }

        @media (max-width: 640px) {
            .tpl-search { min-width: 100%; }
            .tpl-btn { flex: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .tpl-card, .tpl-btn, .tpl-switch { transition: none !important; }
        }
    </style>

    <div class="tpl-toolbar">
        <div class="tpl-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input
                type="search"
                x-ref="search"
                wire:model.live.debounce.300ms="tableSearch"
                placeholder="{{ __('Search templates') }}"
                aria-label="{{ __('Search templates') }}"
                autocomplete="off"
            >
        </div>

        <a
            href="{{ route('students.print-cards', ['tenant' => current_tenant()?->subdomain, 'scope' => 'all', 'layout' => 'pvc']) }}"
            class="tpl-btn"
            target="_blank"
            rel="noopener"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M6 17h12v4H6z"/></svg>
            {{ __('Bulk print ID cards') }}
        </a>

        <a href="{{ \App\Filament\App\Resources\CardTemplateResource::getUrl('create') }}" class="tpl-btn is-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            {{ __('New card template') }}
        </a>
    </div>

    <div class="tpl-grid">
        @foreach ($records as $template)
            @php
                $previewUrl = route('students.print-cards', [
                    'tenant' => current_tenant()?->subdomain,
                    'scope' => 'selected',
                    'ids' => $sample?->getKey() ?? 0,
                    'layout' => 'pvc',
                    'template_id' => $template->getKey(),
                ]);
                $editUrl = \App\Filament\App\Resources\CardTemplateResource::getUrl('edit', ['record' => $template]);
                $recordKey = (string) $template->getKey();
                $canUpdate = \App\Filament\App\Resources\CardTemplateResource::can('update', $template);
                $canDelete = \App\Filament\App\Resources\CardTemplateResource::can('delete', $template);
                $canCreate = \App\Filament\App\Resources\CardTemplateResource::can('create');
            @endphp

            <article wire:key="card-template-{{ $template->getKey() }}" class="tpl-card" x-data="{ tiltX: 0, tiltY: 0 }" x-on:pointermove="
                    const rect = $el.getBoundingClientRect();
                    const x = ($event.clientX - rect.left) / rect.width - 0.5;
                    const y = ($event.clientY - rect.top) / rect.height - 0.5;
                    $el.style.setProperty('--tilt-x', (x * 6).toFixed(2) + 'deg');
                    $el.style.setProperty('--tilt-y', (-y * 6).toFixed(2) + 'deg');
                " x-on:pointerleave="$el.style.removeProperty('--tilt-x'); $el.style.removeProperty('--tilt-y');">
                <div class="tpl-stage {{ $template->orientation === 'portrait' ? 'is-portrait' : '' }}">
                    @if ($sample)
                        @include('components.id-card-render', [
                            'student' => $sample,
                            'template' => $template,
                            'school' => current_tenant(),
                        ])
                    @else
                        <div class="tpl-stage-empty">
                            {{ __('Add a student to preview this template with real data.') }}
                        </div>
                    @endif
                </div>

                <div class="tpl-meta">
                    <div class="tpl-name">
                        <b title="{{ $template->name }}">{{ $template->name }}</b>
                        <small>
                            {{ ucfirst($template->orientation) }}
                            · {{ $template->barcode_format ?: 'Code128' }}
                            @if ($template->is_active)
                                <span class="tpl-active">{{ __('Active') }}</span>
                            @endif
                        </small>
                    </div>

                    @if ($template->is_active)
                        <span class="tpl-switch on" role="img" aria-label="{{ __('Active template') }}"></span>
                    @elseif ($canUpdate)
                        <button
                            type="button"
                            class="tpl-switch"
                            x-on:click="mountAction('activate', @js($recordKey))"
                            aria-label="{{ __('Set :name as active', ['name' => $template->name]) }}"
                            title="{{ __('Set as active') }}"
                        ></button>
                    @else
                        <span class="tpl-switch" role="img" aria-label="{{ __('Inactive template') }}"></span>
                    @endif
                </div>

                <div class="tpl-actions">
                    <button type="button" x-on:click="mountAction('preview', @js($recordKey))">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        {{ __('Preview') }}
                    </button>

                    @if ($canUpdate)
                        <a href="{{ $editUrl }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            {{ __('Edit') }}
                        </a>
                    @endif

                    @if ($canCreate)
                        <button type="button" x-on:click="mountAction('duplicate', @js($recordKey))">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            {{ __('Duplicate') }}
                        </button>
                    @endif

                    @if ($canDelete)
                        <button type="button" class="is-danger" x-on:click="mountAction('delete', @js($recordKey))">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                            {{ __('Delete') }}
                        </button>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @if (method_exists($records, 'total') && $records->total() > 0)
        <div class="tpl-results">
            {{ __('Showing :from–:to of :total templates', [
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'total' => $records->total(),
            ]) }}
        </div>
    @endif
</div>
