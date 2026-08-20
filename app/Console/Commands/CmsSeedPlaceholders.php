<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\CMS\Services\ComponentRegistry;

/**
 * Generates the local placeholder image set shipped with every school template
 * (never hot-linked external images). Each file is a flat, school-palette scene
 * illustration — pleasant enough to keep, trivial to replace, and free of any
 * copyright concerns.
 */
class CmsSeedPlaceholders extends Command
{
    protected $signature = 'cms:seed-placeholders {--force : Overwrite existing files}';

    protected $description = 'Generate scene-illustrated SVG placeholder images for CMS templates under public/images/placeholders';

    public function handle(): int
    {
        $dir = public_path('images/placeholders');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $written = 0;
        foreach (ComponentRegistry::PLACEHOLDER_IMAGES as $key => $path) {
            $file = public_path($path);

            if (file_exists($file) && ! $this->option('force')) {
                $this->warn("Skipped {$file} (exists)");

                continue;
            }

            file_put_contents($file, $this->svg($key));
            $written++;
            $this->line("Wrote {$file}");
        }

        $this->info("Placeholder set complete: {$written} generated.");

        return self::SUCCESS;
    }

    protected function svg(string $key): string
    {
        $label = ucwords(str_replace(['-', '_'], ' ', $key));
        $scene = $this->scene($key);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800" width="1200" height="800" role="img" aria-label="Placeholder: '.$label.'">'
            .$scene
            .'<rect x="0" y="0" width="1200" height="800" fill="none" stroke="'.self::accentOf($key).'" stroke-opacity="0.25" stroke-width="3"/>'
            .'<rect x="1016" y="28" width="156" height="34" rx="17" fill="'.self::accentOf($key).'" fill-opacity="0.1" stroke="'.self::accentOf($key).'" stroke-opacity="0.35"/>'
            .'<text x="1094" y="51" text-anchor="middle" font-family="system-ui, -apple-system, sans-serif" font-size="15" font-weight="600" letter-spacing="1" fill="'.self::accentOf($key).'">'.strtoupper($label).'</text>'
            .'<text x="600" y="764" text-anchor="middle" font-family="system-ui, -apple-system, sans-serif" font-size="17" fill="'.self::accentOf($key).'" fill-opacity="0.6">Add your photo here</text>'
            .'</svg>
';
    }

    protected static function accentOf(string $key): string
    {
        return self::THEMES()[$key][1] ?? '#64748b';
    }

    protected static function THEMES(): array
    {
        return [
            'campus-exterior' => ['#e8eef5', '#4a6fa5'],
            'campus-quad' => ['#e7efe6', '#4f7942'],
            'classroom' => ['#f3eede', '#8a7a3a'],
            'library' => ['#ece7f2', '#6b5b95'],
            'science-lab' => ['#e6f0f5', '#2f7e9e'],
            'sports-field' => ['#e8f2e6', '#3f8f3f'],
            'assembly-hall' => ['#f2e7e5', '#9c4a3a'],
            'students-library' => ['#e9edf5', '#46588c'],
            'students-outdoor' => ['#f5f0e4', '#a8782c'],
            'cafeteria' => ['#f5ede2', '#b0713a'],
            'arts-studio' => ['#f0e8f0', '#8a4a8a'],
            'staff-silhouette' => ['#edf0f3', '#64748b'],
            'logo-placeholder' => ['#f1f5f9', '#94a3b8'],
            'event-cover' => ['#f5eae4', '#b5552a'],
            'news-cover' => ['#e9f0f0', '#3d7a7a'],
        ];
    }

    protected function scene(string $key): string
    {
        return match ($key) {
            'campus-exterior' => $this->campusExterior(),
            'campus-quad' => $this->campusQuad(),
            'classroom' => $this->classroom(),
            'library' => $this->library(),
            'science-lab' => $this->scienceLab(),
            'sports-field' => $this->sportsField(),
            'assembly-hall' => $this->assemblyHall(),
            'students-library' => $this->studentsLibrary(),
            'students-outdoor' => $this->studentsOutdoor(),
            'cafeteria' => $this->cafeteria(),
            'arts-studio' => $this->artsStudio(),
            'staff-silhouette' => $this->staffSilhouette(),
            'logo-placeholder' => $this->logoPlaceholder(),
            'event-cover' => $this->eventCover(),
            'news-cover' => $this->newsCover(),
            default => $this->generic(),
        };
    }

