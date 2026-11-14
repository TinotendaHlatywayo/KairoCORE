<div
    class="tpl-content"
    x-data
    x-on:keydown.window.slash.prevent="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && ! $event.target.isContentEditable) { $refs.search.focus(); $refs.search.select(); }"
>
    <style>
        .tpl-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; margin-bottom: 16px; padding: 11px; border: 1px solid var(--tpl-line); border-radius: 16px; background: var(--tpl-surface); box-shadow: var(--tpl-shadow); }
        .tpl-search { position: relative; flex: 1; min-width: 220px; }
        .tpl-search input { width: 100%; padding: 10px 14px 10px 38px; border: 1px solid var(--tpl-line); border-radius: 10px; background: var(--tpl-surface); color: var(--tpl-text); font-size: 13px; }
        .tpl-search input:focus { outline: 0; border-color: var(--tpl-brand); box-shadow: 0 0 0 4px color-mix(in srgb, var(--tpl-brand) 18%, transparent); }
        .tpl-search svg { position: absolute; left: 13px; top: 50%; width: 16px; height: 16px; transform: translateY(-50%); color: var(--tpl-muted); pointer-events: none; }
        .tpl-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 15px; border: 1px solid var(--tpl-line); border-radius: 10px; background: var(--tpl-surface); color: var(--tpl-text); font-size: 13px; font-weight: 700; line-height: 1.2; text-decoration: none; }
        .tpl-btn:hover { border-color: var(--tpl-brand); }
        .tpl-btn.is-primary { color: #fff; border-color: transparent; background: linear-gradient(135deg, var(--tpl-brand), #6d5dfc); }
        .tpl-empty { padding: 54px 20px; border: 1px dashed var(--tpl-line); border-radius: 18px; background: var(--tpl-surface); color: var(--tpl-muted); text-align: center; }
        .tpl-empty b { display: block; margin-bottom: 5px; color: var(--tpl-text); font-size: 18px; }
        .tpl-empty .tpl-btn { margin-top: 14px; }
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

        <a href="{{ \App\Filament\App\Resources\CardTemplateResource::getUrl('create') }}" class="tpl-btn is-primary">
            {{ __('New card template') }}
        </a>
    </div>

    <div class="tpl-empty">
        <b>{{ __('No templates match') }}</b>
        {{ __('Try a different search or create a new card template.') }}
        <div>
            <button type="button" class="tpl-btn" wire:click="$set('tableSearch', '')">
                {{ __('Clear search') }}
            </button>
        </div>
    </div>
</div>
