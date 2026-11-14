@include('filament.app.resources.student.partials.student-styles')

<div
    class="std-content"
    x-data
    x-on:keydown.window.slash.prevent="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && ! $event.target.isContentEditable) { $refs.search.focus(); $refs.search.select(); }"
>
    @include('filament.app.resources.student.partials.student-toolbar')

    <div class="std-empty">
        <b>{{ __('No students match') }}</b>
        {{ __('Try a different search or clear the filters.') }}
        <div>
            <button type="button" class="std-btn" wire:click="clearStudentFilters">
                {{ __('Clear filters') }}
            </button>
        </div>
    </div>
</div>
