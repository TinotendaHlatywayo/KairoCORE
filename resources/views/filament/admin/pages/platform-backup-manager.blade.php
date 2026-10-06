<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Manual Actions & Upload Panel -->
        <div class="space-y-6">
            <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-3">{{ __('System Actions') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Capture the whole platform, or just a single tenant, into a compressed recovery archive.') }}</p>

                <div class="space-y-3 mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Backup scope') }}</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" wire:click="$set('backupScope', 'system')" @class([
                            'py-2 px-2 rounded-lg text-xs font-semibold border transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' => $backupScope === 'system',
                            'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' => $backupScope !== 'system',
                        ])>{{ __('System') }}</button>
                        <button type="button" wire:click="$set('backupScope', 'tenant')" @class([
                            'py-2 px-2 rounded-lg text-xs font-semibold border transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' => $backupScope === 'tenant',
                            'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' => $backupScope !== 'tenant',
                        ])>{{ __('Single') }}</button>
                        <button type="button" wire:click="$set('backupScope', 'selected')" @class([
                            'py-2 px-2 rounded-lg text-xs font-semibold border transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' => $backupScope === 'selected',
                            'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' => $backupScope !== 'selected',
                        ])>{{ __('Selected') }}</button>
                    </div>

                    @if($backupScope === 'tenant')
                        <select wire:model="tenantSchoolId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
                            <option value="">{{ __('Select a tenant…') }}</option>
                            @foreach($tenantOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    @elseif($backupScope === 'selected')
                        <div class="max-h-48 overflow-y-auto space-y-1 p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs">
                            @foreach($tenantOptions as $id => $name)
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <input type="checkbox" wire:model="selectedTenantIds" value="{{ $id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-gray-700 dark:text-gray-200">{{ $name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <button type="button" wire:click="triggerPlatformBackup" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <x-heroicon-o-cloud-arrow-up class="w-5 h-5"/>
                    <span>{{ $backupScope === 'tenant' ? __('Generate Tenant Backup') : ($backupScope === 'selected' ? __('Generate Selected Tenants Backup') : __('Generate Full Platform Backup')) }}</span>
                </button>
            </div>

            <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-3">{{ __('Upload Recovery ZIP') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Import a local recovery archive to use as a restore point.') }}</p>
                <form wire:submit.prevent="uploadExternalBackup" class="space-y-4">
                    {{ $this->form }}
                    <button type="submit" class="w-full py-2.5 px-4 bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold text-sm rounded-lg transition shadow-sm">
                        {{ __('Import Archive File') }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Compiled Platform Backups Table -->
        <div class="lg:col-span-2 p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">{{ __('Platform Recovery Vault') }}</h3>
            
            @if(empty($backupsList))
                <div class="text-center py-12 text-gray-400">
                    <x-heroicon-o-cloud-arrow-down class="w-12 h-12 mx-auto mb-3" />
                    <p class="text-sm">{{ __('No backups generated yet.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 text-gray-500">
                                <th class="py-3 px-2">{{ __('Archive Filename') }}</th>
                                <th class="py-3 px-2">{{ __('Scope') }}</th>
                                <th class="py-3 px-2">{{ __('Size') }}</th>
                                <th class="py-3 px-2">{{ __('Status') }}</th>
                                <th class="py-3 px-2 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                            @foreach($backupsList as $bk)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="py-3 px-2 font-medium text-gray-800 dark:text-gray-200">
                                        {{ $bk['filename'] }}
                                        <div class="text-[10px] text-gray-400">SHA256: {{ substr($bk['checksum'], 0, 16) }}...</div>
                                    </td>
                                    <td class="py-3 px-2">
                                        @if(($bk['scope'] ?? 'system') === 'tenant')
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded bg-sky-100 text-sky-800 dark:bg-sky-950/40 dark:text-sky-400">{{ __('Tenant') }}</span>
                                            <div class="text-[10px] text-gray-400">{{ $bk['school_name'] ?? ('#'.$bk['school_id']) }}</div>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded bg-violet-100 text-violet-800 dark:bg-violet-950/40 dark:text-violet-400">{{ __('System') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-gray-600 dark:text-gray-400">
                                        {{ round($bk['size_bytes'] / (1024 * 1024), 2) }} MB
                                    </td>
                                    <td class="py-3 px-2">
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400">
                                            {{ $bk['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-right space-x-2">
                                        <button wire:click="downloadBackup({{ $bk['id'] }})" class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded transition" title="Download ZIP">
                                            <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                                        </button>
                                        <button wire:click="executePlatformRestore({{ $bk['id'] }})" onclick="return confirm('{{ (($bk['scope'] ?? 'system') === 'tenant') ? __('WARNING: You are about to restore this tenant. Their current rows will be replaced by the backup. Proceed?') : __('WARNING: You are about to run a full database restore. This will overwrite and recreate all tables and data. Proceed?') }}')" class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition" title="Restore">
                                            <x-heroicon-o-arrow-path class="w-5 h-5" />
                                        </button>
                                        <button wire:click="deleteBackupRecord({{ $bk['id'] }})" onclick="return confirm('Are you sure you want to delete this backup archive?')" class="p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Delete">
                                            <x-heroicon-o-trash class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>