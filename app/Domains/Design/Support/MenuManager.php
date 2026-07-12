<?php

declare(strict_types=1);

namespace App\Domains\Design\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MenuManager
{
    /** @return Collection<int, \stdClass> */
    public static function allWithItems(): Collection
    {
        return DB::table('menus')->orderBy('location')->get()->map(function (\stdClass $menu): \stdClass {
            $menu->items = self::treeForMenu((int) $menu->id);

            return $menu;
        });
    }

    /** @return array<int, array<string, mixed>> */
    public static function treeForMenu(int $menuId): array
    {
        $items = DB::table('menu_items')
            ->where('menu_id', $menuId)
            ->orderBy('sort_order')
            ->get();

        $pageIds = $items->pluck('page_id')->filter()->unique()->values()->all();
        $pages = $pageIds === []
            ? collect()
            : DB::table('pages')->whereIn('id', $pageIds)->whereNull('deleted_at')->get()->keyBy('id');

        /** @var array<int, array<string, mixed>> $nodes */
        $nodes = [];
        foreach ($items as $item) {
            $page = $item->page_id ? $pages->get($item->page_id) : null;
            $url = $item->url;
            if ($page) {
                $url = $page->slug === 'home' ? url('/') : url('/'.$page->slug);
            }

            $nodes[(int) $item->id] = [
                'id' => (int) $item->id,
                'parent_id' => $item->parent_id ? (int) $item->parent_id : null,
                'page_id' => $item->page_id ? (int) $item->page_id : null,
                'label' => $item->label,
                'url' => $url ?: '#',
                'sort_order' => (int) $item->sort_order,
                'children' => [],
            ];
        }

        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parentId = $node['parent_id'];
            if ($parentId && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return array_map(fn (array $node): array => self::detachReferences($node), $tree);
    }

    /** @return array<int, array<string, mixed>> */
    public static function publicItems(string $location): array
    {
        $menu = DB::table('menus')->where('location', $location)->first();
        if (! $menu) {
            return [];
        }

        return self::treeForMenu((int) $menu->id);
    }

    /** @param array<string, mixed> $node */
    private static function detachReferences(array $node): array
    {
        $node['children'] = array_map(
            fn (array $child): array => self::detachReferences($child),
            $node['children'],
        );

        return $node;
    }
}
