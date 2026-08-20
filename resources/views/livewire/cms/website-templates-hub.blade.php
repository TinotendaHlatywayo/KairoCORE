<div class="wcm-shell min-h-screen p-6 lg:p-8">
    @include('modules.cms.studio-base')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <div class="max-w-6xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900">{{ __('Website Templates') }}</h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ __('Design whole websites once, then pick which one is live. Content is managed separately and follows the active template.') }}
                </p>
            </div>
            <div class="flex gap-2">
                <button wire:click="importFromLive" class="wcm-btn wcm-btn-secondary" title="Start a template from your current live website, content included">
                    {{ __('Import current site') }}
                </button>
                <button wire:click="openCreateForm" class="wcm-btn wcm-btn-primary">{{ __('+ Create Website Template') }}</button>
            </div>
        </div>

        {{-- Create form --}}
        @if($showCreateForm)
            <div class="wcm-card p-6">
                <h2 class="text-sm font-black uppercase tracking-wide text-slate-900 mb-4">{{ __('New Website Template') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 block">{{ __('Template name') }}</label>
                        <input type="text" wire:model="newTemplateName" placeholder="e.g. School1, Spring Edition..." class="wcm-input">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 block">{{ __('Base theme preset') }}</label>
                        <select wire:model="newTemplatePreset" class="wcm-input">
                            @foreach($themePresets as $key => $name)
                                <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 block">Description (optional)</label>
                        <input type="text" wire:model="newTemplateDescription" placeholder="What is this template for?" class="wcm-input">
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-4">
                    <button wire:click="createTemplate" class="wcm-btn wcm-btn-success">{{ __('Create &amp; start designing') }}</button>
                    <button wire:click="$set('showCreateForm', false)" class="wcm-btn wcm-btn-ghost">{{ __('Cancel') }}</button>
                </div>
                @error('newTemplateName') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Templates grid --}}
        @forelse($templates as $template)
            <div class="wcm-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-black text-slate-900 truncate">{{ $template['name'] }}</h3>
                        @if($template['is_active'])
                            <span class="wcm-pill wcm-pill-live">{{ __('● Live on website') }}</span>
                        @else
                            <span class="wcm-pill wcm-pill-draft">{{ __('Not live') }}</span>
                        @endif
                    </div>
                    @if($template['description'])
                        <p class="text-sm text-slate-500 mt-1">{{ $template['description'] }}</p>
                    @endif
                    <p class="text-xs text-slate-400 mt-2">
                        {{ $template['page_count'] }} page(s) · updated {{ $template['updated_at'] }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($template['is_active'])
                        <a href="{{ \App\Filament\App\Pages\WebsiteContentManager::getUrl(['templateId' => $template['id']]) }}"
                           class="wcm-btn wcm-btn-primary">{{ __('Manage Content') }}</a>
                    @else
                        <button wire:click="applyTemplate({{ $template['id'] }})" class="wcm-btn wcm-btn-success">
                            {{ __('Set Live / Apply') }}
                        </button>
                    @endif

                    @if($template['home_page_id'])
                        <a href="{{ \App\Filament\App\Pages\VisualCmsBuilder::getUrl(['pageId' => $template['home_page_id']]) }}"
                           class="wcm-btn wcm-btn-secondary">{{ __('Design Studio') }}</a>
                    @endif

                    <button wire:click="duplicateTemplate({{ $template['id'] }})" class="wcm-btn wcm-btn-ghost">{{ __('Duplicate') }}</button>

                    @if($pendingDeleteId === $template['id'])
                        <span class="text-xs font-bold text-red-600">{{ __('Delete this template?') }}</span>
                        <button wire:click="deleteTemplate" class="wcm-btn wcm-btn-danger">{{ __('Yes, delete') }}</button>
                        <button wire:click="cancelDeleteTemplate" class="wcm-btn wcm-btn-ghost">{{ __('Keep') }}</button>
                    @else
                        <button wire:click="confirmDeleteTemplate({{ $template['id'] }})" class="wcm-btn wcm-btn-ghost text-red-500 hover:!bg-red-50">{{ __('Delete') }}</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="wcm-card p-12 text-center">
                <div class="text-4xl mb-3">{{ __('🗂️') }}</div>
                <h3 class="text-lg font-black text-slate-900">{{ __('No website templates yet') }}</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                    {{ __('Create your first template to combine a theme with any of the 5 predesigned page layouts per page, then customize the design.') }}
                </p>
                <button wire:click="openCreateForm" class="wcm-btn wcm-btn-primary mt-5">{{ __('+ Create Website Template') }}</button>
            </div>
        @endforelse

        {{-- Live site summary --}}
        <div class="wcm-card p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-black text-slate-900">{{ __('Public website') }}</h4>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ $livePageCount }} published page(s) on the live site
                </p>
            </div>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="wcm-btn wcm-btn-secondary">
                {{ __('View public website ↗') }}
            </a>
        </div>
    </div>
</div>
