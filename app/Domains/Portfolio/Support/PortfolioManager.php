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
            'gallery' => json_encode($data['gallery'] ?? [], JSON_THROW_ON_ERROR),
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

    private function decodeProject(object $project): object
    {
        foreach (['skills', 'tags', 'metrics', 'before_after_media', 'gallery', 'builder_json'] as $field) {
            $project->{$field} = json_decode((string) ($project->{$field} ?? '[]'), true) ?: [];
        }

        return $project;
    }
}
