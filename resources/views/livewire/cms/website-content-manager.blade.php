<div class="wcm-shell min-h-screen flex flex-col"
     x-data="{ previewTab: 'split' }">
    @include('modules.cms.studio-base')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ \Modules\CMS\Services\CmsTemplateService::googleFontsUrl([$theme['fontPrimary'] ?? 'Inter', $theme['fontSecondary'] ?? 'Outfit', 'Sora'], '400;500;600;700') }}" rel="stylesheet">

    @if(!$activeTemplateId)
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="wcm-card wcm-empty text-center max-w-lg">
                <div class="wcm-empty-icon">{{ __('📝') }}</div>
                <h2 class="text-xl wcm-heading">{{ __('No active website template') }}</h2>
                <p class="text-sm wcm-muted mt-2">
                    {{ __('Content editing works on the template that is currently live. Head to Website Templates to create one and set it live first.') }}
                </p>
                <a href="{{ \App\Filament\App\Pages\WebsiteTemplatesHub::getUrl() }}" class="wcm-btn wcm-btn-primary mt-4">
                    {{ __('Go to Website Templates') }}
                </a>
            </div>
        </div>
    @else
        {{-- Header --}}
        <div class="border-b wcm-pane bg-white px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg wcm-heading">{{ __('Content Manager') }}</h1>
                    <span class="wcm-pill wcm-pill-live">● Editing {{ $activeTemplateName }}</span>
                </div>
                <p class="text-xs wcm-muted mt-1">Content only — design (colors, sizes, styles, positions) is locked. Changes publish automatically.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold wcm-muted">
                    @if($lastSavedAt) Saved at {{ $lastSavedAt }} @else Live · auto-saves @endif
                </span>
                <a href="{{ \App\Filament\App\Pages\WebsiteTemplatesHub::getUrl() }}" class="wcm-btn wcm-btn-ghost">{{ __('Templates') }}</a>
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="wcm-btn wcm-btn-secondary">{{ __('View live site ↗') }}</a>
            </div>
        </div>

        {{-- Body: pages | content form | preview --}}
        <div class="wcm-cms-grid">

            {{-- Pages rail --}}
            <aside class="wcm-divider-r bg-white p-3 space-y-2 overflow-y-auto">
                <p class="wcm-eyebrow px-2 py-1">{{ __('Pages') }}</p>
                @foreach($sitePages as $page)
                    <button wire:click="selectPage({{ $page['id'] }})"
                            class="wcm-nav-item {{ $selectedPageId === $page['id'] ? 'is-active' : '' }}">
                        <span class="wcm-nav-main">
                            <span class="wcm-nav-title">{{ $page['title'] }}</span>
                            @if($page['is_homepage'])
                                <span class="wcm-meta">{{ __('Homepage') }}</span>
                            @endif
                        </span>
                    </button>
                @endforeach
            </aside>

            {{-- Content form --}}
            <section class="wcm-canvas p-4 space-y-3 wcm-scroll">
                {{-- Block picker --}}
                <div class="wcm-card p-3 space-y-2">
                    <p class="wcm-eyebrow px-1">{{ __('Sections on this page') }}</p>
                    @foreach($blocks as $index => $block)
                        <button wire:click="selectBlock({{ $index }})"
                                class="wcm-nav-item {{ $selectedBlockIndex === $index ? 'is-active' : '' }}">
                            <span class="wcm-nav-main">
                                <span class="wcm-nav-title">
                                    {{ $block['title'] ?? \Illuminate\Support\Str::headline($block['type'] ?? 'section') }}
                                </span>
                            </span>
                            <span class="wcm-meta">{{ $block['type'] ?? '' }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Selected block content fields --}}
                @if(!is_null($selectedBlockIndex) && isset($blocks[$selectedBlockIndex]))
                    @php
                        $data = $selectedBlockData;
                        $scalars = collect(['title','description','cta_text','cta_url','secondary_cta_text','secondary_cta_url','principal_name','principal_title','mission','vision','address','phone','email','image_url','video_url','map_url'])
                            ->filter(fn($k) => array_key_exists($k, $data))->values()->all();
                        $collections = collect(['items'=>['title'=>'Title','desc'=>'Description'],'features'=>['title'=>'Title','desc'=>'Description'],'faqs'=>['q'=>'Question','a'=>'Answer'],'testimonials'=>['quote'=>'Quote','name'=>'Name','role'=>'Role'],'images'=>['url'=>'Image URL','caption'=>'Caption label'],'logos'=>['name'=>'Partner name','logo_url'=>'Logo URL']])
                            ->filter(fn($f, $k) => isset($data[$k]) && is_array($data[$k]));
                    @endphp

                    <div class="wcm-card p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm wcm-heading">
                                {{ __('Edit content ·') }} <span class="wcm-accent">{{ \Illuminate\Support\Str::headline($block['type'] ?? 'section') }}</span>
                            </h3>
                        </div>

                        @foreach($scalars as $field)
                            @php
                                $isText = in_array($field, ['description','mission','vision']);
                                $isImage = in_array($field, ['image_url']);
                                $isUrl = in_array($field, ['cta_url','secondary_cta_url','video_url','map_url']);
                            @endphp
                            <div class="space-y-1">
                                <label class="wcm-label block">
                                    {{ $isImage ? 'Section photo' : \Illuminate\Support\Str::headline($field) }}
                                </label>
                                @if($isImage)
                                    <input type="text" wire:model.live="selectedBlockData.{{ $field }}" placeholder="Image URL" class="wcm-input">
                                    <div class="flex items-center gap-2 mt-1">
                                        <input type="file" wire:model.live="tempImage" accept="image/*" class="wcm-input py-1 wcm-tiny" title="Upload a new photo">
                                        <button type="button" wire:click="attachUploadedImage('{{ $field }}')" class="wcm-btn wcm-btn-secondary wcm-tiny">{{ __('Upload') }}</button>
                                    </div>
                                @elseif($isText)
                                    @include('modules.cms.richtext', [
                                        'rtKey' => ($blocks[$selectedBlockIndex]['id'] ?? $selectedPageId) . '-' . $field,
                                        'rtPath' => $field,
                                        'rtValue' => $data[$field] ?? '',
                                        'rtPlaceholder' => 'Write ' . strtolower(\Illuminate\Support\Str::headline($field)) . '… supports bold, italic, lists & emoji',
                                    ])
                                @else
                                    <input type="{{ $isUrl ? 'url' : 'text' }}" wire:model.live="selectedBlockData.{{ $field }}" class="wcm-input">
                                @endif
                            </div>
                        @endforeach

                        @foreach($collections as $collection => $fields)
                            <div class="border-t wcm-pane pt-4 space-y-2">
                                <p class="wcm-eyebrow wcm-accent">{{ \Illuminate\Support\Str::headline($collection) }}</p>
                                @foreach($data[$collection] as $itemIdx => $item)
                                    <div class="rounded-xl border wcm-pane wcm-subtle p-3 space-y-2">
                                        <span class="wcm-meta">Item #{{ $itemIdx + 1 }}</span>
                                        @foreach($fields as $field => $label)
                                            @if($field === 'url')
                                                <div class="space-y-1">
                                                    <label class="wcm-label block">{{ $label }}</label>
                                                    <input type="text" wire:model.live="selectedBlockData.{{ $collection }}.{{ $itemIdx }}.{{ $field }}" class="wcm-input">
                                                    <input type="file" wire:model.live="tempImage" accept="image/*" class="wcm-input py-1 wcm-tiny">
                                                    <button type="button" wire:click="attachUploadedImage('{{ $collection }}.{{ $itemIdx }}.{{ $field }}')" class="wcm-btn wcm-btn-secondary wcm-tiny">{{ __('Upload photo') }}</button>
                                                </div>
                                            @elseif(in_array($field, ['desc', 'a', 'quote']))
                                                <div class="space-y-1">
                                                    <label class="wcm-label block">{{ $label }}</label>
                                                    @include('modules.cms.richtext', [
                                                        'rtKey' => ($blocks[$selectedBlockIndex]['id'] ?? $selectedPageId) . '-' . $collection . '-' . $itemIdx . '-' . $field,
                                                        'rtPath' => $collection . '.' . $itemIdx . '.' . $field,
                                                        'rtValue' => $item[$field] ?? '',
                                                        'rtPlaceholder' => 'Write ' . strtolower($label) . '… supports bold, italic, lists & emoji',
                                                    ])
                                                </div>
                                            @else
                                                <div class="space-y-1">
                                                    <label class="wcm-label block">{{ $label }}</label>
                                                    <input type="text" wire:model.live="selectedBlockData.{{ $collection }}.{{ $itemIdx }}.{{ $field }}" class="wcm-input">
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        @if(empty($scalars) && $collections->isEmpty())
                            <p class="text-xs wcm-muted py-4">{{ __('This section has no editable content.') }}</p>
                        @endif
                    </div>
                @else
                    <div class="wcm-card wcm-empty text-center text-sm wcm-muted">
                        {{ __('Select a section on this page to edit its content.') }}
                    </div>
                @endif
            </section>

            {{-- Live preview pane --}}
            <section class="wcm-divider-l bg-white flex flex-col">
                <div class="flex items-center justify-between gap-2 px-4 py-2.5 border-b wcm-pane">
                    <span class="wcm-eyebrow">{{ __('Live preview') }}</span>
                    <div class="flex items-center gap-1">
                        <button x-on:click="previewTab = 'split'" class="wcm-chip" :class="previewTab==='split' ? 'is-active' : ''">{{ __('Split') }}</button>
                        <button x-on:click="previewTab = 'preview'" class="wcm-chip" :class="previewTab==='preview' ? 'is-active' : ''">{{ __('Full') }}</button>
                        <button wire:click="$set('previewSize', 'full')" class="wcm-chip" :class="{{ json_encode($previewSize) }}==='full' ? 'is-active' : ''">{{ __('Desktop') }}</button>
                        <button wire:click="$set('previewSize', 'tablet')" class="wcm-chip" :class="{{ json_encode($previewSize) }}==='tablet' ? 'is-active' : ''">{{ __('Tablet') }}</button>
                        <button wire:click="$set('previewSize', 'mobile')" class="wcm-chip" :class="{{ json_encode($previewSize) }}==='mobile' ? 'is-active' : ''">{{ __('Mobile') }}</button>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-4 wcm-preview-stage" x-show="previewTab !== 'split'" x-cloak>
                    <div class="mx-auto wcm-preview-{{ $previewSize }} transition-all duration-300">
                        <div style="{{ $this->previewVars() }}" class="wcm-device">
                            @foreach($blocks as $block)
                                @include('modules.cms.sections.preview-block', ['block' => $block, 'theme' => $theme, 'stats' => $stats, 'news' => $news, 'events' => $events, 'staff' => $staff])
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-4 wcm-preview-stage" x-show="previewTab === 'split'" x-cloak>
                    <div class="mx-auto wcm-preview-{{ $previewSize }} transition-all duration-300">
                        <div style="{{ $this->previewVars() }}" class="wcm-device">
                            @foreach($blocks as $block)
                                @include('modules.cms.sections.preview-block', ['block' => $block, 'theme' => $theme, 'stats' => $stats, 'news' => $news, 'events' => $events, 'staff' => $staff])
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endif
</div>