    protected function campusExterior(): string
    {
        $a = '#4a6fa5';
        $w = '#ffffff';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#dbe6f2"/><stop offset="1" stop-color="#eef4fa"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <ellipse cx="600" cy="716" rx="430" ry="52" fill="'.$a.'" fill-opacity="0.1"/>
  <circle cx="180" cy="210" r="62" fill="'.$a.'" fill-opacity="0.5"/>
  <circle cx="1010" cy="210" r="62" fill="'.$a.'" fill-opacity="0.5"/>
  <path d="M520 400l80-42 80 42v180h-160z" fill="'.$a.'" fill-opacity="0.9"/>
  <rect x="286" y="330" width="628" height="300" fill="'.$w.'" stroke="'.$a.'" stroke-opacity="0.35"/>
  <rect x="300" y="330" width="600" height="16" fill="'.$a.'" fill-opacity="0.85"/>
  <rect x="432" y="372" width="336" height="258" fill="'.$a.'" fill-opacity="0.08"/>
  <rect x="446" y="392" width="52" height="34" fill="'.$a.'" fill-opacity="0.35" rx="4"/>
  <rect x="510" y="392" width="52" height="34" fill="'.$a.'" fill-opacity="0.35" rx="4"/>
  <rect x="574" y="392" width="52" height="34" fill="'.$a.'" fill-opacity="0.35" rx="4"/>
  <rect x="638" y="392" width="52" height="34" fill="'.$a.'" fill-opacity="0.35" rx="4"/>
  <rect x="702" y="392" width="52" height="34" fill="'.$a.'" fill-opacity="0.35" rx="4"/>
  <rect x="524" y="452" width="152" height="178" fill="'.$w.'" stroke="'.$a.'" stroke-opacity="0.35"/>
  <rect x="534" y="556" width="44" height="74" fill="'.$a.'" fill-opacity="0.8" rx="22"/>
  <rect x="622" y="556" width="44" height="74" fill="'.$a.'" fill-opacity="0.8" rx="22"/>
  <path d="M512 452h176l-20-22h-136z" fill="'.$a.'" fill-opacity="0.75"/>
  <rect x="574" y="466" width="52" height="40" fill="'.$a.'" fill-opacity="0.28" rx="3"/>
  <path d="M386 358l64-56h78l64 56z" fill="'.$a.'" fill-opacity="0.14"/>
  <rect x="676" y="372" width="118" height="150" fill="'.$w.'" stroke="'.$a.'" stroke-opacity="0.3"/>
  <path d="M688 430l47-40 47 40v-6l-47-40-47 40z" fill="'.$a.'" fill-opacity="0.3"/>
  <path d="M176 600l0 88 60-60z" fill="'.$a.'" fill-opacity="0.7"/>
  <path d="M192 668c-44 26-96 34-148 28" fill="none" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="10" stroke-linecap="round"/>
  <path d="M1024 600l0 88 60-60z" fill="'.$a.'" fill-opacity="0.7"/>
  <path d="M1008 668c44 26 96 34 148 28" fill="none" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="10" stroke-linecap="round"/>
  <path d="M470 610l130 0 0 106-260 0z" fill="none" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="5"/>';
    }

    protected function campusQuad(): string
    {
        $a = '#4f7942';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#dcebda"/><stop offset="1" stop-color="#f1f7f0"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.34" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.16"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <circle cx="1010" cy="150" r="56" fill="#f5d76e" fill-opacity="0.85"/>
  <circle cx="1010" cy="150" r="56" fill="#fff6d0" fill-opacity="0.35"/>
  <rect x="0" y="520" width="1200" height="280" fill="#d9ecd6"/>
  <path d="M520 560h160l-40-260h-80z" fill="#c6e2c2"/>
  <ellipse cx="600" cy="700" rx="520" ry="40" fill="'.$a.'" fill-opacity="0.08"/>
  <path d="M180 560h90c-4 46-40 84-90 84s-86-38-90-84z" fill="'.$a.'" fill-opacity="0.22"/>
  <path d="M1030 560h90c-4 46-40 84-90 84s-86-38-90-84z" fill="'.$a.'" fill-opacity="0.22"/>
  <path d="M300 520l120 0-30-180h-60z" fill="'.$a.'" fill-opacity="0.4"/>
  <path d="M780 520l120 0-30-180h-60z" fill="'.$a.'" fill-opacity="0.4"/>
  <path d="M420 540l360 0-30-220h-300z" fill="'.$a.'" fill-opacity="0.28"/>
  <ellipse cx="460" cy="610" rx="74" ry="20" fill="'.$a.'" fill-opacity="0.14"/>
  <path d="M428 610c-4-66 28-98 66-98s70 32 66 98z" fill="none" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="8"/>
  <ellipse cx="740" cy="610" rx="74" ry="20" fill="'.$a.'" fill-opacity="0.14"/>
  <path d="M708 610c-4-66 28-98 66-98s70 32 66 98z" fill="none" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="8"/>
  <rect x="540" y="640" width="120" height="52" rx="26" fill="'.$a.'" fill-opacity="0.75"/>
  <rect x="540" y="640" width="120" height="20" rx="10" fill="#fff" fill-opacity="0.6"/>
  <rect x="390" y="700" width="150" height="10" rx="5" fill="'.$a.'" fill-opacity="0.5"/>
  <rect x="660" y="700" width="150" height="10" rx="5" fill="'.$a.'" fill-opacity="0.5"/>';
    }

