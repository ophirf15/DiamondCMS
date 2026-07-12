<?php

declare(strict_types=1);

namespace App\Domains\Design\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Throwable;

final class DesignManager
{
    /** @return array<string, mixed> */
    public static function defaultTokens(): array
    {
        return [
            'mode' => 'auto',
            'colors' => [
                'background' => '#e4e9e5',
                'foreground' => '#141816',
                'muted' => '#5c6b63',
                'primary' => '#0d5c4d',
                'primaryContrast' => '#f4faf7',
                'surface' => '#f6f8f6',
                'accent' => '#a67c3d',
            ],
            'dark' => [
                'background' => '#0f0f0f',
                'foreground' => '#f4f4f4',
                'muted' => '#a8b0ac',
                'primary' => '#2dd4bf',
                'primaryContrast' => '#042f2e',
                'surface' => '#1a1a1a',
                'accent' => '#34d399',
            ],
            'typography' => [
                'body' => "'Sora', ui-sans-serif, system-ui, sans-serif",
                'heading' => "'Fraunces', Georgia, serif",
                'scale' => 1.2,
            ],
            'spacing' => [
                'container' => '1120px',
                'radius' => '0.4rem',
                'headerPadY' => '1.35rem',
                'headerPadX' => '1.5rem',
            ],
            'motion' => ['enabled' => true],
            'atmosphere' => [
                'preset' => 'soft-teal',
                'custom' => '',
            ],
            'chrome' => [
                'headerStyle' => 'classic',
                'footerStyle' => 'branded',
                'footerShowLogo' => true,
                'footerShowSiteName' => true,
                'footerTagline' => '',
                'footerShowCredit' => true,
                'footerCreditText' => 'Powered by DiamondCMS',
                'footerCreditUrl' => '',
                'footerSocials' => [],
                'footerSocialStyle' => 'icons',
            ],
            'buttons' => [
                'style' => 'solid',
            ],
            /** HeroUI-inspired polish layered on shadcn-vue (HeroUI itself is React-only). */
            'uiKit' => [
                'radiusPreset' => 'md',
                'surface' => 'soft',
                'density' => 'comfortable',
                'controlStyle' => 'soft',
                'socialStyle' => 'icons-labels',
            ],
            'themeControl' => [
                'allowVisitorToggle' => true,
                'lockMode' => false,
            ],
            'branding' => [
                'logo' => '/brand/logo-primary-gold.svg',
                'alternateLogo' => '/brand/logo-white.svg',
                'favicon' => '/brand/favicon.svg',
            ],
        ];
    }

    /** @return array<string, array{label: string, description: string}> */
    public static function headerStyles(): array
    {
        return [
            'classic' => ['label' => 'Classic', 'description' => 'Logo left, links right'],
            'pill' => ['label' => 'Pill nav', 'description' => 'Centered capsule menu'],
            'minimal' => ['label' => 'Minimal', 'description' => 'Thin bar, quiet links'],
            'centered' => ['label' => 'Centered brand', 'description' => 'Logo centered above links'],
            'split' => ['label' => 'Split CTA', 'description' => 'Links left, accent action right'],
        ];
    }

    /** @return array<string, array{label: string, description: string}> */
    public static function footerStyles(): array
    {
        return [
            'minimal' => ['label' => 'Minimal', 'description' => 'Compact link row'],
            'branded' => ['label' => 'Branded', 'description' => 'Logo + links + credit'],
            'split' => ['label' => 'Split', 'description' => 'Brand left, links right'],
            'centered' => ['label' => 'Centered', 'description' => 'Stacked centered footer'],
        ];
    }

    /** @return array<string, array{label: string, description: string}> */
    public static function buttonStyles(): array
    {
        return [
            'solid' => ['label' => 'Solid', 'description' => 'Filled primary button'],
            'soft' => ['label' => 'Soft', 'description' => 'Tinted background, no hard edge'],
            'outline' => ['label' => 'Outline', 'description' => 'Bordered, transparent fill'],
            'pill' => ['label' => 'Pill', 'description' => 'Fully rounded solid'],
            'ghost' => ['label' => 'Ghost', 'description' => 'Text with subtle hover'],
            'underline' => ['label' => 'Underline', 'description' => 'Link-style with accent underline'],
        ];
    }

    /** @return array<string, array{label: string, description: string, radius: string}> */
    public static function radiusPresets(): array
    {
        return [
            'sm' => ['label' => 'Sharp', 'description' => 'Subtle corners', 'radius' => '0.25rem'],
            'md' => ['label' => 'Balanced', 'description' => 'Default shadcn feel', 'radius' => '0.5rem'],
            'lg' => ['label' => 'Soft', 'description' => 'HeroUI-like rounded', 'radius' => '0.85rem'],
            'xl' => ['label' => 'Plush', 'description' => 'Very rounded panels', 'radius' => '1.15rem'],
            'full' => ['label' => 'Pill', 'description' => 'Capsule controls', 'radius' => '999px'],
        ];
    }

