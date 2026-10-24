<x-filament-panels::page>
    @php
        $activeRestore = collect($restoreLogsList)->first(
            fn (array $log) => in_array($log['status'] ?? '', ['pending', 'processing'], true)
        );
    @endphp

    @if ($activeRestore)
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
            <x-filament::loading-indicator class="h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold">{{ __('A restore is running…') }}</p>
                <p class="mt-0.5">
                    {{ __('The list below refreshes every few seconds as the status moves from :from to completed.', ['from' => $activeRestore['status']]) }}
                </p>
            </div>
        </div>
    @endif

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

                    @if ($backupScope === 'system')
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <input type="checkbox" wire:model="includeFiles" class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-sm">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ __('Include uploaded files') }}</span>
                                <span class="mt-0.5 block text-xs text-gray-500">{{ __('Adds every photo, logo, favicon, website image and library document to the archive, so a restore brings back the files as well as the data.') }}</span>
                            </span>
                        </label>
                    @else
                        <p class="text-xs text-gray-500">{{ __('Files are stored once for the whole platform, so tenant archives cover the data only.') }}</p>
                    @endif

                    <x-filament::button
                        wire:click="triggerPlatformBackup"
                        icon="heroicon-o-cloud-arrow-up"
                        size="lg"
                        class="w-full"
                        wire:loading.attr="disabled"
                        wire:target="triggerPlatformBackup"
                    >
                        <span wire:loading.remove wire:target="triggerPlatformBackup">
                            {{ $backupScope === 'tenant' ? __('Generate Tenant Backup') : ($backupScope === 'selected' ? __('Generate Selected Tenants Backup') : __('Generate Full Platform Backup')) }}
                        </span>
                        <span wire:loading wire:target="triggerPlatformBackup" class="inline-flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            {{ __('Starting…') }}
                        </span>
                    </x-filament::button>

                    <p class="text-xs text-gray-500">
                        {{ __('Every archive is saved to the recovery vault below, where you can download it to your PC or restore it.') }}
                        {{ __('Archives with files included carry the photos, logos and documents too, so a restore brings those back as well.') }}
                        {{ __('The .env file is never backed up — keep your secrets out of the archive.') }}
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
                        <x-filament::button
                            type="submit"
                            color="danger"
                            icon="heroicon-o-arrow-path"
                            class="w-full"
                            wire:loading.attr="disabled"
                            wire:target="restoreFromUploadedBackup"
                        >
                            <span wire:loading.remove wire:target="restoreFromUploadedBackup">{{ __('Import & Restore') }}</span>
                            <span wire:loading wire:target="restoreFromUploadedBackup" class="inline-flex items-center gap-2">
                                <x-filament::loading-indicator class="h-4 w-4" />
                                {{ __('Restoring…') }}
                            </span>
                        </x-filament::button>
                        <x-filament::button
                            type="button"
                            color="gray"
                            wire:click="uploadExternalBackup"
                            icon="heroicon-o-arrow-up-tray"
                            class="w-full"
                            wire:loading.attr="disabled"
                            wire:target="uploadExternalBackup"
                        >
                            <span wire:loading.remove wire:target="uploadExternalBackup">{{ __('Import only') }}</span>
                            <span wire:loading wire:target="uploadExternalBackup" class="inline-flex items-center gap-2">
                                <x-filament::loading-indicator class="h-4 w-4" />
                                {{ __('Importing…') }}
                            </span>
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
                                        {{ \Modules\Recovery\Models\PlatformBackup::formatTimestamp($bk['created_at'], 'Y-m-d H:i') }}
                                        — {{ $bk['scope'] === 'tenant' ? ($bk['school_name'] ?? __('Tenant')) : __('System') }}
                                        @if (! empty($bk['notes'])) — {{ $bk['notes'] }} @endif
                                        @if ($bk['status'] === 'completed') — {{ ($bk['include_files'] ?? false) ? __('data + files') : __('data only') }} @endif
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
                        wire:loading.attr="disabled"
                        wire:target="restoreSelectedBackup"
                    >
                        <span wire:loading.remove wire:target="restoreSelectedBackup">{{ __('Restore selected') }}</span>
                        <span wire:loading wire:target="restoreSelectedBackup" class="inline-flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            {{ __('Starting…') }}
                        </span>
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
                            @elseif (! empty($log['summary']))
                                <div class="text-xs text-emerald-600 dark:text-emerald-400">{{ \Illuminate\Support\Str::limit($log['summary'], 140) }}</div>
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
                <div class="mt-3">
                    <x-filament::button size="sm" color="gray" wire:click="toggleRestoreLogExpansion">
                        {{ $showAllRestoreLogs ? __('Show less') : __('Show more') }}
                    </x-filament::button>
                </div>
            </x-filament::section>

            <x-filament::section heading="{{ __('Platform Recovery Vault') }}">
                @if (empty($backupsList) && $vaultStatusFilter === '' && ! $vaultDateFrom && ! $vaultDateTo)
                    <div class="py-12 text-center text-gray-400">
                        <x-heroicon-o-cloud-arrow-down class="mx-auto mb-3 h-12 w-12" />
                        <p class="text-sm">{{ __('No backups generated yet.') }}</p>
                    </div>
                @else
                    <div class="mb-3 flex flex-wrap items-end gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Status') }}</label>
                            <x-filament::input.wrapper class="w-40">
                                <x-filament::input.select wire:model.live="vaultStatusFilter">
                                    <option value="">{{ __('All statuses') }}</option>
                                    <option value="completed">{{ __('completed') }}</option>
                                    <option value="pending">{{ __('pending') }}</option>
                                    <option value="failed">{{ __('failed') }}</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('From date') }}</label>
                            <x-filament::input.wrapper class="w-40">
                                <x-filament::input type="date" wire:model.live="vaultDateFrom" />
                            </x-filament::input.wrapper>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('To date') }}</label>
                            <x-filament::input.wrapper class="w-40">
                                <x-filament::input type="date" wire:model.live="vaultDateTo" />
                            </x-filament::input.wrapper>
                        </div>
                        @if ($vaultStatusFilter !== '' || $vaultDateFrom || $vaultDateTo)
                            <x-filament::button size="sm" color="gray" icon="heroicon-o-x-mark" wire:click="clearVaultFilters">
                                {{ __('Clear filters') }}
                            </x-filament::button>
                        @endif
                    </div>

                    @if ($selectedBackupIds !== [])
                        <div class="mb-3 flex flex-wrap items-center gap-2 rounded-lg bg-primary-50 p-3 dark:bg-primary-950/30">
                            <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                                {{ trans_choice(':count backup selected|:count backups selected', count($selectedBackupIds), ['count' => count($selectedBackupIds)]) }}
                            </span>
                            <x-filament::button size="sm" icon="heroicon-o-arrow-down-tray" wire:click="downloadSelectedBackups">
                                {{ __('Download selected') }}
                            </x-filament::button>
                            <x-filament::button size="sm" color="danger" icon="heroicon-o-trash" wire:click="deleteSelectedBackups" wire:confirm="{{ __('Delete all selected backup archives?') }}">
                                {{ __('Delete selected') }}
                            </x-filament::button>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800">
                                    <th class="px-2 py-3 w-10">
                                        <input type="checkbox"
                                               wire:click="toggleSelectAllBackups"
                                               @checked(count($backupsList) > 0 && count($selectedBackupIds) === count($backupsList))
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    </th>
                                    <th class="px-2 py-3">{{ __('Date / Comment') }}</th>
                                    <th class="py-3 px-2">{{ __('Scope') }}</th>
                                    <th class="py-3 px-2">{{ __('Size') }}</th>
                                    <th class="py-3 px-2">{{ __('Status') }}</th>
                                    <th class="py-3 px-2 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                @foreach ($backupsList as $bk)
                                    <tr @class(['hover:bg-gray-50 dark:hover:bg-gray-800/30', 'bg-primary-50/50 dark:bg-primary-950/20' => in_array($bk['id'], $selectedBackupIds) || in_array((string) $bk['id'], $selectedBackupIds)])>
                                        <td class="px-2 py-3">
                                            <input type="checkbox" wire:model="selectedBackupIds" value="{{ $bk['id'] }}"
                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        </td>
                                        <td class="px-2 py-3">
                                            <div class="font-medium text-gray-800 dark:text-gray-200">
                                                {{ \Modules\Recovery\Models\PlatformBackup::formatTimestamp($bk['created_at']) }}
                                            </div>
                                            @if (! empty($bk['notes']))
                                                <div class="text-xs text-gray-500">{{ $bk['notes'] }}</div>
                                            @endif
                                            <div class="text-[10px] text-gray-400">{{ $bk['filename'] }}</div>
                                            @if (($bk['status'] ?? '') === 'completed')
                                                @php $withFiles = (bool) ($bk['include_files'] ?? false); @endphp
                                                <div @class([
                                                    'text-[10px] font-semibold',
                                                    'text-emerald-600 dark:text-emerald-400' => $withFiles,
                                                    'text-gray-400' => ! $withFiles,
                                                ])>{{ $withFiles ? __('Database + files') : __('Database only') }}</div>
                                            @endif
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
                                            @if ($status === 'failed' && ! empty($bk['error_log']))
                                                <div class="mt-1 max-w-[220px] text-[10px] text-rose-500">{{ \Illuminate\Support\Str::limit($bk['error_log'], 100) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-2 py-3 text-right">
                                            <div class="flex justify-end gap-1">
                                                @if ($status === 'completed')
                                                    @php
                                                        $withFiles = (bool) ($bk['include_files'] ?? false);
                                                        $confirmRestore = match (true) {
                                                            ($bk['scope'] ?? 'system') === 'tenant' => __('WARNING: This will replace this tenant\'s current rows with the backup. Uploaded files are shared across the platform and are not part of a tenant backup. Proceed?'),
                                                            $withFiles => __('WARNING: This will overwrite all tables and data, and replace every uploaded file and website asset with the copies held in this backup. Proceed?'),
                                                            default => __('WARNING: This will overwrite and recreate all tables and data. This archive holds no files, so uploads are left untouched. Proceed?'),
                                                        };
                                                    @endphp
                                                    <x-filament::icon-button
                                                        :href="route('platform.backups.download', $bk['id'])"
                                                        tag="a"
                                                        icon="heroicon-o-arrow-down-tray"
                                                        color="primary"
                                                        tooltip="{{ __('Download') }}"
                                                    />
                                                    <x-filament::icon-button
                                                        wire:click="executePlatformRestore({{ $bk['id'] }})"
                                                        wire:confirm="{{ $confirmRestore }}"
                                                        icon="heroicon-o-arrow-path"
                                                        color="danger"
                                                        tooltip="{{ __('Restore') }}"
                                                        wire:loading.attr="disabled"
                                                        wire:target="executePlatformRestore"
                                                    />
                                                @else
                                                    <span class="inline-block h-8 w-8 text-center leading-8 text-gray-300 dark:text-gray-600" title="{{ $status === 'pending' ? __('Backup is still being generated') : __('No archive to download — generation failed') }}">
                                                        <x-heroicon-o-arrow-down-tray class="mx-auto h-4 w-4" />
                                                    </span>
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
                                @if ($backupsList === [])
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-sm text-gray-400">{{ __('No backups match the selected filters.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>