    protected function classroom(): string
    {
        $a = '#8a7a3a';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0ead6"/><stop offset="1" stop-color="#faf6ea"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <rect x="0" y="560" width="1200" height="240" fill="#e7dfc6"/>
  <rect x="150" y="190" width="300" height="230" rx="12" fill="#cfe6f2" stroke="'.$a.'" stroke-opacity="0.4"/>
  <rect x="172" y="214" width="256" height="18" fill="#fff" fill-opacity="0.7"/>
  <rect x="172" y="250" width="256" height="18" fill="#fff" fill-opacity="0.7"/>
  <rect x="172" y="286" width="256" height="18" fill="#fff" fill-opacity="0.7"/>
  <rect x="172" y="322" width="256" height="18" fill="#fff" fill-opacity="0.7"/>
  <rect x="172" y="358" width="256" height="18" fill="#fff" fill-opacity="0.7"/>
  <rect x="480" y="196" width="420" height="170" rx="8" fill="#fdfdfb" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="4"/>
  <rect x="500" y="220" width="200" height="8" rx="4" fill="'.$a.'" fill-opacity="0.6"/>
  <rect x="500" y="244" width="240" height="8" rx="4" fill="'.$a.'" fill-opacity="0.35"/>
  <rect x="500" y="268" width="180" height="8" rx="4" fill="'.$a.'" fill-opacity="0.35"/>
  <circle cx="660" cy="330" r="22" fill="'.$a.'" fill-opacity="0.4"/>
  <rect x="500" y="520" width="260" height="30" rx="6" fill="'.$a.'" fill-opacity="0.85"/>
  <path d="M520 520v-60l220 0v60z" fill="#fdf4d8" stroke="'.$a.'" stroke-opacity="0.5"/>
  <path d="M760 520l-140 0v-44l100-26 40 34z" fill="#fdf4d8" stroke="'.$a.'" stroke-opacity="0.5"/>
  <rect x="770" y="536" width="40" height="44" rx="6" fill="'.$a.'" fill-opacity="0.8"/>
  <rect x="806" y="504" width="40" height="44" rx="6" fill="'.$a.'" fill-opacity="0.8"/>
  <rect x="922" y="540" width="170" height="24" rx="6" fill="'.$a.'" fill-opacity="0.35"/>
  <path d="M934 540l-16-56h120l-16 56z" fill="'.$a.'" fill-opacity="0.22"/>';
    }

    protected function library(): string
    {
        $a = '#6b5b95';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e4ddf0"/><stop offset="1" stop-color="#f4f1f9"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.15"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <rect x="180" y="170" width="840" height="430" fill="#8b7bb0" fill-opacity="0.18" rx="10"/>
  <rect x="180" y="170" width="210" height="430" fill="#6b5b95" fill-opacity="0.3" rx="10"/>
  <rect x="405" y="170" width="210" height="430" fill="#6b5b95" fill-opacity="0.3" rx="10"/>
  <rect x="630" y="170" width="210" height="430" fill="#6b5b95" fill-opacity="0.3" rx="10"/>
  <rect x="855" y="170" width="165" height="430" fill="#6b5b95" fill-opacity="0.3" rx="10"/>
  <rect x="196" y="186" width="36" height="130" fill="#f6c177" rx="4"/>
  <rect x="242" y="186" width="36" height="130" fill="#88b3d9" rx="4"/>
  <rect x="288" y="186" width="36" height="130" fill="#e0a5b0" rx="4"/>
  <rect x="334" y="186" width="40" height="130" fill="#a8c686" rx="4"/>
  <rect x="196" y="332" width="36" height="120" fill="#a8c686" rx="4"/>
  <rect x="242" y="332" width="36" height="120" fill="#f0c98a" rx="4"/>
  <rect x="288" y="332" width="36" height="120" fill="#9fb4d8" rx="4"/>
  <rect x="334" y="332" width="40" height="120" fill="#dca6b4" rx="4"/>
  <rect x="196" y="468" width="36" height="116" fill="#c9a4d8" rx="4"/>
  <rect x="242" y="468" width="36" height="116" fill="#e0b96c" rx="4"/>
  <rect x="288" y="468" width="36" height="116" fill="#7fb3a6" rx="4"/>
  <rect x="334" y="468" width="40" height="116" fill="#b0b8e0" rx="4"/>
  <rect x="421" y="186" width="36" height="130" fill="#7fb3a6" rx="4"/>
  <rect x="467" y="186" width="36" height="130" fill="#e0a5b0" rx="4"/>
  <rect x="513" y="186" width="36" height="130" fill="#f6c177" rx="4"/>
  <rect x="559" y="186" width="40" height="130" fill="#88b3d9" rx="4"/>
  <rect x="421" y="332" width="36" height="120" fill="#dca6b4" rx="4"/>
  <rect x="467" y="332" width="36" height="120" fill="#c9a4d8" rx="4"/>
  <rect x="513" y="332" width="36" height="120" fill="#7fb3a6" rx="4"/>
  <rect x="559" y="332" width="40" height="120" fill="#e0b96c" rx="4"/>
  <rect x="421" y="468" width="36" height="116" fill="#b0b8e0" rx="4"/>
  <rect x="467" y="468" width="36" height="116" fill="#f0c98a" rx="4"/>
  <rect x="513" y="468" width="36" height="116" fill="#dca6b4" rx="4"/>
  <rect x="559" y="468" width="40" height="116" fill="#a8c686" rx="4"/>
  <rect x="646" y="186" width="36" height="130" fill="#e0b96c" rx="4"/>
  <rect x="692" y="186" width="36" height="130" fill="#9fb4d8" rx="4"/>
  <rect x="738" y="186" width="36" height="130" fill="#a8c686" rx="4"/>
  <rect x="784" y="186" width="40" height="130" fill="#dca6b4" rx="4"/>
  <rect x="646" y="332" width="36" height="120" fill="#f6c177" rx="4"/>
  <rect x="692" y="332" width="36" height="120" fill="#88b3d9" rx="4"/>
  <rect x="738" y="332" width="36" height="120" fill="#dca6b4" rx="4"/>
  <rect x="784" y="332" width="40" height="120" fill="#c9a4d8" rx="4"/>
  <rect x="646" y="468" width="36" height="116" fill="#7fb3a6" rx="4"/>
  <rect x="692" y="468" width="36" height="116" fill="#f0c98a" rx="4"/>
  <rect x="738" y="468" width="36" height="116" fill="#b0b8e0" rx="4"/>
  <rect x="784" y="468" width="40" height="116" fill="#e0a5b0" rx="4"/>
  <rect x="871" y="186" width="36" height="130" fill="#c9a4d8" rx="4"/>
  <rect x="917" y="186" width="36" height="130" fill="#88b3d9" rx="4"/>
  <rect x="963" y="186" width="36" height="130" fill="#f6c177" rx="4"/>
  <rect x="871" y="332" width="36" height="120" fill="#e0b96c" rx="4"/>
  <rect x="917" y="332" width="36" height="120" fill="#a8c686" rx="4"/>
  <rect x="963" y="332" width="36" height="120" fill="#dca6b4" rx="4"/>
  <rect x="871" y="468" width="36" height="116" fill="#9fb4d8" rx="4"/>
  <rect x="917" y="468" width="36" height="116" fill="#7fb3a6" rx="4"/>
  <rect x="963" y="468" width="36" height="116" fill="#f0c98a" rx="4"/>
  <rect x="120" y="600" width="960" height="34" rx="8" fill="#6b5b95" fill-opacity="0.75"/>
  <rect x="152" y="634" width="120" height="56" rx="6" fill="#fdfcfb" stroke="'.$a.'" stroke-opacity="0.4"/>
  <rect x="480" y="634" width="200" height="56" rx="6" fill="#fdfcfb" stroke="'.$a.'" stroke-opacity="0.4"/>
  <path d="M540 634v-58l80 0v58z" fill="#fdfcfb" stroke="'.$a.'" stroke-opacity="0.4"/>
  <rect x="560" y="592" width="40" height="30" rx="4" fill="'.$a.'" fill-opacity="0.3"/>';
    }

