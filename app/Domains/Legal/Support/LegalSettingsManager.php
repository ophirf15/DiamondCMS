<?php

declare(strict_types=1);

namespace App\Domains\Legal\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

final class LegalSettingsManager
{
    private const KEY = 'legal_settings';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'operator_name' => '',
            'contact_email' => '',
            'contact_address' => '',
            'website_url' => '',
            'jurisdiction' => '',
            'effective_date' => '',
            'show_in_footer' => true,
            'pages' => [
                'privacy' => true,
                'cookies' => true,
                'terms' => true,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        try {
            $raw = DB::table('settings')->where('key', self::KEY)->value('value');
        } catch (Throwable) {
            return self::defaults();
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $merged = array_replace_recursive(self::defaults(), is_array($decoded) ? $decoded : []);
        $merged['pages'] = array_replace(self::defaults()['pages'], is_array($merged['pages'] ?? null) ? $merged['pages'] : []);

        if (trim((string) ($merged['website_url'] ?? '')) === '') {
            $merged['website_url'] = rtrim((string) config('app.url'), '/');
        }

        if (trim((string) ($merged['operator_name'] ?? '')) === '') {
            $merged['operator_name'] = (string) (function_exists('diamondcms_site_name') ? diamondcms_site_name() : config('app.name', 'This website'));
        }

        if (trim((string) ($merged['effective_date'] ?? '')) === '') {
            $merged['effective_date'] = now()->toDateString();
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function save(array $input, ?int $userId = null): array
    {
        $defaults = self::defaults();
        $pages = is_array($input['pages'] ?? null) ? $input['pages'] : [];

        $normalized = [
            'operator_name' => trim((string) ($input['operator_name'] ?? '')),
            'contact_email' => trim((string) ($input['contact_email'] ?? '')),
            'contact_address' => trim((string) ($input['contact_address'] ?? '')),
            'website_url' => rtrim(trim((string) ($input['website_url'] ?? '')), '/'),
            'jurisdiction' => trim((string) ($input['jurisdiction'] ?? '')),
            'effective_date' => trim((string) ($input['effective_date'] ?? '')),
            'show_in_footer' => (bool) ($input['show_in_footer'] ?? true),
            'pages' => [
                'privacy' => (bool) ($pages['privacy'] ?? $defaults['pages']['privacy']),
                'cookies' => (bool) ($pages['cookies'] ?? $defaults['pages']['cookies']),
                'terms' => (bool) ($pages['terms'] ?? $defaults['pages']['terms']),
            ],
        ];

        DB::table('settings')->updateOrInsert(['key' => self::KEY], [
            'value' => json_encode($normalized, JSON_THROW_ON_ERROR),
            'group' => 'legal',
            'is_public' => true,
            'updated_by' => $userId,
            'updated_at' => now(),
            'created_at' => DB::table('settings')->where('key', self::KEY)->value('created_at') ?? now(),
        ]);

        if ($normalized['show_in_footer']) {
            self::syncFooterLinks($normalized['pages']);
        }

        return self::all();
    }

    public static function pageEnabled(string $page): bool
    {
        $pages = self::all()['pages'] ?? [];

        return (bool) ($pages[$page] ?? false);
    }

    /** @return array{slug: string, label: string, title: string} */
    public static function pageMeta(string $page): array
    {
        return match ($page) {
            'privacy' => ['slug' => 'privacy', 'label' => 'Privacy', 'title' => 'Privacy Policy'],
            'cookies' => ['slug' => 'cookies', 'label' => 'Cookies', 'title' => 'Cookie Policy'],
            'terms' => ['slug' => 'terms', 'label' => 'Terms', 'title' => 'Terms of Use'],
            default => ['slug' => $page, 'label' => ucfirst($page), 'title' => ucfirst($page)],
        };
    }

    /** @param  array<string, bool>  $pages */
    public static function syncFooterLinks(array $pages): void
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable('menus')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $menuId = DB::table('menus')->where('location', 'footer')->value('id');
        if (! $menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'name' => 'Footer',
                'location' => 'footer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $desired = [];
        foreach (['privacy', 'cookies', 'terms'] as $key) {
            if (! ($pages[$key] ?? false)) {
                continue;
            }
            $meta = self::pageMeta($key);
            $desired[$meta['slug']] = $meta['label'];
        }

        $existing = DB::table('menu_items')
            ->where('menu_id', $menuId)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $maxSort = (int) $existing->max('sort_order');

        foreach ($desired as $slug => $label) {
            $url = '/'.$slug;
            $match = $existing->first(function ($item) use ($url, $slug, $label): bool {
                $itemUrl = (string) ($item->url ?? '');

                return $itemUrl === $url
                    || str_ends_with(rtrim($itemUrl, '/'), '/'.$slug)
                    || strcasecmp((string) $item->label, $label) === 0;
            });

            if ($match) {
                DB::table('menu_items')->where('id', $match->id)->update([
                    'label' => $label,
                    'url' => $url,
                    'page_id' => null,
                    'updated_at' => now(),
                ]);

                continue;
            }

            $maxSort++;
            DB::table('menu_items')->insert([
                'menu_id' => $menuId,
                'parent_id' => null,
                'page_id' => null,
                'label' => $label,
                'url' => $url,
                'sort_order' => $maxSort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