    /** @return array<string, array{label: string, description: string}> */
    public static function surfacePresets(): array
    {
        return [
            'flat' => ['label' => 'Flat', 'description' => 'No elevation'],
            'soft' => ['label' => 'Soft', 'description' => 'Tinted panels, light blur'],
            'elevated' => ['label' => 'Elevated', 'description' => 'Deeper shadow cards'],
        ];
    }

    /** @return array<string, array{label: string, description: string}> */
    public static function socialStyles(): array
    {
        return [
            'list' => ['label' => 'Text list', 'description' => 'Classic dotted list'],
            'icons' => ['label' => 'Icons only', 'description' => 'Brand marks in a row'],
            'icons-labels' => ['label' => 'Icons + labels', 'description' => 'Icon beside name'],
            'pills' => ['label' => 'Pills', 'description' => 'Soft rounded chips'],
        ];
    }

    /** @return array<string, array{label: string, css: string, mode?: string, dark?: array<string, string>}> */
    public static function atmospherePresets(): array
    {
        // All atmospheres use theme tokens (--dc-bg, --dc-primary, etc.) so light/dark
        // toggles re-shade the whole page instead of leaving a fixed dark backdrop.
        return [
            'solid' => [
                'label' => 'Solid color',
                'css' => 'var(--dc-bg)',
            ],
            'soft-teal' => [
                'label' => 'Soft teal wash',
                'css' => 'radial-gradient(ellipse 80% 50% at 0% -10%, color-mix(in srgb, var(--dc-primary) 22%, transparent), transparent 55%), radial-gradient(ellipse 60% 40% at 100% 0%, color-mix(in srgb, var(--dc-accent) 14%, transparent), transparent 50%), linear-gradient(165deg, var(--dc-bg), color-mix(in srgb, var(--dc-bg) 88%, var(--dc-fg)) 100%)',
            ],
            'navy' => [
                'label' => 'Navy AI gradient',
                'css' => 'radial-gradient(circle at 20% 18%, color-mix(in srgb, var(--dc-primary) 42%, var(--dc-bg)) 0%, var(--dc-bg) 46%, color-mix(in srgb, var(--dc-bg) 88%, #000) 100%)',
                'mode' => 'dark',
                'dark' => [
                    'background' => '#050a15',
                    'foreground' => '#eef5ff',
                    'muted' => '#9eb0c8',
                    'primary' => '#00a3ff',
                    'primaryContrast' => '#041018',
                    'surface' => '#0b1524',
                    'accent' => '#4cc9f0',
                ],
            ],
            'midnight' => [
                'label' => 'Midnight charcoal',
                'css' => 'radial-gradient(ellipse 70% 50% at 50% -20%, color-mix(in srgb, var(--dc-surface) 70%, var(--dc-primary)) 0%, var(--dc-bg) 58%)',
                'mode' => 'dark',
                'dark' => [
                    'background' => '#0a0a0a',
                    'foreground' => '#f4f4f4',
                    'muted' => '#a3a3a3',
                    'primary' => '#b8ff3c',
                    'primaryContrast' => '#041004',
                    'surface' => '#141414',
                    'accent' => '#84cc16',
                ],
            ],
            'split-teal' => [
                'label' => 'Deep teal panel',
                'css' => 'linear-gradient(115deg, color-mix(in srgb, var(--dc-primary) 40%, var(--dc-bg)) 0%, var(--dc-bg) 48%, color-mix(in srgb, var(--dc-surface) 75%, var(--dc-bg)) 48%)',
                'mode' => 'dark',
                'dark' => [
                    'background' => '#062828',
                    'foreground' => '#f4fff8',
                    'muted' => '#a7c4bc',
                    'primary' => '#2dd4bf',
                    'primaryContrast' => '#042f2e',
                    'surface' => '#0d3333',
                    'accent' => '#a3e635',
                ],
            ],
            'custom' => [
                'label' => 'Custom CSS',
                'css' => '',
            ],
        ];
    }

    public static function atmosphereCss(?array $tokens = null): string
    {
        $tokens ??= self::tokens();
        $atmosphere = $tokens['atmosphere'] ?? [];
        $preset = (string) ($atmosphere['preset'] ?? 'soft-teal');
        $presets = self::atmospherePresets();

        if ($preset === 'custom') {
            $custom = trim((string) ($atmosphere['custom'] ?? ''));

            return $custom !== '' ? $custom : (string) $presets['solid']['css'];
        }

        return (string) ($presets[$preset]['css'] ?? $presets['soft-teal']['css']);
    }