    protected function scienceLab(): string
    {
        $a = '#2f7e9e';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d9ecf5"/><stop offset="1" stop-color="#f0f8fb"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.15"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <rect x="120" y="600" width="960" height="30" rx="6" fill="#2f7e9e" fill-opacity="0.8"/>
  <rect x="140" y="556" width="920" height="18" rx="4" fill="#2f7e9e" fill-opacity="0.45"/>
  <path d="M420 430c70-90 150-160 210-170 34-6 62 18 58 52-8 66-74 128-168 128h-40z" fill="#ffffff" stroke="'.$a.'" stroke-opacity="0.6" stroke-width="6"/>
  <ellipse cx="480" cy="470" rx="108" ry="60" fill="#6cc3dd" fill-opacity="0.85"/>
  <path d="M420 430l54 40 8-12-16-16z" fill="#2f7e9e" fill-opacity="0.5"/>
  <circle cx="452" cy="444" r="7" fill="#fff" fill-opacity="0.85"/>
  <circle cx="516" cy="466" r="5" fill="#fff" fill-opacity="0.7"/>
  <circle cx="486" cy="424" r="4" fill="#fff" fill-opacity="0.6"/>
  <path d="M700 320v100" stroke="'.$a.'" stroke-opacity="0.6" stroke-width="8" stroke-linecap="round"/>
  <path d="M672 330l28-36 28 36z" fill="'.$a.'" fill-opacity="0.8"/>
  <path d="M700 420l-56-20 16-34 56 20z" fill="'.$a.'" fill-opacity="0.75"/>
  <rect x="676" y="456" width="48" height="14" rx="4" fill="'.$a.'" fill-opacity="0.5"/>
  <path d="M838 430h-120l20-28h80z" fill="#f7c96c" fill-opacity="0.95"/>
  <path d="M748 402v-44h52v44z" fill="#f7c96c" fill-opacity="0.7"/>
  <rect x="760" y="358" width="28" height="16" fill="'.$a.'" fill-opacity="0.5"/>
  <circle cx="770" cy="340" r="10" fill="'.$a.'" fill-opacity="0.5"/>
  <path d="M918 470l-34-46h68z" fill="#fdfcfb" stroke="'.$a.'" stroke-opacity="0.6" stroke-width="6"/>
  <path d="M884 424l20 30h-40z" fill="#6cc3dd" fill-opacity="0.8"/>
  <rect x="884" y="454" width="68" height="16" rx="4" fill="'.$a.'" fill-opacity="0.5"/>';
    }

    protected function sportsField(): string
    {
        $a = '#3f8f3f';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d9ecd6"/><stop offset="1" stop-color="#eef7ec"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.34" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <ellipse cx="600" cy="470" rx="470" ry="230" fill="none" stroke="#fff" stroke-opacity="0.9" stroke-width="16"/>
  <ellipse cx="600" cy="470" rx="470" ry="230" fill="none" stroke="'.$a.'" stroke-opacity="0.35" stroke-width="4"/>
  <ellipse cx="600" cy="470" rx="404" ry="188" fill="none" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="5" stroke-dasharray="30 22"/>
  <rect x="220" y="330" width="760" height="280" fill="#5fae55" fill-opacity="0.9" rx="10"/>
  <ellipse cx="600" cy="470" rx="420" ry="120" fill="none" stroke="#fff" stroke-opacity="0.85" stroke-width="8"/>
  <ellipse cx="600" cy="470" rx="70" ry="70" fill="none" stroke="#fff" stroke-opacity="0.85" stroke-width="8"/>
  <circle cx="600" cy="470" r="12" fill="#fff" fill-opacity="0.9"/>
  <rect x="220" y="404" width="120" height="132" fill="none" stroke="#fff" stroke-opacity="0.85" stroke-width="8"/>
  <rect x="860" y="404" width="120" height="132" fill="none" stroke="#fff" stroke-opacity="0.85" stroke-width="8"/>
  <rect x="238" y="372" width="84" height="16" fill="#fff" fill-opacity="0.9"/>
  <rect x="878" y="372" width="84" height="16" fill="#fff" fill-opacity="0.9"/>
  <path d="M240 470l-40 0M962 470l-42 0" stroke="#fff" stroke-opacity="0.9" stroke-width="8"/>
  <path d="M220 330l760 0 -60-36 -640 0z" fill="#3f8f3f" fill-opacity="0.35"/>
  <rect x="546" y="200" width="108" height="130" fill="#fff" fill-opacity="0.95"/>
  <path d="M546 200v-24a20 20 0 0 1 40-10 20 20 0 0 1 40 10v24z" fill="#fff" fill-opacity="0.95"/>
  <path d="M600 188l0 142" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="6"/>
  <rect x="546" y="690" width="108" height="60" fill="#fff" fill-opacity="0.9" rx="8"/>
  <path d="M560 690v-44a22 22 0 0 1 44 0v44z" fill="#fff" fill-opacity="0.9"/>
  <path d="M600 670l0 60" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="6"/>';
    }

