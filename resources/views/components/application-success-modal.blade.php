@props(['applicationNumber' => null, 'studentName' => null])

<div 
    x-data="{ 
        show: true,
        applicationNumber: '{{ $applicationNumber }}',
        studentName: '{{ $studentName }}',
        timer: 10
    }"
    x-init="
        let interval = setInterval(() => {
            if (timer > 0) {
                timer--
            } else {
                show = false
                clearInterval(interval)
            }
        }, 1000)
        
        // Trigger confetti on load
        setTimeout(() => {
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 50,
                    spread: 70,
                    origin: { y: 0.6 }
                })
            }
        }, 200)
    "
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm"
        x-show="show"
        @click="show = false"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all border border-gray-200 dark:border-gray-700"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            <!-- Close button -->
            <button 
                @click="show = false"
                class="absolute top-3 right-3 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="p-6">
                <!-- Success Animation -->
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30 animate-pulse">
                        <svg class="h-12 w-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ __('Application Submitted! 🎉') }}
                    </h3>
                    
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        {{ __('Your online application has been submitted successfully!') }}
                    </p>
                    
                    <!-- Tracking Reference -->
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                            {{ __('Application Tracking Ref') }}
                        </p>
                        <p class="text-lg font-mono font-bold text-blue-700 dark:text-blue-300 mt-1" x-text="applicationNumber">
                            {{ __('APP-2026-FQLBO8') }}
                        </p>
                    </div>

                    @if($studentName)
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Thank you,') }} <strong class="text-gray-900 dark:text-white" x-text="studentName"></strong>{{ __('!') }}
                        </p>
                    @endif

                    <!-- Next Steps -->
                    <div class="mt-6 text-left bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                            <span class="text-xl mr-2">{{ __('📋') }}</span> 
                            {{ __('What happens next?') }}
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-start">
                                <span class="text-blue-500 mr-2">{{ __('•') }}</span>
                                <span>We'll review your application within 3-5 business days</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-500 mr-2">{{ __('•') }}</span>
                                <span>You'll receive an email confirmation shortly</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-500 mr-2">{{ __('•') }}</span>
                                <span>{{ __('Check your email for status updates') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                        <a 
                            href="{{ route('filament.app.resources.applications.index') }}"
                            class="inline-flex justify-center items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition text-sm font-medium"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            </svg>
                            {{ __('View All Applications') }}
                        </a>
                        
                        <button 
                            @click="show = false"
                            class="inline-flex justify-center items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-sm font-medium"
                        >
                            {{ __('Close') }}
                        </button>
                    </div>

                    <!-- Auto-close timer -->
                    <div class="mt-4 flex items-center justify-center gap-2">
                        <div class="w-16 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-primary-500 rounded-full transition-all duration-1000"
                                x-bind:style="'width: ' + (timer / 10 * 100) + '%'"
                            ></div>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-gray-500" x-text="timer + 's'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>