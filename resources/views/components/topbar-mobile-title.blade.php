@php
    $tenantName = ($school ?? null)?->name ?? config('app.name', 'Workspace');
@endphp

<div class="sc-topbar-title">
    <span class="sc-topbar-title-text">{{ $tenantName }}</span>
</div>
