<?php

namespace App\Filament\Admin\Pages;

use App\Filament\App\Pages\WebsiteContentManager;
use App\Services\PlatformCmsService;

/**
 * Platform Content Manager.
 *
 * The platform's own website is edited with the exact same Content Manager the
 * schools use — this page simply re-hosts that editor inside the platform panel
 * and points it at the platform's reserved CMS website. Every database read and
 * write runs inside PlatformCmsService's tenant context so the tenant CMS
 * services work unchanged.
 */
class PlatformContentManager extends WebsiteContentManager
{
    protected static string $view = 'livewire.cms.platform-content-manager';

    protected static ?string $slug = 'cms/content';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Content Manager';

    protected static ?int $navigationSort = 1;

    /**
     * The tenant permission trait denies any class that is not in the capability
     * catalogue. The platform panel is already gated by authentication, so this
     * page opts out of that tenant-only gate.
     */
    public static function canAccess(): bool
    {
        return (bool) auth()->check();
    }

    public static function can(string $ability, $record = null): bool
    {
        return (bool) auth()->check();
    }

    public function mount(): void
    {
        PlatformCmsService::ensureWebsite();

        PlatformCmsService::withContext(fn () => parent::mount());
    }

    public function loadPages(): void
    {
        PlatformCmsService::withContext(fn () => parent::loadPages());
    }

    public function selectPage(int $pageId): void
    {
        PlatformCmsService::withContext(fn () => parent::selectPage($pageId));
    }

    public function selectBlock(int $index): void
    {
        PlatformCmsService::withContext(fn () => parent::selectBlock($index));
    }

    public function updatedSelectedBlockData(): void
    {
        PlatformCmsService::withContext(fn () => parent::updatedSelectedBlockData());
    }

    public function attachUploadedImage(?string $targetField = null): void
    {
        PlatformCmsService::withContext(fn () => parent::attachUploadedImage($targetField));
    }

    public function buildTheme(): void
    {
        PlatformCmsService::withContext(fn () => parent::buildTheme());
    }

    public function previewVars(): string
    {
        return PlatformCmsService::withContext(fn () => parent::previewVars());
    }

    /**
     * The platform page has no HelpContent entry, so drop the null action rather
     * than handing Filament an array containing null.
     */
    protected function getHeaderActions(): array
    {
        return array_values(array_filter([$this->getHelpAction()]));
    }
}
