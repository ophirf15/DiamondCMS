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
                'background' => '#121714',
                'foreground' => '#e8eee9',
                'muted' => '#9aada1',
                'primary' => '#3d9b82',
                'primaryContrast' => '#061510',
                'surface' => '#1a221e',
                'accent' => '#c9a15b',
            ],
            'typography' => [
                'body' => "'Sora', ui-sans-serif, system-ui, sans-serif",
                'heading' => "'Fraunces', Georgia, serif",
                'scale' => 1.2,
            ],
            'spacing' => ['container' => '1120px', 'radius' => '0.4rem'],
            'motion' => ['enabled' => true],
            'branding' => ['logo' => null, 'alternateLogo' => null, 'favicon' => null],
        ];
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

        $css = ':root{'
            .'--dc-bg:'.($colors['background'] ?? '#e4e9e5').';'
            .'--dc-fg:'.($colors['foreground'] ?? '#141816').';'
            .'--dc-muted:'.($colors['muted'] ?? '#5c6b63').';'
            .'--dc-primary:'.($colors['primary'] ?? '#0d5c4d').';'
            .'--dc-primary-contrast:'.($colors['primaryContrast'] ?? '#f4faf7').';'
            .'--dc-surface:'.($colors['surface'] ?? '#f6f8f6').';'
            .'--dc-accent:'.($colors['accent'] ?? '#a67c3d').';'
            .'--dc-font-body:'.($typography['body'] ?? "'Sora', ui-sans-serif, system-ui, sans-serif").';'
            .'--dc-font-heading:'.($typography['heading'] ?? "'Fraunces', Georgia, serif").';'
            .'--dc-container:'.($spacing['container'] ?? '1120px').';'
            .'--dc-radius:'.($spacing['radius'] ?? '0.4rem').';'
            .'}'
            .'@media (prefers-color-scheme: dark){:root{'
            .'--dc-bg:'.($dark['background'] ?? '#121714').';'
            .'--dc-fg:'.($dark['foreground'] ?? '#e8eee9').';'
            .'--dc-muted:'.($dark['muted'] ?? '#9aada1').';'
            .'--dc-primary:'.($dark['primary'] ?? '#3d9b82').';'
            .'--dc-primary-contrast:'.($dark['primaryContrast'] ?? '#061510').';'
            .'--dc-surface:'.($dark['surface'] ?? '#1a221e').';'
            .'--dc-accent:'.($dark['accent'] ?? '#c9a15b').';'
            .'}}'
            .'@media (prefers-reduced-motion: reduce){*,::before,::after{animation:none!important;transition:none!important}}';

        return new HtmlString('<style id="diamondcms-design-tokens">'.$css.'</style>');
    }
}
