<?php

declare(strict_types=1);

namespace App\Domains\Design\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SocialLinksManager
{
    private const KEY = 'social_links';

    /** @return array<int, array{id: string, label: string, url: string, icon: string}> */
    public static function all(): array
    {
        self::seedFromLegacyIfEmpty();

        $row = DB::table('settings')->where('key', self::KEY)->first();
        if (! $row) {
            return [];
        }

        $decoded = json_decode((string) $row->value, true);
        if (! is_array($decoded)) {
            return [];
        }

        return self::normalizeList($decoded);
    }

    /**
     * @param array<int, array<string, mixed>> $links
     *
     * @return array<int, array{id: string, label: string, url: string, icon: string}>
     */
    public static function save(array $links): array
    {
        $normalized = self::normalizeList($links);

        DB::table('settings')->updateOrInsert(['key' => self::KEY], [
            'value' => json_encode($normalized, JSON_THROW_ON_ERROR),
            'group' => 'design',
            'is_public' => false,
            'updated_at' => now(),
            'created_at' => DB::table('settings')->where('key', self::KEY)->value('created_at') ?? now(),
        ]);

        // Keep footer snapshot in sync when Theme already selected library IDs.
        $tokens = DesignManager::tokens();
        $ids = $tokens['chrome']['footerSocialLinkIds'] ?? [];
        if (is_array($ids) && $ids !== []) {
            DesignManager::saveTokens($tokens);
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>|string  $selection  'all' or ordered link IDs
     * @return array<int, array{id: string, label: string, url: string, icon: string}>
     */
    public static function resolve(array|string $selection): array
    {
        $library = collect(self::all())->keyBy('id');

        if ($selection === 'all') {
            return $library->values()->all();
        }

        if (! is_array($selection)) {
            return [];
        }

        $items = [];
        foreach ($selection as $id) {
            if (! is_string($id) || $id === '') {
                continue;
            }
            $item = $library->get($id);
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    public static function resolveBlockProps(array $props): array
    {
        $source = (string) ($props['source'] ?? '');
        if ($source === '' && is_array($props['items'] ?? null) && ($props['items'] ?? []) !== []) {
            $source = 'custom';
        }
        if ($source === '') {
            $source = 'library';
        }

        if ($source === 'custom') {
            return self::legacyItems($props['items'] ?? []);
        }

        $selection = $props['selection'] ?? 'all';

        return self::resolve(is_array($selection) ? $selection : (string) $selection);
    }

    public static function seedFromLegacyIfEmpty(): void
    {
        if (DB::table('settings')->where('key', self::KEY)->exists()) {
            return;
        }

        $chrome = DesignManager::chrome();
        $legacy = is_array($chrome['footerSocials'] ?? null) ? $chrome['footerSocials'] : [];
        $ids = [];

        $links = collect($legacy)->map(function ($item) use (&$ids): array {
            $item = is_array($item) ? $item : [];
            $id = (string) Str::uuid();
            $ids[] = $id;

            return [
                'id' => $id,
                'label' => (string) ($item['label'] ?? 'Link'),
                'url' => (string) ($item['url'] ?? '#'),
                'icon' => (string) ($item['icon'] ?? ''),
            ];
        })->values()->all();

        if ($links === []) {
            return;
        }

        self::save($links);

        $tokens = DesignManager::tokens();
        $tokens['chrome'] = array_merge($tokens['chrome'] ?? [], [
            'footerSocialLinkIds' => $ids,
        ]);
        DesignManager::saveTokens($tokens);
    }

    /** @param mixed $items @return array<int, array{label: string, url: string, icon: string}> */
    private static function legacyItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)->map(function ($item): array {
            $item = is_array($item) ? $item : [];

            return [
                'label' => (string) ($item['label'] ?? ''),
                'url' => (string) ($item['url'] ?? '#'),
                'icon' => (string) ($item['icon'] ?? ''),
            ];
        })->filter(fn (array $item) => $item['label'] !== '' || $item['url'] !== '#')->values()->all();
    }

    /**
     * @param  array<int, mixed>  $links
     * @return array<int, array{id: string, label: string, url: string, icon: string}>
     */
    private static function normalizeList(array $links): array
    {
        $normalized = [];
        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }
            $label = trim((string) ($link['label'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));
            if ($label === '' && $url === '') {
                continue;
            }
            $id = trim((string) ($link['id'] ?? ''));
            if ($id === '') {
                $id = (string) Str::uuid();
            }
            $normalized[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : 'Link',
                'url' => $url !== '' ? $url : '#',
                'icon' => trim((string) ($link['icon'] ?? '')),
            ];
        }

        return $normalized;
    }
}
