<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <flux:heading size="lg" level="2">{{ __('Academic Setup Wizard') }}</flux:heading>
        <flux:text class="mt-1">
            Step {{ $currentStep }} of {{ $totalSteps }}: {{ $stepDetails['title'] ?? 'Unknown' }}
        </flux:text>
    </div>

    <flux:separator class="my-6" />

    <div class="mb-6">
        <flux:progress :value="$progress" max="100" />
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:heading size="md">{{ $stepDetails['title'] ?? '' }}</flux:heading>
            <flux:text>{{ $stepDetails['description'] ?? '' }}</flux:text>
        </div>

        @if ($isCompleted)
            <flux:badge color="pill" class="text-green-500 mb-4">{{ __('Completed') }}</flux:badge>
        @else
            <flux:badge color="pill" class="text-blue-500 mb-4">{{ __('In Progress') }}</flux:badge>
        @endif

        @if ($stepInfo['dependencies'] ?? [])
            <flux:text size="sm" class="mb-4">
                <strong>{{ __('Prerequisites:') }}</strong>
                {{ implode(', ', $stepInfo['dependencies']) }}
            </flux:text>
        @endif

        @php
            $hasDependencies = $stepInfo['dependencies'] ?? [];
            $dependenciesMet = true;
            foreach ($hasDependencies as $dep) {
                if (!$engine->checkStepCompletion($dep)) {
                    $dependenciesMet = false;
                    break;
                }
            }
        @endphp

        @if (!$dependenciesMet && !empty($hasDependencies))
            <flux:callout color="warning" class="mb-4">
                <flux:callout.heading>{{ __('Dependencies Not Met') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Complete the prerequisite steps before proceeding.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="mt-6">
            <flux:link :href="route($engine->getStepRoute($currentKey) ?? '#')"
                       wire:navigate>
                <flux:button>
                    Configure {{ $stepDetails['title'] ?? 'this step' }}
                </flux:button>
            </flux:link>
        </div>
    </flux:card>

    <div class="flex justify-between mt-6">
        <flux:button variant="outline" wire:click="previousStep"
                     {{ $currentStep <= 1 ? 'disabled' : '' }}>
            {{ __('Previous') }}
        </flux:button>
        <flux:button variant="outline" wire:click="nextStep"
                     {{ $currentStep >= $totalSteps ? 'disabled' : '' }}>
            {{ __('Next') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-6">
        @foreach ($availableSteps as $idx => $stepKey)
            @php
                $stepNum = $idx + 1;
                $status = $engine->checkStepCompletion($stepKey);
                $bgColor = match($status) {
                    'completed' => 'bg-green-100 text-green-800',
                    'pending' => !$engine->getBlockedSteps() || !in_array($stepKey, $engine->getBlockedSteps()) ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800',
                };
            @endphp
            <flux:button
                variant="ghost"
                class="{{ $bgColor }} rounded-lg py-2 px-3 text-xs font-medium cursor-pointer"
                wire:click="goToStep({{ $stepNum }})"
            >
                {{ $stepNum }}. {{ AcademicWorkflowEngine::SETUP_WORKFLOW[$stepKey]['title'] ?? $stepKey }}
            </flux:button>
        @endforeach
    </div>
</div>
