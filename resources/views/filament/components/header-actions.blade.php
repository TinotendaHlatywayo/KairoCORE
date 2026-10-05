{{--
    Header for pages that deliberately show no page title (they render their own
    heading inside the page body) but still need their toolbar actions.

    Filament only renders the header - and therefore the header actions - when
    getHeader() returns a view or getHeading() returns a non-empty string. A page
    that returns an empty heading to hide the built-in title therefore loses every
    header action unless it supplies this view instead.
--}}
@if (filled($actions))
    <div class="fi-header-actions flex flex-wrap items-center justify-end gap-x-3 gap-y-2">
        <x-filament-actions::actions :actions="$actions" />
    </div>
@endif