<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[60vh] p-8 text-center">
        <!-- Success Animation -->
        <div class="relative mb-8">
            <div class="w-32 h-32 rounded-full bg-success-100 dark:bg-success-900/20 flex items-center justify-center mx-auto animate-bounce">
                <div class="w-24 h-24 rounded-full bg-success-200 dark:bg-success-900/40 flex items-center justify-center">
                    <svg class="w-16 h-16 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <div class="absolute -top-2 -right-2">
                <span class="text-4xl">{{ __('🎉') }}</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {{ __('Application Submitted Successfully!') }}
            </h1>
            
            @if($applicationNumber ?? false)
                <p class="text-xl text-gray-600 dark:text-gray-400 mb-1">
                    {{ __('Application #:') }} <strong class="text-primary-600 dark:text-primary-400">{{ $applicationNumber }}</strong>
                </p>
            @endif
            
            @if($studentName ?? false)
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('Thank you,') }} <strong>{{ $studentName }}</strong>{{ __('!') }}
                </p>
            @endif

            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Current Status') }}</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ __('Pending Review') }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Response Time') }}</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ __('3-5 Business Days') }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Confirmation') }}</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ __('Email Sent') }}</p>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 text-left mb-8">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('📋 Next Steps') }}</h3>
                <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                    <li class="flex items-start">
                        <span class="text-blue-500 mr-2">{{ __('1.') }}</span>
                        <span>We'll review your application within 3-5 business days.</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-500 mr-2">{{ __('2.') }}</span>
                        <span>You'll receive an email confirmation shortly.</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-500 mr-2">{{ __('3.') }}</span>
                        <span>{{ __('Check your email for updates on the application status.') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('filament.app.resources.applications.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    {{ __('View All Applications') }}
                </a>
                
                <a href="{{ route('filament.app.resources.applications.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('Submit Another Application') }}
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
        
        <script>
            // Auto-trigger confetti when page loads
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof confetti !== 'undefined') {
                    // First burst
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                    
                    // Second burst after a delay
                    setTimeout(() => {
                        confetti({
                            particleCount: 50,
                            spread: 100,
                            origin: { y: 0.5 }
                        });
                    }, 500);
                }
            });
            
            // Listen for Livewire events
            document.addEventListener('livewire:init', function () {
                Livewire.on('application-submitted', function () {
                    if (typeof confetti !== 'undefined') {
                        confetti({
                            particleCount: 100,
                            spread: 70,
                            origin: { y: 0.6 }
                        });
                    }
                });
            });
        </script>
    @endpush
</x-filament-panels::page>