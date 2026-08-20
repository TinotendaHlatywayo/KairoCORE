<div 
    x-data="{ 
        show: true,
        applicationNumber: '{{ $applicationNumber ?? '' }}',
        studentName: '{{ $studentName ?? '' }}'
    }"
    x-show="show"
    x-init="setTimeout(() => { show = false }, 10000)"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity"
        x-show="show"
        @click="show = false"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl transition-all"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            <!-- Close button -->
            <button 
                @click="show = false"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Success Animation -->
            <div class="p-6 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                    <svg class="h-12 w-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ __('Application Submitted! 🎉') }}
                </h3>
                
                <p class="text-gray-600 dark:text-gray-400 mb-1">
                    {{ __('Your online application has been submitted successfully!') }}
                </p>
                
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Application Tracking Ref:') }}</p>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400" x-text="applicationNumber">
                        {{ __('APP-2026-FQLBO8') }}
                    </p>
                </div>

                @if($studentName)
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Thank you,') }} <strong x-text="studentName"></strong>{{ __('!') }}
                    </p>
                @endif

                <!-- Next Steps -->
                <div class="mt-6 text-left bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('📋 What happens next?') }}</p>
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
                        href="{{ route('filament.app.resources.applications.edit', $applicationNumber ?? '') }}"
                        class="inline-flex justify-center items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        {{ __('View Application') }}
                    </a>
                    
                    <button 
                        @click="show = false"
                        class="inline-flex justify-center items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{ __('Close') }}
                    </button>
                </div>

                <!-- Auto-close timer -->
                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                    {{ __('This window will close automatically in') }} <span x-text="10"></span> {{ __('seconds') }}
                </p>
            </div>
        </div>
    </div>
</div>