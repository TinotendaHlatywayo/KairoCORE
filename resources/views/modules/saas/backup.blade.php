<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Platform Backups & Disasters Recovery') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Automate cloud backup runs, monitor physical snapshot directories, and run data recovery states.') }}</p>
        </div>
        <button wire:click="triggerBackup" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <x-heroicon-s-arrow-path class="h-4 w-4" />
            {{ __('Trigger Database Snapshot') }}
        </button>
    </div>

    <!-- Active Snapshots Ledger Table -->
    <div class="rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ __('Active Database Snapshot Backups') }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                        <th class="py-3 px-4">{{ __('Filename') }}</th>
                        <th class="py-3 px-4">{{ __('Location') }}</th>
                        <th class="py-3 px-4">{{ __('File Size') }}</th>
                        <th class="py-3 px-4">{{ __('Creation Date') }}</th>
                        <th class="py-3 px-4">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-sm text-gray-800 dark:text-gray-200">
                    @forelse($backups as $file)
                        <tr>
                            <td class="py-4 px-4 font-mono text-xs text-gray-900 dark:text-indigo-400">{{ $file['name'] }}</td>
                            <td class="py-4 px-4">S3 Storage (AWS)</td>
                            <td class="py-4 px-4">{{ $file['size'] }}</td>
                            <td class="py-4 px-4">{{ $file['date'] }}</td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                    {{ __('Synced') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400">{{ __('No backup records currently on disk. Click the button above to generate.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>