    /** @param array<string, mixed> $tokens */
    public static function saveTokens(array $tokens, ?int $userId = null): void
    {
        $tokens = array_replace_recursive(self::defaultTokens(), $tokens);
        DB::table('settings')->updateOrInsert(
            ['key' => 'design_tokens'],
            [
                'value' => json_encode($tokens, JSON_THROW_ON_ERROR),
                'group' => 'design',
                'is_public' => true,
                'updated_by' => $userId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        DB::table('design_revisions')->insert([
            'type' => 'tokens',
            'payload' => json_encode($tokens, JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    public static function tokens(): array
    {
        try {
            $stored = DB::table('settings')->where('key', 'design_tokens')->value('value');
            $decoded = is_string($stored) ? json_decode($stored, true) : null;
        } catch (Throwable) {
            $decoded = null;
        }

        return array_replace_recursive(self::defaultTokens(), is_array($decoded) ? $decoded : []);
    }

    public static function cssVariables(): HtmlString
    {
        $tokens = self::tokens();
        $colors = $tokens['colors'] ?? [];
        $dark = $tokens['dark'] ?? [];
        $typography = $tokens['typography'] ?? [];
        $spacing = $tokens['spacing'] ?? [];
        $mode = (string) ($tokens['mode'] ?? 'auto');
        $themeControl = $tokens['themeControl'] ?? [];
        $lockMode = (bool) ($themeControl['lockMode'] ?? false);

        $lightVars = self::colorVars($colors, [
            'background' => '#e4e9e5',
            'foreground' => '#141816',
            'muted' => '#5c6b63',
            'primary' => '#0d5c4d',
            'primaryContrast' => '#f4faf7',
            'surface' => '#f6f8f6',
            'accent' => '#a67c3d',
        ]);
        $darkVars = self::colorVars($dark, [
            'background' => '#0f0f0f',
            'foreground' => '#f4f4f4',
            'muted' => '#a8b0ac',
            'primary' => '#2dd4bf',
            'primaryContrast' => '#042f2e',
            'surface' => '#1a1a1a',
            'accent' => '#34d399',
        ]);

        $atmosphereCss = str_replace(['</', ';'], ['', ''], self::atmosphereCss($tokens));
        $uiKit = $tokens['uiKit'] ?? [];
        $radiusPreset = (string) ($uiKit['radiusPreset'] ?? 'md');
        $radiusMap = self::radiusPresets();
        $resolvedRadius = $radiusMap[$radiusPreset]['radius'] ?? ($spacing['radius'] ?? '0.5rem');
        // Explicit spacing.radius still wins when uiKit preset is default-balanced and spacing was customized.
        if ($radiusPreset === 'md' && isset($spacing['radius']) && is_string($spacing['radius']) && $spacing['radius'] !== '') {
            $resolvedRadius = $spacing['radius'];
        } elseif ($radiusPreset !== 'md') {
            $resolvedRadius = $radiusMap[$radiusPreset]['radius'] ?? $resolvedRadius;
        }

        $surface = (string) ($uiKit['surface'] ?? 'soft');
        $density = (string) ($uiKit['density'] ?? 'comfortable');
        $controlStyle = (string) ($uiKit['controlStyle'] ?? 'soft');
        $socialStyle = (string) ($uiKit['socialStyle'] ?? 'icons-labels');

        $shared = '--dc-font-body:'.($typography['body'] ?? "'Sora', ui-sans-serif, system-ui, sans-serif").';'
            .'--dc-font-heading:'.($typography['heading'] ?? "'Fraunces', Georgia, serif").';'
            .'--dc-container:'.($spacing['container'] ?? '1120px').';'
            .'--dc-radius:'.$resolvedRadius.';'
            .'--dc-header-pad-y:'.($spacing['headerPadY'] ?? '1.35rem').';'
            .'--dc-header-pad-x:'.($spacing['headerPadX'] ?? '1.5rem').';'
            .'--dc-site-bg:'.$atmosphereCss.';'
            .'--dc-uikit-surface:'.$surface.';'
            .'--dc-uikit-density:'.$density.';'
            .'--dc-uikit-control:'.$controlStyle.';'
            .'--dc-social-style:'.$socialStyle.';';

        $rootVars = match ($mode) {
            'dark' => $darkVars.$shared,
            'light' => $lightVars.$shared,
            default => $lightVars.$shared,
        };

        $css = ':root{'.$rootVars.'}';
        if ($mode === 'auto' && ! $lockMode) {
            $css .= '@media (prefers-color-scheme: dark){:root{'.$darkVars.'--dc-site-bg:'.$atmosphereCss.';color-scheme:dark;}}';
            $css .= '@media (prefers-color-scheme: light){:root{color-scheme:light;}}';
        }

        if ($mode === 'dark') {
            $css .= ':root{color-scheme:dark;}';
        } elseif ($mode === 'light') {
            $css .= ':root{color-scheme:light;}';
        }

        if (! $lockMode) {
            $css .= 'html[data-theme="light"]{'.$lightVars.$shared.'color-scheme:light;}';
            $css .= 'html[data-theme="dark"]{'.$darkVars.$shared.'color-scheme:dark;}';
        }

        if (! self::motionEnabled()) {
            $css .= 'html[data-dc-motion="off"] [data-dc-animate],html[data-dc-motion="off"] .dc-reveal{animation:none!important;opacity:1!important;transform:none!important}';
        }

        $css .= '@media (prefers-reduced-motion: reduce){*,::before,::after{animation:none!important;transition:none!important}}';

        return new HtmlString('<style id="diamondcms-design-tokens">'.$css.'</style>');
    }

    public static function resolvedDefaultTheme(): string
    {
        $tokens = self::tokens();
        $mode = (string) ($tokens['mode'] ?? 'auto');

        return match ($mode) {
            'light', 'dark' => $mode,
            default => 'auto',
        };
    }

    public static function visitorToggleEnabled(): bool
    {
        $control = self::tokens()['themeControl'] ?? [];

        return (bool) ($control['allowVisitorToggle'] ?? true) && ! (bool) ($control['lockMode'] ?? false);
    }

    public static function themeLocked(): bool
    {
        return (bool) ((self::tokens()['themeControl'] ?? [])['lockMode'] ?? false);
    }

    public static function headerStyle(): string
    {
        $style = (string) ((self::tokens()['chrome'] ?? [])['headerStyle'] ?? 'classic');

        return array_key_exists($style, self::headerStyles()) ? $style : 'classic';
    }

    public static function footerStyle(): string
    {
        $style = (string) ((self::tokens()['chrome'] ?? [])['footerStyle'] ?? 'branded');

        return array_key_exists($style, self::footerStyles()) ? $style : 'branded';
    }

    public static function buttonStyle(): string
    {
        $style = (string) ((self::tokens()['buttons'] ?? [])['style'] ?? 'solid');

        return array_key_exists($style, self::buttonStyles()) ? $style : 'solid';
    }

    /** @return array<string, mixed> */
    public static function chrome(): array
    {
        return self::tokens()['chrome'] ?? [];
    }

    /** @return array<string, mixed> */
    public static function uiKit(): array
    {
        return self::tokens()['uiKit'] ?? [];
    }

    public static function socialStyle(): string
    {
        $chrome = self::chrome();
        $fromChrome = (string) ($chrome['footerSocialStyle'] ?? '');
        if (array_key_exists($fromChrome, self::socialStyles())) {
            return $fromChrome;
        }

        $fromKit = (string) ((self::uiKit()['socialStyle'] ?? 'icons-labels'));

        return array_key_exists($fromKit, self::socialStyles()) ? $fromKit : 'icons-labels';
    }

    public static function surfaceAttr(): string
    {
        $surface = (string) ((self::uiKit()['surface'] ?? 'soft'));

        return array_key_exists($surface, self::surfacePresets()) ? $surface : 'soft';
    }

    public static function motionEnabled(): bool
    {
        $motion = self::tokens()['motion'] ?? [];

        return (bool) ($motion['enabled'] ?? true);
    }

    /** @param array<string, mixed> $colors @param array<string, string> $defaults */
    private static function colorVars(array $colors, array $defaults): string
    {
        return '--dc-bg:'.($colors['background'] ?? $defaults['background']).';'
            .'--dc-fg:'.($colors['foreground'] ?? $defaults['foreground']).';'
            .'--dc-muted:'.($colors['muted'] ?? $defaults['muted']).';'
            .'--dc-primary:'.($colors['primary'] ?? $defaults['primary']).';'
            .'--dc-primary-contrast:'.($colors['primaryContrast'] ?? $defaults['primaryContrast']).';'
            .'--dc-surface:'.($colors['surface'] ?? $defaults['surface']).';'
            .'--dc-accent:'.($colors['accent'] ?? $defaults['accent']).';'
            .'--dc-line:color-mix(in srgb, var(--dc-fg) 14%, transparent);'
            .'--dc-ink:var(--dc-fg);'
            .'--dc-shadow:0 1px 0 color-mix(in srgb, var(--dc-fg) 6%, transparent), 0 18px 40px color-mix(in srgb, var(--dc-fg) 10%, transparent);';
    }

    public static function logoUrl(): string
    {
        $logo = self::tokens()['branding']['logo'] ?? null;

        return is_string($logo) && $logo !== '' ? $logo : '/brand/logo-primary-gold.svg';
    }
}
