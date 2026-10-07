<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" wire:poll.5s="refreshBackupsList">
        <!-- Create & Upload panel -->
        <div class="space-y-6">
            <x-filament::section
                heading="{{ __('Create a backup') }}"
                description="{{ __('Capture the whole platform, a single tenant, or several chosen tenants into a recovery archive.') }}"
            >
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Backup scope') }}</label>
                        <div class="grid grid-cols-3 gap-2">
                            <x-filament::button
                                size="sm"
                                :color="$backupScope === 'system' ? 'primary' : 'gray'"
                                wire:click="$set('backupScope', 'system')"
                            >
                                {{ __('System') }}
                            </x-filament::button>
                            <x-filament::button
                                size="sm"
                                :color="$backupScope === 'tenant' ? 'primary' : 'gray'"
                                wire:click="$set('backupScope', 'tenant')"
                            >
                                {{ __('Single') }}
                            </x-filament::button>
                            <x-filament::button
                                size="sm"
                                :color="$backupScope === 'selected' ? 'primary' : 'gray'"
                                wire:click="$set('backupScope', 'selected')"
                            >
                                {{ __('Selected') }}
                            </x-filament::button>
                        </div>
                    </div>

                    @if ($backupScope === 'tenant')
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="tenantSchoolId">
                                <option value="">{{ __('Select a tenant…') }}</option>
                                @foreach ($tenantOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    @elseif ($backupScope === 'selected')
                        <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2 text-sm dark:border-gray-700">
                            @foreach ($tenantOptions as $id => $name)
                                <label class="flex cursor-pointer items-center gap-2 rounded p-1 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <input type="checkbox" wire:model="selectedTenantIds" value="{{ $id }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-gray-700 dark:text-gray-200">{{ $name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

<div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Comment (optional)') }}</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model="backupNotes" placeholder="{{ __('e.g. Before the new term') }}" />
                        </x-filament::input.wrapper>
                    </div>

                    <x-filament::button
                        wire:click="triggerPlatformBackup"
                        icon="heroicon-o-cloud-arrow-up"
                        size="lg"
                        class="w-full"
                    >
                        {{ $backupScope === 'tenant' ? __('Generate Tenant Backup') : ($backupScope === 'selected' ? __('Generate Selected Tenants Backup') : __('Generate Full Platform Backup')) }}
                    </x-filament::button>

                    <p class="text-xs text-gray-500">
                        {{ __('Every archive is saved to the recovery vault below, where you can download it to your PC or restore it.') }}
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section
                heading="{{ __('Restore from a file') }}"
                description="{{ __('Restore a recovery archive you are holding locally (.zip).') }}"
            >
                <form wire:submit.prevent="restoreFromUploadedBackup" class="space-y-4">
                    {{ $this->form }}
                    <div class="grid grid-cols-2 gap-2">
                        <x-filament::button type="submit" color="danger" icon="heroicon-o-arrow-path" class="w-full">
                            {{ __('Import & Restore') }}
                        </x-filament::button>
                        <x-filament::button type="button" color="gray" wire:click="uploadExternalBackup" icon="heroicon-o-arrow-up-tray" class="w-full">
                            {{ __('Import only') }}
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        </div>

        <!-- Vault -->
        <div class="space-y-6 lg:col-span-2">
            <x-filament::section
                heading="{{ __('Restore from the server') }}"
                description="{{ __('Pick a backup from the vault (shown with its date and comment) and restore it.') }}"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="restoreBackupId">
                                <option value="">{{ __('Select a backup…') }}</option>
                                @foreach ($backupsList as $bk)
                                    <option value="{{ $bk['id'] }}">
                                        {{ \Illuminate\Support\Carbon::parse($bk['created_at'])->format('Y-m-d H:i') }}
                                        — {{ $bk['scope'] === 'tenant' ? ($bk['school_name'] ?? __('Tenant')) : __('System') }}
                                        @if (! empty($bk['notes'])) — {{ $bk['notes'] }} @endif
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button
                        color="danger"
                        icon="heroicon-o-arrow-path"
                        wire:click="restoreSelectedBackup"
                        wire:confirm="{{ __('WARNING: This will overwrite current data with the selected backup. Proceed?') }}"
                    >
                        {{ __('Restore selected') }}
                    </x-filament::button>
                </div>
            </x-filament::section>

            <x-filament::section heading="{{ __('Recent restore activity') }}">
                @forelse ($restoreLogsList as $log)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0 dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="truncate text-gray-700 dark:text-gray-200">{{ $log['backup'] ?? __('Backup') }}</div>
                            <div class="text-xs text-gray-400">{{ $log['created_at'] ?? '' }}</div>
                            @if (! empty($log['error']))
                                <div class="text-xs text-rose-500">{{ \Illuminate\Support\Str::limit($log['error'], 120) }}</div>
                            @endif
                        </div>
                        @php $st = $log['status']; @endphp
                        <span @class([
                            'shrink-0 rounded px-2 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400' => in_array($st, ['pending', 'processing']),
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' => $st === 'completed',
                            'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400' => $st === 'failed',
                        ])>{{ $st }}</span>
                    </div>
                @empty
                    <p class="py-2 text-sm text-gray-400">{{ __('No restores have been run yet.') }}</p>
                @endforelse
            </x-filament::section>

            <x-filament::section heading="{{ __('Platform Recovery Vault') }}">
                @if (empty($backupsList))
                    <div class="py-12 text-center text-gray-400">
                        <x-heroicon-o-cloud-arrow-down class="mx-auto mb-3 h-12 w-12" />
                        <p class="text-sm">{{ __('No backups generated yet.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800">
                                    <th class="px-2 py-3">{{ __('Date / Comment') }}</th>
                                    <th class="py-3 px-2">{{ __('Scope') }}</th>
                                    <th class="py-3 px-2">{{ __('Size') }}</th>
                                    <th class="py-3 px-2">{{ __('Status') }}</th>
                                    <th class="py-3 px-2 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                @foreach ($backupsList as $bk)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                        <td class="px-2 py-3">
                                            <div class="font-medium text-gray-800 dark:text-gray-200">
                                                {{ \Illuminate\Support\Carbon::parse($bk['created_at'])->format('d M Y, H:i') }}
                                            </div>
                                            @if (! empty($bk['notes']))
                                                <div class="text-xs text-gray-500">{{ $bk['notes'] }}</div>
                                            @endif
                                            <div class="text-[10px] text-gray-400">{{ $bk['filename'] }}</div>
                                        </td>
                                        <td class="px-2 py-3">
                                            @if (($bk['scope'] ?? 'system') === 'tenant')
                                                <span class="inline-flex rounded bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800 dark:bg-sky-950/40 dark:text-sky-400">{{ __('Tenant') }}</span>
                                                <div class="text-[10px] text-gray-400">{{ $bk['school_name'] ?? ('#'.$bk['school_id']) }}</div>
                                            @else
                                                <span class="inline-flex rounded bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-800 dark:bg-violet-950/40 dark:text-violet-400">{{ __('System') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-3 text-gray-600 dark:text-gray-400">
                                            {{ round(($bk['size_bytes'] ?? 0) / (1024 * 1024), 2) }} MB
                                        </td>
                                        <td class="px-2 py-3">
                                            @php $status = $bk['status'] ?? 'completed'; @endphp
                                            <span @class([
                                                'inline-flex rounded px-2 py-0.5 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' => $status === 'completed',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400' => $status === 'pending',
                                                'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400' => $status === 'failed',
                                            ])>{{ $status }}</span>
                                        </td>
                                        <td class="px-2 py-3 text-right">
                                            <div class="flex justify-end gap-1">
                                                <x-filament::icon-button
                                                    :href="route('platform.backups.download', $bk['id'])"
                                                    tag="a"
                                                    icon="heroicon-o-arrow-down-tray"
                                                    color="primary"
                                                    tooltip="{{ __('Download') }}"
                                                />
                                                @if ($status === 'completed')
                                                    <x-filament::icon-button
                                                        wire:click="executePlatformRestore({{ $bk['id'] }})"
                                                        wire:confirm="{{ (($bk['scope'] ?? 'system') === 'tenant') ? __('WARNING: This will replace this tenant\'s current rows with the backup. Proceed?') : __('WARNING: This will overwrite and recreate all tables and data. Proceed?') }}"
                                                        icon="heroicon-o-arrow-path"
                                                        color="danger"
                                                        tooltip="{{ __('Restore') }}"
                                                    />
                                                @endif
                                                <x-filament::icon-button
                                                    wire:click="deleteBackupRecord({{ $bk['id'] }})"
                                                    wire:confirm="{{ __('Delete this backup archive?') }}"
                                                    icon="heroicon-o-trash"
                                                    color="gray"
                                                    tooltip="{{ __('Delete') }}"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>