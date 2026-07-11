<?php

declare(strict_types=1);

namespace App\Domains\Builder\Support;

use App\Domains\Portfolio\Support\PortfolioManager;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class BuilderDocument
{
    public const CURRENT_SCHEMA = 1;

    /** @return array<string, mixed> */
    public static function empty(string $title = 'Untitled page'): array
    {
        return [
            'schema' => self::CURRENT_SCHEMA,
            'title' => $title,
            'blocks' => [
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'section',
                    'props' => ['padding' => '4rem 1rem'],
                    'children' => [
                        [
                            'id' => (string) Str::uuid(),
                            'type' => 'heading',
                            'props' => ['level' => 1, 'text' => $title],
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'type' => 'text',
                            'props' => ['text' => 'Start editing this page in the visual builder.'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $document */
    public static function validate(array $document): array
    {
        $document = self::migrate($document);

        if (($document['schema'] ?? null) !== self::CURRENT_SCHEMA) {
            throw new InvalidArgumentException('Unsupported builder schema.');
        }

        if (! is_array($document['blocks'] ?? null)) {
            throw new InvalidArgumentException('Builder document requires a blocks array.');
        }

        foreach ($document['blocks'] as $block) {
            self::validateBlock($block);
        }

        return $document;
    }

    /** @param array<string, mixed> $document */
    public static function migrate(array $document): array
    {
        if (! isset($document['schema']) && isset($document['blocks'])) {
            $document['schema'] = 1;
        }

        return $document;
    }

    /** @param array<string, mixed> $document */
    public static function render(array $document): HtmlString
    {
        $document = self::validate($document);
        $html = collect($document['blocks'])->map(fn (array $block) => self::renderBlock($block))->implode('');

        return new HtmlString($html);
    }

    /** @return array<int, array<string, mixed>> */
    public static function registry(): array
    {
        return [
            ['type' => 'section', 'label' => 'Section', 'defaults' => ['padding' => '4rem 1rem']],
            ['type' => 'columns', 'label' => 'Columns', 'defaults' => ['columns' => 2]],
            ['type' => 'heading', 'label' => 'Heading', 'defaults' => ['level' => 2, 'text' => 'Heading']],
            ['type' => 'text', 'label' => 'Text', 'defaults' => ['text' => 'Write your copy here.']],
            ['type' => 'image', 'label' => 'Image', 'defaults' => ['src' => '', 'alt' => '']],
            ['type' => 'button', 'label' => 'Button', 'defaults' => ['text' => 'Learn more', 'url' => '#']],
            ['type' => 'spacer', 'label' => 'Spacer', 'defaults' => ['height' => '2rem']],
            ['type' => 'divider', 'label' => 'Divider', 'defaults' => []],
            ['type' => 'html', 'label' => 'HTML', 'defaults' => ['html' => '<p>Custom HTML</p>']],
            ['type' => 'resume-summary', 'label' => 'Resume summary', 'defaults' => []],
            ['type' => 'resume-experience', 'label' => 'Resume experience', 'defaults' => []],
            ['type' => 'resume-download', 'label' => 'Resume download', 'defaults' => ['text' => 'Download PDF']],
            ['type' => 'portfolio-featured-grid', 'label' => 'Featured project grid', 'defaults' => ['limit' => 6]],
            ['type' => 'portfolio-project-card', 'label' => 'Project card', 'defaults' => ['slug' => '']],
            ['type' => 'form', 'label' => 'Form', 'defaults' => ['slug' => '']],
        ];
    }

    /** @param mixed $block */
    private static function validateBlock($block): void
    {
        if (! is_array($block) || ! is_string($block['type'] ?? null)) {
            throw new InvalidArgumentException('Every builder block requires a type.');
        }

        $allowed = collect(self::registry())->pluck('type')->all();
        if (! in_array($block['type'], $allowed, true)) {
            throw new InvalidArgumentException("Unsupported builder block [{$block['type']}].");
        }

        foreach (($block['children'] ?? []) as $child) {
            self::validateBlock($child);
        }
    }

    /** @param array<string, mixed> $block */
    private static function renderBlock(array $block): string
    {
        $props = $block['props'] ?? [];
        $children = collect($block['children'] ?? [])->map(fn (array $child) => self::renderBlock($child))->implode('');

        return match ($block['type']) {
            'section' => '<section class="dc-section" style="'.e(self::style(['padding' => Arr::get($props, 'padding', '4rem 1rem')])).'">'.$children.'</section>',
            'columns' => '<div class="dc-columns" style="'.e('--dc-columns: '.max(1, (int) Arr::get($props, 'columns', 2))).'">'.$children.'</div>',
            'heading' => self::heading($props),
            'text' => '<div class="dc-text">'.self::cleanHtml((string) Arr::get($props, 'text', '')).'</div>',
            'image' => '<img class="dc-image" src="'.e((string) Arr::get($props, 'src', '')).'" alt="'.e((string) Arr::get($props, 'alt', '')).'" loading="lazy">',
            'button' => '<a class="dc-button" href="'.e((string) Arr::get($props, 'url', '#')).'">'.e((string) Arr::get($props, 'text', 'Button')).'</a>',
            'spacer' => '<div aria-hidden="true" style="height:'.e((string) Arr::get($props, 'height', '2rem')).'"></div>',
            'divider' => '<hr class="dc-divider">',
            'html' => '<div class="dc-html">'.self::cleanHtml((string) Arr::get($props, 'html', '')).'</div>',
            'resume-summary' => '<div data-resume-block="summary"></div>',
            'resume-experience' => '<div data-resume-block="experience"></div>',
            'resume-download' => '<a class="dc-button" data-resume-download href="#">'.e((string) Arr::get($props, 'text', 'Download PDF')).'</a>',
            'portfolio-featured-grid' => self::featuredProjects($props),
            'portfolio-project-card' => self::projectCard((string) Arr::get($props, 'slug', '')),
            'form' => '<div data-form-slug="'.e((string) Arr::get($props, 'slug', '')).'"></div>',
            default => '',
        };
    }

    /** @param array<string, mixed> $props */
    private static function heading(array $props): string
    {
        $level = min(6, max(1, (int) Arr::get($props, 'level', 2)));

        return sprintf('<h%d class="dc-heading">%s</h%d>', $level, e((string) Arr::get($props, 'text', 'Heading')), $level);
    }

    private static function cleanHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><span><blockquote><code><pre>');

        return preg_replace('/\s(on\w+|style)=("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    }

    /** @param array<string, mixed> $props */
    private static function featuredProjects(array $props): string
    {
        $projects = app(PortfolioManager::class)->featuredForBuilder((int) Arr::get($props, 'limit', 6));
        $cards = $projects->map(fn (object $project) => sprintf(
            '<article class="dc-project-card"><h3><a href="%s">%s</a></h3><p>%s</p></article>',
            e(route('projects.show', $project->slug)),
            e($project->title),
            e((string) $project->summary),
        ))->implode('');

        return '<div class="dc-project-grid">'.$cards.'</div>';
    }

    private static function projectCard(string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        try {
            $project = app(PortfolioManager::class)->publicProject($slug);
        } catch (\Throwable) {
            return '';
        }

        return sprintf(
            '<article class="dc-project-card"><h3><a href="%s">%s</a></h3><p>%s</p></article>',
            e(route('projects.show', $project->slug)),
            e($project->title),
            e((string) $project->summary),
        );
    }

    /** @param array<string, string> $styles */
    private static function style(array $styles): string
    {
        return collect($styles)
            ->map(fn (string $value, string $key) => $key.': '.$value)
            ->implode('; ');
    }
}
