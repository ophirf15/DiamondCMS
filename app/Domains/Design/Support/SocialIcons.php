<?php

declare(strict_types=1);

namespace App\Domains\Design\Support;

/**
 * Renders brand icons for social links.
 * Prefers Simple Icons CDN (https://simpleicons.org); uses local SVG for brands
 * removed from Simple Icons (e.g. LinkedIn) and generic email/phone/website marks.
 */
final class SocialIcons
{
    /** @var array<string, array{title: string, hex: string, path: string}> */
    private const LOCAL = [
        'linkedin' => [
            'title' => 'LinkedIn',
            'hex' => '0A66C2',
            'path' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
        ],
        'email' => [
            'title' => 'Email',
            'hex' => 'EA4335',
            'path' => 'M1.5 4.5A2.5 2.5 0 014 2h16a2.5 2.5 0 012.5 2.5v15A2.5 2.5 0 0120 22H4a2.5 2.5 0 01-2.5-2.5v-15zm2.1.5 8.4 6.3L20.4 5H3.6zm17.4 1.9-8.1 6.08a1.5 1.5 0 01-1.8 0L2.999 6.9V19.5c0 .28.22.5.5.5h17a.5.5 0 00.5-.5V6.9z',
        ],
        'phone' => [
            'title' => 'Phone',
            'hex' => '34A853',
            'path' => 'M6.62 10.79a15.15 15.15 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z',
        ],
        'website' => [
            'title' => 'Website',
            'hex' => '6366F1',
            'path' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
        ],
    ];

    public static function guessSlug(string $label, string $url): string
    {
        $hay = strtolower($label.' '.$url);

        return match (true) {
            str_contains($hay, 'linkedin') => 'linkedin',
            str_contains($hay, 'instagram') || str_contains($hay, 'insta') => 'instagram',
            str_contains($hay, 'github') => 'github',
            str_contains($hay, 'twitter') || str_contains($hay, 'x.com') || preg_match('/(^|[^a-z])x([^a-z]|$)/', $hay) === 1 => 'x',
            str_contains($hay, 'youtube') || str_contains($hay, 'youtu.be') => 'youtube',
            str_contains($hay, 'facebook') || str_contains($hay, 'fb.com') => 'facebook',
            str_contains($hay, 'tiktok') => 'tiktok',
            str_contains($hay, 'threads') => 'threads',
            str_contains($hay, 'bluesky') || str_contains($hay, 'bsky.app') => 'bluesky',
            str_contains($hay, 'discord') => 'discord',
            str_contains($hay, 'telegram') || str_contains($hay, 't.me') => 'telegram',
            str_contains($hay, 'whatsapp') || str_contains($hay, 'wa.me') => 'whatsapp',
            str_contains($hay, 'spotify') => 'spotify',
            str_contains($hay, 'medium.com') || str_contains($hay, 'medium') => 'medium',
            str_contains($hay, 'behance') => 'behance',
            str_contains($hay, 'dribbble') => 'dribbble',
            str_contains($hay, 'mastodon') => 'mastodon',
            str_starts_with($hay, 'mailto:') || str_contains($hay, 'email') || str_contains($hay, 'gmail') => 'email',
            str_starts_with($hay, 'tel:') || str_contains($hay, 'phone') => 'phone',
            default => 'website',
        };
    }

    public static function resolveSlug(?string $icon, string $label = '', string $url = ''): string
    {
        $slug = strtolower(trim((string) $icon));
        if ($slug !== '') {
            return preg_replace('/[^a-z0-9]/', '', $slug) ?: 'website';
        }

        return self::guessSlug($label, $url);
    }

    public static function isLocal(string $slug): bool
    {
        return array_key_exists($slug, self::LOCAL);
    }

    public static function cdnUrl(string $slug, ?string $hex = null): string
    {
        $url = 'https://cdn.simpleicons.org/'.rawurlencode($slug);
        if (is_string($hex) && $hex !== '') {
            $url .= '/'.rawurlencode(ltrim($hex, '#'));
        }

        return $url;
    }

    public static function markup(string $slug, bool $colored = true): string
    {
        $slug = self::resolveSlug($slug);

        if (isset(self::LOCAL[$slug])) {
            $icon = self::LOCAL[$slug];
            $fill = $colored ? '#'.$icon['hex'] : 'currentColor';

            return '<span class="dc-social-icon" aria-hidden="true">'
                .'<svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">'
                .'<title>'.e($icon['title']).'</title>'
                .'<path fill="'.$fill.'" d="'.$icon['path'].'"/>'
                .'</svg></span>';
        }

        $src = e(self::cdnUrl($slug));

        return '<span class="dc-social-icon" aria-hidden="true">'
            .'<img src="'.$src.'" alt="" width="20" height="20" loading="lazy" decoding="async">'
            .'</span>';
    }

    /**
     * @param  array{label?: mixed, url?: mixed, icon?: mixed}  $item
     * @param  'list'|'icons'|'icons-labels'|'pills'  $variant
     */
    public static function linkHtml(array $item, string $variant = 'icons-labels'): string
    {
        $label = (string) ($item['label'] ?? 'Link');
        $url = (string) ($item['url'] ?? '#');
        $slug = self::resolveSlug(isset($item['icon']) ? (string) $item['icon'] : null, $label, $url);
        $iconHtml = self::markup($slug, $variant !== 'list');
        $safeLabel = e($label);
        $safeUrl = e($url);

        $class = match ($variant) {
            'icons' => 'dc-social-link dc-social-link--icon',
            'pills' => 'dc-social-link dc-social-link--pill',
            'list' => 'dc-social-link dc-social-link--list',
            default => 'dc-social-link dc-social-link--labeled',
        };

        $labelHtml = $variant === 'icons'
            ? '<span class="sr-only">'.$safeLabel.'</span>'
            : '<span class="dc-social-label">'.$safeLabel.'</span>';

        if ($variant === 'list') {
            return '<a class="'.$class.'" href="'.$safeUrl.'"><span class="dc-social-dot" aria-hidden="true"></span>'.$labelHtml.'</a>';
        }

        return '<a class="'.$class.'" href="'.$safeUrl.'"'.($variant === 'icons' ? ' title="'.$safeLabel.'"' : '').'>'.$iconHtml.$labelHtml.'</a>';
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  'list'|'icons'|'icons-labels'|'pills'  $variant
     */
    public static function groupHtml(array $items, string $variant = 'icons-labels', string $extraClass = ''): string
    {
        $variant = in_array($variant, ['list', 'icons', 'icons-labels', 'pills'], true) ? $variant : 'icons-labels';
        $links = collect($items)->map(fn ($item) => self::linkHtml(is_array($item) ? $item : [], $variant))->implode('');
        $class = trim('dc-social-links dc-social-links--'.$variant.' '.$extraClass);
        $animate = ! str_contains($extraClass, 'dc-footer-socials');

        return '<div class="'.$class.'"'.($animate ? ' data-dc-animate="stagger"' : '').'>'.$links.'</div>';
    }
}
