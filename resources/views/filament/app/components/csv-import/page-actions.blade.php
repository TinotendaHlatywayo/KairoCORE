@if (filled($actions))
    <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-2 px-4 pt-4 sm:px-6">
        <x-filament-actions::actions :actions="$actions" />
    </div>
@endif
