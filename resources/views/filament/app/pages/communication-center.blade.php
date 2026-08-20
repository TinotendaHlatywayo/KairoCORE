<x-filament-panels::page>
    @php
        $tints = [
            'emerald' => '#10b981', 'blue' => '#3b82f6', 'amber' => '#f59e0b',
            'violet' => '#8b5cf6', 'slate' => '#64748b', 'rose' => '#f43f5e',
        ];
    @endphp

    <div class="sc-comm-overview">
        @if (count($kpis))
            <div class="sc-kpi-grid">
                @foreach ($kpis as $kpi)
                    <a href="{{ data_get($kpi, 'link', '#') }}" class="sc-kpi" data-tint="{{ $kpi['tint'] ?? 'slate' }}">
                        <span class="sc-kpi-icon">@svg($kpi['icon'], 'h-5 w-5')</span>
                        <span class="sc-kpi-body">
                            <span class="sc-kpi-value">{{ $kpi['value'] }}</span>
                            <span class="sc-kpi-label">{{ $kpi['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="sc-comm-grid">
            <div class="sc-panel">
                <div class="sc-panel-head">
                    <h3 class="sc-panel-title">{{ __('Recent Announcements') }}</h3>
                    <a class="sc-panel-link" href="{{ \App\Filament\App\Resources\AnnouncementResource::getUrl('index') }}">{{ __('View all') }}</a>
                </div>
                @forelse ($recentAnnouncements as $item)
                    <div class="sc-row">
                        <span class="sc-row-title">{{ $item->title }}</span>
                        <span class="sc-row-meta">{{ $item->published_at?->format('M d, Y') ?? 'Draft' }}</span>
                    </div>
                @empty
                    <p class="sc-empty">{{ __('No announcements yet. Publish one to keep your school community informed.') }}</p>
                @endforelse
            </div>

            <div class="sc-panel">
                <div class="sc-panel-head">
                    <h3 class="sc-panel-title">{{ __('Upcoming Events') }}</h3>
                    <a class="sc-panel-link" href="{{ \App\Filament\App\Resources\EventCalendarResource::getUrl('index') }}">{{ __('View calendar') }}</a>
                </div>
                @forelse ($upcomingEvents as $item)
                    <div class="sc-row">
                        <span class="sc-row-title">{{ $item->title }}</span>
                        <span class="sc-row-meta">{{ $item->start_time?->format('M d · H:i') }}</span>
                    </div>
                @empty
                    <p class="sc-empty">{{ __('No upcoming events scheduled.') }}</p>
                @endforelse
            </div>

            <div class="sc-panel">
                <div class="sc-panel-head">
                    <h3 class="sc-panel-title">{{ __('Recent Conversations') }}</h3>
                    <a class="sc-panel-link" href="{{ \App\Filament\App\Resources\ChatThreadResource::getUrl('index') }}">{{ __('Open chat') }}</a>
                </div>
                @forelse ($recentThreads as $item)
                    <div class="sc-row">
                        <span class="sc-row-title">{{ $item->name ?? 'Untitled thread' }}</span>
                        <span class="sc-row-meta">{{ $item->messages_count }} messages</span>
                    </div>
                @empty
                    <p class="sc-empty">{{ __('No conversations yet. Start a chat thread to connect with your community.') }}</p>
                @endforelse
            </div>

            <div class="sc-panel">
                <div class="sc-panel-head">
                    <h3 class="sc-panel-title">{{ __('SchoolCore Messages') }}</h3>
                    <a class="sc-panel-link" href="{{ \App\Filament\App\Resources\PlatformInboxResource::getUrl('index') }}">{{ __('Open inbox') }}</a>
                </div>
                @forelse ($platformMessages as $item)
                    <div class="sc-row">
                        <span class="sc-row-title">{{ $item->subject ?? 'Message' }}</span>
                        <span class="sc-row-meta">{{ $item->created_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="sc-empty">{{ __('No messages from the SchoolCore platform team.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        .sc-comm-overview { display: grid; gap: 1.25rem; }

        .sc-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.75rem;
        }

        .sc-kpi {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.125rem;
            background: var(--gray-50, #f9fafb);
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 0.75rem;
            text-decoration: none;
            transition: background-color 150ms ease, border-color 150ms ease;
        }

        .sc-kpi:hover {
            background: var(--gray-100, #f3f4f6);
            border-color: var(--gray-300, #d1d5db);
        }

        .dark .sc-kpi {
            background: var(--gray-800, #1f2937);
            border-color: var(--gray-700, #374151);
        }

        .dark .sc-kpi:hover {
            background: var(--gray-700, #374151);
            border-color: var(--gray-600, #4b5563);
        }

        .sc-kpi-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            flex: none;
            border-radius: 0.625rem;
            color: #fff;
        }

        .sc-kpi[data-tint="emerald"] .sc-kpi-icon { background: #10b981; }
        .sc-kpi[data-tint="blue"] .sc-kpi-icon { background: #3b82f6; }
        .sc-kpi[data-tint="amber"] .sc-kpi-icon { background: #f59e0b; }
        .sc-kpi[data-tint="violet"] .sc-kpi-icon { background: #8b5cf6; }
        .sc-kpi[data-tint="slate"] .sc-kpi-icon { background: #64748b; }
        .sc-kpi[data-tint="rose"] .sc-kpi-icon { background: #f43f5e; }

        .sc-kpi-body { display: flex; flex-direction: column; min-width: 0; }

        .sc-kpi-value {
            font-size: 1.375rem;
            font-weight: 700;
            line-height: 1.1;
            color: var(--gray-900, #111827);
            letter-spacing: -0.01em;
        }

        .dark .sc-kpi-value { color: var(--gray-50, #f9fafb); }

        .sc-kpi-label {
            margin-top: 0.125rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--gray-500, #6b7280);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .sc-kpi-label { color: var(--gray-400, #9ca3af); }

        .sc-comm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 0.75rem;
        }

        .sc-panel {
            padding: 1rem 1.125rem;
            background: var(--gray-50, #f9fafb);
            border: 1px solid var(--gray-200, #e5e7eb);
            border-radius: 0.75rem;
        }

        .dark .sc-panel {
            background: var(--gray-800, #1f2937);
            border-color: var(--gray-700, #374151);
        }

        .sc-panel-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.625rem;
        }

        .sc-panel-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--gray-800, #1f2937);
        }

        .dark .sc-panel-title { color: var(--gray-100, #f3f4f6); }

        .sc-panel-link {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-600, #15803d);
            text-decoration: none;
        }

        .dark .sc-panel-link { color: var(--primary-400, #4ade80); }

        .sc-panel-link:hover { text-decoration: underline; }

        .sc-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-top: 1px solid var(--gray-200, #e5e7eb);
        }

        .dark .sc-row { border-top-color: var(--gray-700, #374151); }

        .sc-row-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--gray-700, #374151);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .sc-row-title { color: var(--gray-200, #e5e7eb); }

        .sc-row-meta {
            flex: none;
            font-size: 0.75rem;
            color: var(--gray-500, #6b7280);
        }

        .dark .sc-row-meta { color: var(--gray-400, #9ca3af); }

        .sc-empty {
            padding: 0.5rem 0;
            font-size: 0.8125rem;
            line-height: 1.5;
            color: var(--gray-500, #6b7280);
        }

        .dark .sc-empty { color: var(--gray-400, #9ca3af); }
    </style>
</x-filament-panels::page>