    protected function assemblyHall(): string
    {
        $a = '#9c4a3a';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ecd9d4"/><stop offset="1" stop-color="#f8f1ef"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <path d="M0 330h1200v-40q0-70-70-70H70Q0 220 0 290z" fill="'.$a.'" fill-opacity="0.85"/>
  <path d="M0 292l1200 0" stroke="#d9a05b" stroke-opacity="0.9" stroke-width="10"/>
  <path d="M0 250h1200v40q-90 0-160-20 60-8 130-20t30 0z" fill="'.$a.'" fill-opacity="0.9"/>
  <path d="M0 200c130 34 260 34 390 0M390 200c130 34 260 34 390 0M780 200c130 34 260 34 390 0" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="8" fill="none"/>
  <path d="M0 360l300 0 -40 120L0 480z" fill="'.$a.'" fill-opacity="0.75"/>
  <path d="M1200 360l-300 0 40 120 260 0z" fill="'.$a.'" fill-opacity="0.75"/>
  <rect x="300" y="360" width="600" height="130" fill="#9c4a3a" fill-opacity="0.85"/>
  <path d="M300 360h600l-70-80h-460z" fill="'.$a.'" fill-opacity="0.9"/>
  <rect x="560" y="400" width="80" height="90" fill="#8a3f31" rx="6"/>
  <rect x="578" y="380" width="44" height="30" rx="8" fill="#d9a05b" fill-opacity="0.9"/>
  <rect x="0" y="490" width="1200" height="60" fill="#8a3f31"/>
  <path d="M0 510h1200" stroke="#d9a05b" stroke-opacity="0.8" stroke-width="8"/>
  <ellipse cx="600" cy="560" rx="520" ry="60" fill="#7d3629" fill-opacity="0.5"/>
  <path d="M140 660h-100c0-60 45-100 100-100s100 40 100 100z" fill="'.$a.'" fill-opacity="0.75"/>
  <path d="M420 660h-100c0-60 45-100 100-100s100 40 100 100z" fill="'.$a.'" fill-opacity="0.75"/>
  <path d="M700 660h-100c0-60 45-100 100-100s100 40 100 100z" fill="'.$a.'" fill-opacity="0.75"/>
  <path d="M980 660h-100c0-60 45-100 100-100s100 40 100 100z" fill="'.$a.'" fill-opacity="0.75"/>
  <rect x="0" y="700" width="1200" height="100" fill="#7d3629" fill-opacity="0.4"/>';
    }

    protected function studentsLibrary(): string
    {
        $a = '#46588c';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#dde4f2"/><stop offset="1" stop-color="#f2f5fb"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <rect x="120" y="560" width="960" height="26" rx="6" fill="'.$a.'" fill-opacity="0.85"/>
  <rect x="300" y="586" width="600" height="60" fill="'.$a.'" fill-opacity="0.35"/>
  <path d="M520 240c64 0 100 44 100 110 0 66-44 110-100 110s-100-44-100-110c0-66 36-110 100-110z" fill="#f2b28c"/>
  <circle cx="520" cy="300" r="42" fill="#e8a37e"/>
  <path d="M400 586l30-160 180 0 30 160z" fill="#5b6fb0"/>
  <rect x="520" y="320" width="0" height="0"/>
  <circle cx="520" cy="296" r="10" fill="#46588c" fill-opacity="0.6"/>
  <path d="M480 470c-36 30-84 44-140 44" fill="none" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="14" stroke-linecap="round"/>
  <path d="M560 470c36 30 84 44 140 44" fill="none" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="14" stroke-linecap="round"/>
  <circle cx="220" cy="330" r="46" fill="#f2b28c"/>
  <path d="M152 586l26-200 84 0 26 200z" fill="#6e82c2"/>
  <path d="M180 470c-30 20-70 30-116 30" fill="none" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="12" stroke-linecap="round"/>
  <rect x="640" y="440" width="200" height="120" rx="8" fill="#ffffff" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4"/>
  <rect x="660" y="462" width="120" height="10" rx="5" fill="'.$a.'" fill-opacity="0.5"/>
  <rect x="660" y="484" width="140" height="10" rx="5" fill="'.$a.'" fill-opacity="0.3"/>
  <rect x="660" y="506" width="100" height="10" rx="5" fill="'.$a.'" fill-opacity="0.3"/>
  <rect x="700" y="380" width="80" height="60" rx="8" fill="'.$a.'" fill-opacity="0.25"/>
  <path d="M700 380l-40-70 40 0zM780 380l-40-70 40 0z" fill="'.$a.'" fill-opacity="0.4"/>';
    }

