<?php

namespace App\Filament\App\Resources\CmsWebsiteResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\CmsWebsiteResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsSiteTemplate;
use Modules\CMS\Services\CmsSiteTemplateService;
use Modules\CMS\Services\CmsTemplateService;

class ManageCmsWebsites extends Page
{
    use HasPageHelp;

    protected static string $resource = CmsWebsiteResource::class;

    protected static string $view = 'filament.app.resources.cms-website.manage-cms-websites';

    protected static ?string $title = 'Website Templates';

    public function getTitle(): string
    {
        return __('Website Templates');
    }

    public function getHeading(): string
    {
        return __('Website Templates');
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return array_filter([
            $this->getHelpAction(),
            Action::make('createWebsiteFromTemplate')
                ->label(__('Create cms website'))
                ->icon('heroicon-o-plus')
                ->modalHeading(__('Create cms website'))
                ->modalWidth('7xl')
                ->form([
                    ViewField::make('template_gallery')
                        ->label(__('Choose your website foundation'))
                        ->view('filament.forms.components.cms-template-picker')
                        ->viewData(['templates' => CmsTemplateService::selectableTemplates()])
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Select::make('active_template')
                        ->options(collect(CmsTemplateService::selectableTemplates())->mapWithKeys(fn (array $template, string $key) => [$key => $template['name']])->all())
                        ->default('heritage-editorial')
                        ->required(),
                    TextInput::make('seo_title_suffix')
                        ->label(__('Title Suffix (e.g. | Royal Academy)'))
                        ->maxLength(255),
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $presetKey = CmsTemplateService::canonicalTemplate($data['active_template'] ?? 'heritage-editorial');
                    $preset = CmsTemplateService::selectableTemplates()[$presetKey] ?? CmsTemplateService::getTemplates()['heritage-editorial'] ?? [];
                    $palette = $preset['palette'] ?? [];
                    $fonts = $preset['fonts'] ?? [];
                    $design = $preset['design'] ?? [];

                    $data['active_template'] = $presetKey;
                    $data['color_primary'] = $palette['primary'] ?? '#1e3a8a';
                    $data['color_secondary'] = $palette['secondary'] ?? '#0284c7';
                    $data['color_accent'] = $palette['accent'] ?? '#f59e0b';
                    $data['color_background'] = $palette['background'] ?? '#ffffff';
                    $data['color_text'] = $palette['text'] ?? '#0f172a';
                    $data['color_card_bg'] = $palette['card_bg'] ?? '#f8fafc';
                    $data['font_primary'] = $fonts['primary'] ?? 'Inter';
                    $data['font_secondary'] = $fonts['secondary'] ?? 'Outfit';
                    $data['design_radius'] = $design['radius'] ?? 'lg';
                    $data['design_shadow'] = $design['shadow'] ?? 'md';
                    $data['design_container'] = $design['container'] ?? 'wide';
                    $data['design_button_style'] = $design['button_style'] ?? 'pill';

                    return $data;
                })
                ->action(function (array $data): void {
                    $schoolId = app('current_tenant')?->id;
                    if (! $schoolId) {
                        Notification::make()->title(__('No tenant context found.'))->danger()->send();
                        return;
                    }

                    DB::transaction(function () use ($schoolId, $data): void {
                        $live = CmsSiteTemplate::liveWebsite() ?? \Modules\CMS\Models\CmsWebsite::create([
                            'school_id' => $schoolId,
                            'is_template_site' => false,
                        ]);

                        $live->fill([
                            'active_template' => $data['active_template'],
                            'color_primary' => $data['color_primary'],
                            'color_secondary' => $data['color_secondary'],
                            'color_accent' => $data['color_accent'],
                            'color_background' => $data['color_background'],
                            'color_text' => $data['color_text'],
                            'color_card_bg' => $data['color_card_bg'],
                            'font_primary' => $data['font_primary'],
                            'font_secondary' => $data['font_secondary'],
                            'design_radius' => $data['design_radius'],
                            'design_shadow' => $data['design_shadow'],
                            'design_container' => $data['design_container'],
                            'design_button_style' => $data['design_button_style'],
                            'seo_title_suffix' => $data['seo_title_suffix'] ?? null,
                        ])->save();

                        // Create/apply a premade site template bundle so pages/layouts are ready
                        CmsSiteTemplateService::ensurePremadeTemplates($schoolId);
                        $preset = CmsTemplateService::selectableTemplates()[$data['active_template']] ?? null;
                        $template = null;
                        if ($preset) {
                            $template = CmsSiteTemplate::query()
                                ->where('school_id', $schoolId)
                                ->where('name', $preset['name'])
                                ->first();
                        }
                        if ($template) {
                            CmsSiteTemplateService::apply($template->id);
                        } else {
                            \Modules\CMS\Http\Controllers\CmsRenderController::ensureAllStandardPages($live, $schoolId);
                        }
                    });

                    Notification::make()
                        ->title(__('Website template ready'))
                        ->body(__('Your website foundation has been created/updated. You can now manage content or open Visual Studio.'))
                        ->success()
                        ->send();
                }),
        ]);
    }

    protected function getViewData(): array
    {
        $schoolId = app('current_tenant')?->id;
        if ($schoolId) {
            CmsSiteTemplateService::ensurePremadeTemplates($schoolId);
        }

        $premade = CmsSiteTemplateService::premade($schoolId);
        $live = CmsSiteTemplate::liveWebsite();

        foreach ($premade as $key => &$item) {
            $homeId = null;
            if (! empty($item['cms_website_id'])) {
                $homeId = CmsPage::where('cms_website_id', $item['cms_website_id'])->where('is_homepage', true)->value('id')
                    ?? CmsPage::where('cms_website_id', $item['cms_website_id'])->orderBy('sort_order')->value('id');
            }
            $item['home_page_id'] = $homeId;
            $item['preview_url'] = null; // safe fallback; visual card preview shown
            $item['theme'] = CmsTemplateService::selectableTemplates()[$key] ?? CmsTemplateService::getTemplates()[$key] ?? null;
        }
        unset($item);

        $activeId = $live?->active_site_template_id;

        return [
            'premadeTemplates' => $premade,
            'liveWebsite' => $live,
            'activeTemplateId' => $activeId,
        ];
    }

    public function applyTemplate(int $templateId): void
    {
        try {
            CmsSiteTemplateService::apply($templateId);
            Notification::make()->title(__('Template set live'))->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title(__('Could not apply template'))->body($e->getMessage())->danger()->send();
        }
    }
}
