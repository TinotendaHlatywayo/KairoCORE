<?php

namespace Modules\CMS\Models;

use App\Models\School;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CMS\Services\CmsTemplateService;

/**
 * @property int $id
 * @property int $school_id
 * @property bool $is_template_site
 * @property int|null $active_site_template_id
 * @property string $active_template
 * @property string $font_primary
 * @property string $font_secondary
 * @property string $color_primary
 * @property string $color_background
 * @property string $color_text
 * @property array|null $navigation_menu
 * @property array|null $theme_overrides
 */
class CmsWebsite extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'is_template_site',
        'active_site_template_id',
        'active_template',
        'font_primary',
        'font_secondary',
        'font_heading',
        'color_primary',
        'color_secondary',
        'color_accent',
        'color_background',
        'color_text',
        'color_card_bg',
        'color_border',
        'color_error',
        'color_success',
        'color_warning',
        'design_radius',
        'design_shadow',
        'design_container',
        'design_button_style',
        'design_spacing_scale',
        'navigation_menu',
        'footer_menu',
        'social_links',
        'custom_css',
        'custom_js',
        'custom_head',
        'logo_light_path',
        'logo_dark_path',
        'favicon_path',
        'apple_touch_icon_path',
        'seo_title_suffix',
        'seo_global_description',
        'seo_og_image',
        'seo_default_meta',
        'announcement_banner',
        'theme_overrides',
        'font_load_strategy',
        'enable_animations',
        'enable_lazy_load',
    ];

    protected $casts = [
        'navigation_menu' => 'array',
        'footer_menu' => 'array',
        'social_links' => 'array',
        'seo_default_meta' => 'array',
        'announcement_banner' => 'array',
        'theme_overrides' => 'array',
        'font_load_strategy' => 'array',
        'enable_animations' => 'boolean',
        'enable_lazy_load' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(CmsPage::class);
    }

    public function navigationMenus(): HasMany
    {
        return $this->hasMany(CmsNavigationMenu::class);
    }

    public function globalComponents(): HasMany
    {
        return $this->hasMany(CmsGlobalComponent::class);
    }

    public function dynamicSources(): HasMany
    {
        return $this->hasMany(CmsDynamicSource::class);
    }

    public function getThemeCss(): string
    {
        return CmsTemplateService::generateThemeCss(
            [
                'primary' => $this->color_primary,
                'secondary' => $this->color_secondary,
                'accent' => $this->color_accent,
                'bg' => $this->color_background,
                'text' => $this->color_text,
                'cardBg' => $this->color_card_bg,
            ],
            [
                'radius' => CmsTemplateService::RADIUS_SCALE[$this->design_radius] ?? CmsTemplateService::RADIUS_SCALE['md'],
                'shadow' => CmsTemplateService::SHADOW_SCALE[$this->design_shadow] ?? CmsTemplateService::SHADOW_SCALE['md'],
                'container' => CmsTemplateService::CONTAINER_SCALE[$this->design_container] ?? CmsTemplateService::CONTAINER_SCALE['wide'],
                'button_style' => CmsTemplateService::BUTTON_STYLES[$this->design_button_style] ?? CmsTemplateService::BUTTON_STYLES['pill'],
                'fontPrimary' => $this->font_primary,
                'fontSecondary' => $this->font_secondary,
            ]
        );
    }
}