    protected function studentsOutdoor(): string
    {
        $a = '#a8782c';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f3ead6"/><stop offset="1" stop-color="#faf6ea"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <circle cx="1030" cy="150" r="54" fill="#f2c94c" fill-opacity="0.9"/>
  <circle cx="1030" cy="150" r="30" fill="#fdf1c0" fill-opacity="0.8"/>
  <rect x="0" y="560" width="1200" height="240" fill="#efe4c6"/>
  <path d="M320 560c-20-96 8-150 70-150s90 54 70 150z" fill="'.$a.'" fill-opacity="0.55"/>
  <path d="M860 560c-20-96 8-150 70-150s90 54 70 150z" fill="'.$a.'" fill-opacity="0.55"/>
  <path d="M600 560l140 0 -40-320h-60z" fill="'.$a.'" fill-opacity="0.35"/>
  <circle cx="492" cy="330" r="40" fill="#f2b28c"/>
  <path d="M420 560l34-170 76 0 34 170z" fill="#5b6fb0"/>
  <path d="M460 470c-34 30-80 44-136 44" fill="none" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="12" stroke-linecap="round"/>
  <rect x="534" y="396" width="46" height="54" rx="10" fill="#3c4a80" stroke="#ffffff" stroke-opacity="0.6" stroke-width="4"/>
  <circle cx="642" cy="330" r="40" fill="#eab285"/>
  <path d="M574 560l30-160 76 0 30 160z" fill="#c97d45"/>
  <path d="M616 470c-32 30-76 44-130 44" fill="none" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="12" stroke-linecap="round"/>
  <rect x="684" y="396" width="46" height="54" rx="10" fill="#5b6fb0" stroke="#ffffff" stroke-opacity="0.6" stroke-width="4"/>
  <circle cx="772" cy="336" r="36" fill="#f2b28c"/>
  <path d="M712 560l28-152 64 0 28 152z" fill="#7c9a3f"/>
  <path d="M750 474c-30 26-72 38-122 38" fill="none" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="12" stroke-linecap="round"/>
  <ellipse cx="590" cy="560" rx="340" ry="16" fill="'.$a.'" fill-opacity="0.08"/>
  <path d="M150 400c10 20 10 40 0 60" stroke="#fff" stroke-opacity="0.5" stroke-width="6" stroke-linecap="round"/>
  <path d="M1050 400c10 20 10 40 0 60" stroke="#fff" stroke-opacity="0.5" stroke-width="6" stroke-linecap="round"/>';
    }

    protected function cafeteria(): string
    {
        $a = '#b0713a';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f4ead9"/><stop offset="1" stop-color="#fbf7ee"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.13"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <line x1="220" y1="90" x2="220" y2="520" stroke="'.$a.'" stroke-opacity="0.35" stroke-width="6" stroke-linecap="round"/>
  <path d="M130 90l180 0 -30 40-120 0z" fill="#f6c177" fill-opacity="0.95"/>
  <circle cx="220" cy="96" r="10" fill="#f6c177"/>
  <line x1="980" y1="90" x2="980" y2="520" stroke="'.$a.'" stroke-opacity="0.35" stroke-width="6" stroke-linecap="round"/>
  <path d="M890 90l180 0 -30 40-120 0z" fill="#f6c177" fill-opacity="0.95"/>
  <circle cx="980" cy="96" r="10" fill="#f6c177"/>
  <rect x="130" y="560" width="940" height="22" rx="6" fill="'.$a.'" fill-opacity="0.85"/>
  <rect x="110" y="520" width="980" height="20" rx="8" fill="'.$a.'" fill-opacity="0.45"/>
  <rect x="130" y="582" width="260" height="12" rx="6" fill="'.$a.'" fill-opacity="0.4"/>
  <rect x="810" y="582" width="260" height="12" rx="6" fill="'.$a.'" fill-opacity="0.4"/>
  <rect x="200" y="360" width="150" height="160" rx="12" fill="#f2b28c"/>
  <circle cx="228" cy="392" r="10" fill="#ffffff" fill-opacity="0.85"/>
  <path d="M240 420h90" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="6" stroke-linecap="round"/>
  <path d="M240 440h70" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="6" stroke-linecap="round"/>
  <path d="M240 460h80" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="6" stroke-linecap="round"/>
  <path d="M240 480h56" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="6" stroke-linecap="round"/>
  <rect x="640" y="360" width="150" height="160" rx="12" fill="#f2b28c"/>
  <circle cx="668" cy="392" r="10" fill="#ffffff" fill-opacity="0.85"/>
  <path d="M680 420h90" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="6" stroke-linecap="round"/>
  <path d="M680 440h70" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="6" stroke-linecap="round"/>
  <path d="M680 460h80" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="6" stroke-linecap="round"/>
  <path d="M680 480h56" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="6" stroke-linecap="round"/>
  <rect x="900" y="140" width="120" height="180" rx="14" fill="#f2c94c" fill-opacity="0.9"/>
  <rect x="900" y="140" width="120" height="44" rx="14" fill="#f6a24d"/>
  <circle cx="930" cy="196" r="22" fill="#fff" fill-opacity="0.3"/>
  <rect x="180" y="140" width="120" height="180" rx="14" fill="#88b3d9" fill-opacity="0.9"/>
  <rect x="180" y="140" width="120" height="44" rx="14" fill="#6fa3cd"/>
  <circle cx="210" cy="196" r="22" fill="#fff" fill-opacity="0.3"/>';
    }

