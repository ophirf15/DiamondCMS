<?php

declare(strict_types=1);

namespace App\Domains\Builder\Support;

use App\Domains\Design\Support\DesignManager;
use App\Domains\Design\Support\SocialIcons;
use App\Domains\Forms\Support\FormManager;
use App\Domains\Portfolio\Support\PortfolioManager;
use App\Domains\Resume\Support\ResumeManager;
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
        $html = collect($document['blocks'])->map(fn (array $block) => self::renderBlock($block, 0))->implode('');

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
            ['type' => 'stats-row', 'label' => 'Stats row', 'defaults' => [
                'items' => [
                    ['value' => '10+', 'label' => 'Years'],
                    ['value' => '50+', 'label' => 'Projects'],
                    ['value' => '98%', 'label' => 'Occupancy'],
                ],
            ]],
            ['type' => 'social-links', 'label' => 'Social links', 'defaults' => [
                'variant' => 'icons-labels',
                'items' => [
                    ['label' => 'Email', 'url' => 'mailto:hello@example.com', 'icon' => 'email'],
                    ['label' => 'LinkedIn', 'url' => 'https://linkedin.com', 'icon' => 'linkedin'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com', 'icon' => 'instagram'],
                ],
            ]],
            ['type' => 'timeline', 'label' => 'Timeline', 'defaults' => [
                'items' => [
                    ['date' => '2019 — Current', 'title' => 'Property Manager', 'organization' => 'Woodmont', 'bullets' => ['Led multi-site operations.']],
                    ['date' => '2017 — 2019', 'title' => 'Assistant Manager', 'organization' => 'Greystar', 'bullets' => ['Leasing and resident care.']],
                ],
            ]],
            ['type' => 'gallery-grid', 'label' => 'Gallery grid', 'defaults' => [
                'images' => [
                    ['src' => '/brand/logo-primary-gold.svg', 'alt' => 'Gallery 1'],
                    ['src' => '/brand/logo-gold-on-charcoal.svg', 'alt' => 'Gallery 2'],
                    ['src' => '/brand/diamond-icon-gold.svg', 'alt' => 'Gallery 3'],
                ],
            ]],
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
    private static function renderBlock(array $block, int $depth = 0): string
    {
        $props = $block['props'] ?? [];
        $children = collect($block['children'] ?? [])->map(fn (array $child) => self::renderBlock($child, $depth + 1))->implode('');

        return match ($block['type']) {
            'section' => '<section class="dc-section'.($depth > 0 ? ' dc-section--nested' : '').'" data-dc-animate="rise" style="'.e(self::style(['padding' => Arr::get($props, 'padding', $depth > 0 ? '0.5rem' : '4rem 1rem')])).'">'.$children.'</section>',
            'columns' => '<div class="dc-columns" data-dc-animate="stagger" style="'.e('--dc-columns: '.max(1, (int) Arr::get($props, 'columns', 2))).'">'.$children.'</div>',
            'heading' => self::heading($props),
            'text' => '<div class="dc-text" data-dc-animate="fade">'.self::formatText((string) Arr::get($props, 'text', '')).'</div>',
            'image' => '<img class="dc-image" data-dc-animate="parallax" src="'.e((string) Arr::get($props, 'src', '')).'" alt="'.e((string) Arr::get($props, 'alt', '')).'" loading="lazy">',
            'button' => '<a class="dc-button" data-dc-animate="fade" href="'.e((string) Arr::get($props, 'url', '#')).'">'.e((string) Arr::get($props, 'text', 'Button')).'</a>',
            'spacer' => '<div aria-hidden="true" style="height:'.e((string) Arr::get($props, 'height', '2rem')).'"></div>',
            'divider' => '<hr class="dc-divider">',
            'html' => '<div class="dc-html">'.self::cleanHtml((string) Arr::get($props, 'html', '')).'</div>',
            'stats-row' => self::statsRow($props),
            'social-links' => self::socialLinks($props),
            'timeline' => self::timeline($props),
            'gallery-grid' => self::galleryGrid($props),
            'resume-summary' => self::resumeSummary(),
            'resume-experience' => self::resumeExperience(),
            'resume-download' => self::resumeDownload($props),
            'portfolio-featured-grid' => self::featuredProjects($props),
            'portfolio-project-card' => self::projectCard((string) Arr::get($props, 'slug', '')),
            'form' => app(FormManager::class)->renderEmbed((string) Arr::get($props, 'slug', '')),
            default => '',
        };
    }

    /** @param array<string, mixed> $props */
    private static function statsRow(array $props): string
    {
        $items = Arr::get($props, 'items', []);
        if (! is_array($items) || $items === []) {
            $items = [
                ['value' => '10+', 'label' => 'Years'],
                ['value' => '50+', 'label' => 'Projects'],
            ];
        }

        $html = collect($items)->map(function ($item): string {
            $item = is_array($item) ? $item : [];

            return '<div class="dc-stat" data-dc-animate="rise"><span class="dc-stat-value">'.e((string) ($item['value'] ?? '')).'</span><span class="dc-stat-label">'.e((string) ($item['label'] ?? '')).'</span></div>';
        })->implode('');

        return '<div class="dc-stats-row" data-dc-animate="stagger">'.$html.'</div>';
    }

    /** @param array<string, mixed> $props */
    private static function socialLinks(array $props): string
    {
        $items = Arr::get($props, 'items', []);
        if (! is_array($items)) {
            $items = [];
        }

        $variant = (string) Arr::get($props, 'variant', DesignManager::socialStyle());
        if (! array_key_exists($variant, DesignManager::socialStyles())) {
            $variant = 'icons-labels';
        }

        return SocialIcons::groupHtml(array_values($items), $variant);
    }

    /** @param array<string, mixed> $props */
    private static function timeline(array $props): string
    {
        $items = Arr::get($props, 'items', []);
        if (! is_array($items) || $items === []) {
            return '<p class="dc-text">Add timeline entries in the builder.</p>';
        }

        $html = collect($items)->map(function ($item): string {
            $item = is_array($item) ? $item : [];
            $date = e((string) ($item['date'] ?? ''));
            $title = e((string) ($item['title'] ?? ''));
            $org = e((string) ($item['organization'] ?? ''));
            $bullets = collect(is_array($item['bullets'] ?? null) ? $item['bullets'] : [])
                ->map(fn ($b) => '<li>'.e((string) $b).'</li>')
                ->implode('');

            return '<article class="dc-timeline-item" data-dc-animate="rise">'
                .($date !== '' ? '<span class="dc-timeline-date">'.$date.'</span>' : '')
                .'<h3 class="dc-timeline-title">'.$title.($org !== '' ? ' <span class="dc-timeline-org">· '.$org.'</span>' : '').'</h3>'
                .($bullets !== '' ? '<ul class="dc-timeline-bullets">'.$bullets.'</ul>' : '')
                .'</article>';
        })->implode('');

        return '<div class="dc-timeline" data-dc-animate="stagger">'.$html.'</div>';
    }

    /** @param array<string, mixed> $props */
    private static function galleryGrid(array $props): string
    {
        $images = Arr::get($props, 'images', []);
        if (! is_array($images) || $images === []) {
            return '<p class="dc-text">Add gallery images in the builder.</p>';
        }

        $html = collect($images)->map(function ($image): string {
            $image = is_array($image) ? $image : [];
            $src = e((string) ($image['src'] ?? ''));
            $alt = e((string) ($image['alt'] ?? ''));

            return '<figure class="dc-gallery-item" data-dc-animate="rise"><img src="'.$src.'" alt="'.$alt.'" loading="lazy"></figure>';
        })->implode('');

        return '<div class="dc-gallery-grid" data-dc-animate="stagger">'.$html.'</div>';
    }

    private static function resumeSummary(): string
    {
        try {
            $profile = app(ResumeManager::class)->primaryPublicProfile();
        } catch (\Throwable) {
            return '<p class="dc-text">Add a résumé profile in admin to show a summary here.</p>';
        }

        if (! $profile) {
            return '<p class="dc-text">Add a résumé profile in admin to show a summary here.</p>';
        }

        $parts = array_filter([
            $profile->headline ?? null,
            $profile->summary ?? null,
        ]);

        if ($parts === []) {
            return '<p class="dc-text">'.e($profile->name).'</p>';
        }

        return '<div class="dc-resume-summary"><p class="dc-text">'.e(implode(' — ', $parts)).'</p></div>';
    }

    /** @param array<string, mixed> $props */
    private static function resumeDownload(array $props): string
    {
        $label = e((string) Arr::get($props, 'text', 'Download PDF'));
        $variant = \Illuminate\Support\Facades\DB::table('resume_variants')
            ->where('visibility', 'public')
            ->orderByDesc('updated_at')
            ->first();

        if (! $variant) {
            return '<p class="dc-text">Publish a public résumé variant in admin to enable downloads.</p>';
        }

        return '<a class="dc-button" href="'.e(route('resume.print', $variant->slug)).'">'.$label.'</a>';
    }

    private static function resumeExperience(): string
    {
        try {
            $items = app(ResumeManager::class)->primaryExperienceItems();
        } catch (\Throwable) {
            return '<p class="dc-text">Add résumé experience in admin to show it here.</p>';
        }

        if ($items->isEmpty()) {
            return '<p class="dc-text">Add résumé experience in admin to show it here.</p>';
        }

        $html = $items->map(function (object $item): string {
            $title = e((string) ($item->title ?? 'Role'));
            $org = e((string) ($item->organization ?? ''));
            $body = e((string) ($item->body ?? $item->description ?? ''));

            return '<article class="dc-resume-item"><h3>'.$title.($org !== '' ? ' · '.$org : '').'</h3><p>'.$body.'</p></article>';
        })->implode('');

        return '<div class="dc-resume-experience">'.$html.'</div>';
    }

    /** @param array<string, mixed> $props */
    private static function heading(array $props): string
    {
        $level = min(6, max(1, (int) Arr::get($props, 'level', 2)));

        return sprintf('<h%d class="dc-heading">%s</h%d>', $level, e((string) Arr::get($props, 'text', 'Heading')), $level);
    }

    private static function formatText(string $text): string
    {
        // Contenteditable stores plain text with newlines; .dc-text uses white-space: pre-wrap.
        if (! preg_match('/<[a-z][\s\S]*>/i', $text)) {
            return e($text);
        }

        return self::cleanHtml($text);
    }

    private static function cleanHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><span><blockquote><code><pre>');

        return preg_replace('/\s(on\w+|style)=("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    }

    /** @param array<string, mixed> $props */
    private static function featuredProjects(array $props): string
    {
        $portfolio = app(PortfolioManager::class);
        $projects = $portfolio->featuredForBuilder((int) Arr::get($props, 'limit', 6));
        $cards = $projects->map(fn (object $project) => $portfolio->projectCardHtml($project))->implode('');

        return '<div class="dc-project-grid" data-dc-animate="stagger">'.$cards.'</div>';
    }

    private static function projectCard(string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        try {
            $portfolio = app(PortfolioManager::class);
            $project = $portfolio->publicProject($slug);
        } catch (\Throwable) {
            return '';
        }

        return $portfolio->projectCardHtml($project);
    }

    /** @param array<string, string> $styles */
    private static function style(array $styles): string
    {
        return collect($styles)
            ->map(fn (string $value, string $key) => $key.': '.$value)
            ->implode('; ');
    }
}
