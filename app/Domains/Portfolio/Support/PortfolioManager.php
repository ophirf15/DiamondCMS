<?php

declare(strict_types=1);

namespace App\Domains\Portfolio\Support;

use App\Domains\Builder\Support\BuilderDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PortfolioManager
{
    public function createCategory(array $data): int
    {
        return (int) DB::table('portfolio_categories')->insertGetId([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?? $data['name']),
            'type' => $data['type'] ?? 'project',
            'sort_order' => $data['sort_order'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createProject(array $data, ?int $userId = null): int
    {
        $title = (string) $data['title'];
        $status = $data['status'] ?? 'draft';

        return (int) DB::table('projects')->insertGetId([
            'category_id' => $data['category_id'] ?? null,
            'title' => $title,
            'slug' => Str::slug($data['slug'] ?? $title),
            'type' => $data['type'] ?? 'project',
            'status' => $status,
            'visibility' => $data['visibility'] ?? 'private',
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            'started_on' => $data['started_on'] ?? null,
            'completed_on' => $data['completed_on'] ?? null,
            'year' => $data['year'] ?? (isset($data['completed_on']) ? (int) substr((string) $data['completed_on'], 0, 4) : null),
            'client' => $data['client'] ?? null,
            'role' => $data['role'] ?? null,
            'url' => $data['url'] ?? null,
            'repository_url' => $data['repository_url'] ?? null,
            'cover_image' => $data['cover_image'] ?? null,
            'summary' => $data['summary'] ?? null,
            'case_study' => $data['case_study'] ?? null,
            'skills' => json_encode(array_values($data['skills'] ?? []), JSON_THROW_ON_ERROR),
            'tags' => json_encode(array_values($data['tags'] ?? []), JSON_THROW_ON_ERROR),
            'metrics' => json_encode($data['metrics'] ?? [], JSON_THROW_ON_ERROR),
            'before_after_media' => json_encode($data['before_after_media'] ?? [], JSON_THROW_ON_ERROR),
            'gallery' => json_encode(self::normalizeGallery($data['gallery'] ?? []), JSON_THROW_ON_ERROR),
            'logos' => json_encode(self::normalizeLogos($data['logos'] ?? []), JSON_THROW_ON_ERROR),
            'builder_json' => json_encode($data['builder_json'] ?? BuilderDocument::empty($title), JSON_THROW_ON_ERROR),
            'meta_title' => $data['meta_title'] ?? $title,
            'meta_description' => $data['meta_description'] ?? $data['summary'] ?? null,
            'published_at' => $status === 'published' ? ($data['published_at'] ?? now()) : null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function relate(int $projectId, int $relatedProjectId, string $type = 'related'): void
    {
        DB::table('project_relations')->updateOrInsert(
            ['project_id' => $projectId, 'related_project_id' => $relatedProjectId],
            ['relation_type' => $type],
        );
    }

    public function softDelete(int $projectId): void
    {
        DB::table('projects')->where('id', $projectId)->whereNull('deleted_at')->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function updateProject(int $projectId, array $data, ?int $userId = null): ?object
    {
        $payload = [];
        foreach (['category_id', 'title', 'slug', 'type', 'status', 'visibility', 'is_featured', 'sort_order', 'started_on', 'completed_on', 'year', 'client', 'role', 'url', 'repository_url', 'cover_image', 'summary', 'case_study', 'meta_title', 'meta_description'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('skills', $data)) {
            $payload['skills'] = json_encode(array_values(is_array($data['skills']) ? $data['skills'] : []), JSON_THROW_ON_ERROR);
        }
        if (array_key_exists('tags', $data)) {
            $payload['tags'] = json_encode(array_values(is_array($data['tags']) ? $data['tags'] : []), JSON_THROW_ON_ERROR);
        }
        if (array_key_exists('gallery', $data)) {
            $payload['gallery'] = json_encode(self::normalizeGallery($data['gallery']), JSON_THROW_ON_ERROR);
        }
        if (array_key_exists('logos', $data)) {
            $payload['logos'] = json_encode(self::normalizeLogos($data['logos']), JSON_THROW_ON_ERROR);
        }
        if (array_key_exists('metrics', $data)) {
            $payload['metrics'] = json_encode(is_array($data['metrics']) ? $data['metrics'] : [], JSON_THROW_ON_ERROR);
        }

        if (($payload['status'] ?? null) === 'published') {
            $payload['published_at'] = $data['published_at'] ?? now();
        }

        $payload['updated_by'] = $userId;
        $payload['updated_at'] = now();

        DB::table('projects')->where('id', $projectId)->whereNull('deleted_at')->update($payload);

        $row = DB::table('projects')->where('id', $projectId)->first();

        return $row ? $this->decodeProject($row) : null;
    }

    /** @return Collection<int, object> */
    public function adminProjects(): Collection
    {
        return DB::table('projects')
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (object $project) => $this->decodeProject($project));
    }

    /**
     * @param  mixed  $gallery
     * @return list<array{src: string, alt: string}>
     */
    public static function normalizeGallery(mixed $gallery): array
    {
        if (! is_array($gallery)) {
            return [];
        }

        $items = [];
        foreach ($gallery as $row) {
            if (is_string($row) && trim($row) !== '') {
                $items[] = ['src' => trim($row), 'alt' => ''];
                continue;
            }
            if (! is_array($row)) {
                continue;
            }
            $src = trim((string) ($row['src'] ?? $row['url'] ?? ''));
            if ($src === '') {
                continue;
            }
            $items[] = [
                'src' => $src,
                'alt' => trim((string) ($row['alt'] ?? '')),
            ];
        }

        return $items;
    }

    /**
     * @param  mixed  $logos
     * @return list<array{label: string, icon: string, image: string, url: string}>
     */
    public static function normalizeLogos(mixed $logos): array
    {
        if (! is_array($logos)) {
            return [];
        }

        $items = [];
        foreach ($logos as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $icon = strtolower(trim((string) ($row['icon'] ?? '')));
            $image = trim((string) ($row['image'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            if ($label === '' && $icon === '' && $image === '') {
                continue;
            }
            $items[] = [
                'label' => $label !== '' ? $label : ($icon !== '' ? $icon : 'Logo'),
                'icon' => preg_replace('/[^a-z0-9]/', '', $icon) ?: '',
                'image' => $image,
                'url' => $url,
            ];
        }

        return $items;
    }

    public function restore(int $projectId): void
    {
        DB::table('projects')->where('id', $projectId)->whereNotNull('deleted_at')->update([
            'deleted_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function forceDelete(int $projectId): void
    {
        DB::table('project_relations')
            ->where('project_id', $projectId)
            ->orWhere('related_project_id', $projectId)
            ->delete();
        DB::table('projects')->where('id', $projectId)->delete();
    }

    /** @return Collection<int, object> */
    public function publicProjects(array $filters = []): Collection
    {
        $query = DB::table('projects')
            ->leftJoin('portfolio_categories', 'portfolio_categories.id', '=', 'projects.category_id')
            ->where('projects.status', 'published')
            ->where('projects.visibility', 'public')
            ->whereNull('projects.deleted_at')
            ->select('projects.*', 'portfolio_categories.name as category_name', 'portfolio_categories.slug as category_slug')
            ->orderByDesc('projects.is_featured')
            ->orderBy('projects.sort_order')
            ->orderByDesc('projects.published_at');

        foreach (['type', 'status', 'year'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where("projects.$field", $filters[$field]);
            }
        }

        if (filled($filters['category'] ?? null)) {
            $query->where('portfolio_categories.slug', $filters['category']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null) {
            $query->where('projects.is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOL));
        }

        if (filled($filters['skill'] ?? null)) {
            $query->where('projects.skills', 'like', '%"'.str_replace('"', '\"', (string) $filters['skill']).'"%');
        }

        return $query->get()->map(fn (object $project) => $this->decodeProject($project));
    }

    public function publicProject(string $slug): object
    {
        $project = DB::table('projects')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNull('deleted_at')
            ->first();

        abort_unless($project, 404);

        return $this->decodeProject($project);
    }

    /** @return Collection<int, object> */
    public function featuredForBuilder(int $limit = 6): Collection
    {
        return $this->publicProjects(['featured' => true])->take($limit)->values();
    }

    public function projectCardHtml(object $project, string $heading = 'h3'): string
    {
        $url = e(route('projects.show', $project->slug));
        $title = e((string) $project->title);
        $summary = e((string) ($project->summary ?? ''));
        $cover = trim((string) ($project->cover_image ?? ''));

        $thumb = $cover !== ''
            ? '<a class="dc-project-thumb-link" href="'.$url.'"><img class="dc-project-thumb" src="'.e($cover).'" alt="" loading="lazy" decoding="async"></a>'
            : '<a class="dc-project-thumb-link dc-project-thumb-link--empty" href="'.$url.'" aria-hidden="true"><span class="dc-project-thumb dc-project-thumb--empty"></span></a>';

        $summaryHtml = $summary !== '' ? '<p>'.$summary.'</p>' : '';

        return '<article class="dc-project-card">'
            .$thumb
            .'<div class="dc-project-card-body">'
            .'<'.$heading.'><a href="'.$url.'">'.$title.'</a></'.$heading.'>'
            .$summaryHtml
            .'</div></article>';
    }

    private function decodeProject(object $project): object
    {
        foreach (['skills', 'tags', 'metrics', 'before_after_media', 'gallery', 'logos', 'builder_json'] as $field) {
            if (! property_exists($project, $field) && ! isset($project->{$field})) {
                $project->{$field} = [];
                continue;
            }
            $raw = $project->{$field} ?? null;
            if (is_array($raw)) {
                continue;
            }
            $project->{$field} = json_decode((string) ($raw ?? '[]'), true) ?: [];
        }

        $project->gallery = self::normalizeGallery($project->gallery ?? []);
        $project->logos = self::normalizeLogos($project->logos ?? []);

        return $project;
    }
}