    protected function artsStudio(): string
    {
        $a = '#8a4a8a';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ecdcf0"/><stop offset="1" stop-color="#f8f2fa"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.15"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <rect x="120" y="560" width="960" height="26" rx="6" fill="'.$a.'" fill-opacity="0.8"/>
  <path d="M520 300l180 0 0 260-180 0z" fill="#ffffff" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="8"/>
  <path d="M610 300v-70l-70 70zM700 300v-70l-90 70z" fill="'.$a.'" fill-opacity="0.7"/>
  <path d="M520 300h180v260h-180z" fill="none" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4"/>
  <path d="M360 220l180 320" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="8"/>
  <circle cx="360" cy="220" r="18" fill="'.$a.'" fill-opacity="0.5"/>
  <rect x="520" y="560" width="180" height="40" fill="'.$a.'" fill-opacity="0.35"/>
  <circle cx="560" cy="420" r="46" fill="#f2c94c" fill-opacity="0.9"/>
  <path d="M600 300c40 60 40 120 0 180l-40-40z" fill="#6cc3dd" fill-opacity="0.85"/>
  <path d="M640 420l60 60" stroke="#f26d6d" stroke-opacity="0.85" stroke-width="22" stroke-linecap="round"/>
  <rect x="800" y="470" width="200" height="90" rx="14" fill="#fdfcfb" stroke="'.$a.'" stroke-opacity="0.4"/>
  <circle cx="846" cy="500" r="16" fill="#f26d6d"/>
  <circle cx="892" cy="506" r="16" fill="#f2c94c"/>
  <circle cx="938" cy="500" r="16" fill="#6cc3dd"/>
  <circle cx="872" cy="532" r="16" fill="#8a4a8a"/>
  <circle cx="918" cy="534" r="16" fill="#7cb66a"/>
  <rect x="956" y="430" width="44" height="56" rx="8" fill="#f6c177" fill-opacity="0.9"/>
  <path d="M964 430l-10-30h26l-12 30z" fill="#d9a05b" fill-opacity="0.9"/>
  <rect x="872" y="430" width="44" height="56" rx="8" fill="#6cc3dd" fill-opacity="0.9"/>
  <path d="M880 430l-10-30h26l-12 30z" fill="#4fa9c9" fill-opacity="0.9"/>
  <path d="M130 300c40-30 100-30 140 0" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="8" stroke-linecap="round"/>';
    }

    protected function staffSilhouette(): string
    {
        $a = '#64748b';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e3e9ef"/><stop offset="1" stop-color="#f4f7fa"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.32" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.16"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <ellipse cx="600" cy="650" rx="240" ry="30" fill="'.$a.'" fill-opacity="0.12"/>
  <circle cx="600" cy="320" r="150" fill="none" stroke="'.$a.'" stroke-opacity="0.28" stroke-width="6"/>
  <path d="M600 190a150 150 0 0 1 150 150c0 18-3 35-9 51h-282c-6-16-9-33-9-51a150 150 0 0 1 150-150z" fill="'.$a.'" fill-opacity="0.2"/>
  <circle cx="600" cy="296" r="78" fill="'.$a.'" fill-opacity="0.85"/>
  <path d="M470 612c26-128 92-196 130-196s104 68 130 196c34 26 60 60 74 100h-408c14-40 40-74 74-100z" fill="'.$a.'" fill-opacity="0.85"/>
  <circle cx="600" cy="296" r="90" fill="none" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4" stroke-dasharray="10 12"/>
  <path d="M600 640v-120" stroke="#ffffff" stroke-opacity="0.5" stroke-width="6" stroke-linecap="round"/>';
    }

    protected function logoPlaceholder(): string
    {
        $a = '#94a3b8';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e8edf3"/><stop offset="1" stop-color="#f6f8fb"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.4" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.2"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <ellipse cx="600" cy="620" rx="260" ry="26" fill="'.$a.'" fill-opacity="0.12"/>
  <path d="M600 210l52 106 118 17-85 83 20 118-105-55-105 55 20-118-85-83 118-17z" fill="'.$a.'" fill-opacity="0.9"/>
  <circle cx="600" cy="330" r="52" fill="#ffffff" fill-opacity="0.95"/>
  <path d="M600 284l20 40 45 6-32 32 8 45-40-21-40 21 8-45-32-32 45-6z" fill="'.$a.'" fill-opacity="0.9"/>
  <path d="M600 176l30 60 67 10-48 47 11 66-60-31-60 31 11-66-48-47 67-10z" fill="none" stroke="'.$a.'" stroke-opacity="0.45" stroke-width="4"/>';
    }

