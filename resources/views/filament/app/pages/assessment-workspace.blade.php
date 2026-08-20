<x-filament-panels::page>
    <div class="space-y-6">
        
        <!-- Header Selection Filters Form -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            {{ $this->form }}
        </div>

        @if(!empty($this->data['section_id'] ?? null) && !empty($this->data['subject_id'] ?? null))
            @php $kanbanData = $this->getKanbanCards(); @endphp

            <!-- Horizontal Scrolling Kanban Board -->
            <div class="flex space-x-4 overflow-x-auto pb-6" style="min-height: 480px;">
                @foreach($this::$workflowStates as $stateKey => $config)
                    <div class="flex-shrink-0 w-80 bg-gray-50 p-4 rounded-xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700" style="min-height: 400px;">
                        
                        <!-- Column Header -->
                        <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-2 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center">
                                <span class="w-2.5 h-2.5 rounded-full mr-2 bg-{{ $config['color'] }}"></span style="color: gray;">
                                {{ $config['title'] }}
                            </h3>
                            <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full font-semibold dark:bg-gray-700 dark:text-gray-300">
                                {{ count($kanbanData[$stateKey] ?? []) }}
                            </span>
                        </div>

                        <!-- Cards List -->
                        <div class="space-y-3">
                            @forelse($kanbanData[$stateKey] ?? [] as $card)
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm space-y-3 dark:bg-gray-900 dark:border-gray-800 transition hover:shadow-md">
                                    
                                    <!-- Title & Date -->
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-gray-100">{{ $card['name'] }}</h4>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Date: {{ $card['date'] }}</span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-[10px] font-bold text-gray-600 dark:text-gray-400">
                                            <span>{{ __('Progress:') }}</span>
                                            <span>{{ $card['marked_progress'] }} ({{ $card['progress_percent'] }}%)</span>
                                        </div>
                                        <div class="w-full bg-gray-200 h-1.5 rounded-full dark:bg-gray-700 overflow-hidden">
                                            <div class="bg-success h-full" style="width: {{ $card['progress_percent'] }}%; transition: width 0.3s ease;"></div>
                                        </div>
                                    </div>

                                    <!-- Performance Analytics -->
                                    <div class="grid grid-cols-2 gap-2 bg-gray-50 p-2.5 rounded-lg text-[10px] border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                                        <div><strong>{{ __('AVG:') }}</strong> {{ $card['avg'] }}%</div>
                                        <div><strong>{{ __('Highest:') }}</strong> {{ $card['highest'] }}%</div>
                                        <div><strong>{{ __('Lowest:') }}</strong> {{ $card['lowest'] }}%</div>
                                        <div class="{{ $card['missing'] > 0 ? 'text-danger font-bold' : 'text-success' }}">
                                            <strong>{{ __('Missing:') }}</strong> {{ $card['missing'] }}
                                        </div>
                                    </div>

                                    <!-- Action state shifters -->
                                    <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-800">
                                        
                                        <!-- Open standard Mark Entry grid link (Dynamically routed to prevent broken indices) -->
                                        <a href="{{ $card['record_marks_url'] }}" 
                                           class="text-[10px] font-semibold text-info hover:underline flex items-center">
                                            {{ __('📝 Record Marks') }}
                                        </a>

                                        <!-- Status Transitions triggers -->
                                        <div class="flex space-x-1">
                                            @if($stateKey === 'draft')
                                                <button wire:click="moveCard({{ $card['id'] }}, 'scheduled')" class="text-[10px] font-bold text-success hover:underline">{{ __('📆 Schedule') }}</button>
                                            @elseif($stateKey === 'scheduled')
                                                <button wire:click="moveCard({{ $card['id'] }}, 'open')" class="text-[10px] font-bold text-success hover:underline">{{ __('🔓 Open') }}</button>
                                            @elseif($stateKey === 'open')
                                                <button wire:click="moveCard({{ $card['id'] }}, 'marking')" class="text-[10px] font-bold text-warning hover:underline">{{ __('✍️ Mark') }}</button>
                                            @elseif($stateKey === 'marking')
                                                <button wire:click="moveCard({{ $card['id'] }}, 'submitted')" class="text-[10px] font-bold text-success hover:underline">{{ __('📤 Submit') }}</button>
                                            @elseif($stateKey === 'submitted')
                                                <button wire:click="moveCard({{ $card['id'] }}, 'reviewed')" class="text-[10px] font-bold text-success hover:underline">{{ __('✅ Review') }}</button>
                                            @elseif($stateKey === 'reviewed')
                                                <button wire:click="moveCard({{ $card['id'] }}, 'locked')" class="text-[10px] font-bold text-danger hover:underline">{{ __('🔒 Lock') }}</button>
                                            @elseif($stateKey === 'locked' && $card['is_complete'])
                                                <button wire:click="moveCard({{ $card['id'] }}, 'published')" class="text-[10px] font-bold text-success hover:underline">{{ __('📢 Publish') }}</button>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            @empty
                                <div class="text-center py-6 text-xs text-gray-500 italic dark:text-gray-400">
                                    {{ __('No assessments') }}
                                </div>
                            @endforelse
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center p-12 bg-white rounded-xl border border-gray-200 dark:bg-gray-900 dark:border-gray-800 text-center space-y-2">
                <span class="text-3xl">{{ __('📊') }}</span>
                <h3 class="font-bold text-gray-700 dark:text-gray-300">{{ __('Workspace Inactive') }}</h3>
                <p class="text-xs text-gray-500 max-w-sm dark:text-gray-400">{{ __('Select a Class Stream and Subject from the filters above to load the dynamic assessment board.') }}</p>
            </div>
        @endif

    </div>
</x-filament-panels::page>