    protected function eventCover(): string
    {
        $a = '#b5552a';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f4e3da"/><stop offset="1" stop-color="#fbf4f0"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <path d="M0 210q150 70 300 0t300 0 300 0 300 0" fill="none" stroke="'.$a.'" stroke-opacity="0.7" stroke-width="10"/>
  <path d="M20 210l56 0 10 60-76 0z" fill="#f2c94c" fill-opacity="0.95"/>
  <path d="M560 210l56 0 10 60-76 0z" fill="#f2c94c" fill-opacity="0.95"/>
  <path d="M1100 210l56 0 10 60-76 0z" fill="#f2c94c" fill-opacity="0.95"/>
  <path d="M290 210l50 0 8 60-66 0z" fill="#7cb66a" fill-opacity="0.9"/>
  <path d="M850 210l50 0 8 60-66 0z" fill="#6cc3dd" fill-opacity="0.9"/>
  <rect x="320" y="330" width="560" height="150" rx="12" fill="#b5552a" fill-opacity="0.85"/>
  <rect x="344" y="358" width="220" height="12" rx="6" fill="#ffffff" fill-opacity="0.95"/>
  <rect x="344" y="386" width="300" height="12" rx="6" fill="#ffffff" fill-opacity="0.5"/>
  <rect x="344" y="414" width="260" height="12" rx="6" fill="#ffffff" fill-opacity="0.5"/>
  <rect x="344" y="442" width="120" height="18" rx="9" fill="#f2c94c"/>
  <circle cx="150" cy="470" r="34" fill="#f26d6d" fill-opacity="0.85"/>
  <circle cx="150" cy="470" r="12" fill="#fff" fill-opacity="0.3"/>
  <path d="M96 470h-24M54 470h-20" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4" stroke-linecap="round"/>
  <path d="M150 420v-24M150 380v-16" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4" stroke-linecap="round"/>
  <circle cx="1050" cy="440" r="28" fill="#7cb66a" fill-opacity="0.9"/>
  <circle cx="1050" cy="440" r="10" fill="#fff" fill-opacity="0.3"/>
  <path d="M1002 440h-20M968 440h-14" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4" stroke-linecap="round"/>
  <circle cx="320" cy="180" r="10" fill="#f2c94c" fill-opacity="0.8"/>
  <circle cx="600" cy="150" r="8" fill="#f26d6d" fill-opacity="0.8"/>
  <circle cx="880" cy="180" r="10" fill="#6cc3dd" fill-opacity="0.8"/>
  <circle cx="500" cy="130" r="6" fill="#7cb66a" fill-opacity="0.8"/>
  <circle cx="760" cy="132" r="6" fill="#f2c94c" fill-opacity="0.8"/>
  <rect x="0" y="640" width="1200" height="120" fill="#b5552a" fill-opacity="0.15"/>
  <path d="M120 690h260M820 690h260" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="8" stroke-linecap="round"/>';
    }

    protected function newsCover(): string
    {
        $a = '#3d7a7a';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e2eeed"/><stop offset="1" stop-color="#f2f8f7"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.3" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.14"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <path d="M360 300l480 0 0 360-480 0z" fill="#fdfcfb" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4"/>
  <path d="M840 300l60 0 0 360-60 0z" fill="#f3f1ea" stroke="'.$a.'" stroke-opacity="0.35" stroke-width="4"/>
  <path d="M360 352l480 0" stroke="'.$a.'" stroke-opacity="0.4" stroke-width="4"/>
  <path d="M384 330l120 0" stroke="'.$a.'" stroke-opacity="0.75" stroke-width="8" stroke-linecap="round"/>
  <rect x="384" y="376" width="180" height="150" fill="#dbe7ee" stroke="'.$a.'" stroke-opacity="0.3"/>
  <circle cx="474" cy="430" r="26" fill="#f2c94c" fill-opacity="0.8"/>
  <rect x="588" y="376" width="228" height="10" rx="5" fill="'.$a.'" fill-opacity="0.6"/>
  <rect x="588" y="398" width="198" height="8" rx="4" fill="'.$a.'" fill-opacity="0.3"/>
  <rect x="588" y="416" width="214" height="8" rx="4" fill="'.$a.'" fill-opacity="0.3"/>
  <rect x="588" y="434" width="160" height="8" rx="4" fill="'.$a.'" fill-opacity="0.3"/>
  <rect x="384" y="552" width="200" height="10" rx="5" fill="'.$a.'" fill-opacity="0.55"/>
  <rect x="384" y="574" width="240" height="8" rx="4" fill="'.$a.'" fill-opacity="0.28"/>
  <rect x="384" y="592" width="220" height="8" rx="4" fill="'.$a.'" fill-opacity="0.28"/>
  <rect x="608" y="552" width="208" height="10" rx="5" fill="'.$a.'" fill-opacity="0.55"/>
  <rect x="608" y="574" width="188" height="8" rx="4" fill="'.$a.'" fill-opacity="0.28"/>
  <rect x="608" y="592" width="160" height="8" rx="4" fill="'.$a.'" fill-opacity="0.28"/>
  <circle cx="972" cy="610" r="74" fill="#ffffff" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="10"/>
  <circle cx="948" cy="588" r="8" fill="'.$a.'" fill-opacity="0.5"/>
  <path d="M1014 652l44 44" stroke="'.$a.'" stroke-opacity="0.6" stroke-width="12" stroke-linecap="round"/>
  <path d="M936 548v-20" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="8" stroke-linecap="round"/>
  <path d="M880 520l30-30" stroke="'.$a.'" stroke-opacity="0.3" stroke-width="8" stroke-linecap="round"/>';
    }

    protected function generic(): string
    {
        $a = '#64748b';

        return '<defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e8edf3"/><stop offset="1" stop-color="#f6f8fb"/></linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.4" r="0.7"><stop offset="0" stop-color="'.$a.'" stop-opacity="0.16"/><stop offset="1" stop-color="'.$a.'" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/><rect width="1200" height="800" fill="url(#glow)"/>
  <circle cx="600" cy="380" r="130" fill="none" stroke="'.$a.'" stroke-opacity="0.5" stroke-width="10"/>
  <circle cx="600" cy="380" r="52" fill="'.$a.'" fill-opacity="0.55"/>
  <path d="M540 490l44 44 100-110" fill="none" stroke="'.$a.'" stroke-opacity="0.55" stroke-width="14" stroke-linecap="round" stroke-linejoin="round"/>';
    }
}
