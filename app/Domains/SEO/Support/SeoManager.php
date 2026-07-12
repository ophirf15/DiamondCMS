<?php

declare(strict_types=1);

namespace App\Domains\SEO\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SeoManager
{
    public function sitemap(): string
    {
        $urls = collect();
        DB::table('pages')->where('status', 'published')->whereNull('password_hash')->whereNull('deleted_at')->orderBy('updated_at')->get()
            ->each(fn (object $page) => $urls->push(['loc' => url($page->slug === 'home' ? '/' : '/'.$page->slug), 'lastmod' => $page->updated_at]));
        DB::table('projects')->where('status', 'published')->where('visibility', 'public')->whereNull('deleted_at')->get()
            ->each(fn (object $project) => $urls->push(['loc' => route('projects.show', $project->slug), 'lastmod' => $project->updated_at]));

        return view('public.sitemap', ['urls' => $urls])->render();
    }

    public function robots(): string
    {
        $managed = DB::table('settings')->where('key', 'robots_txt')->value('value');
        if ($managed) {
            return (string) json_decode((string) $managed, true);
        }

        return "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /preview\nSitemap: ".url('/sitemap.xml')."\n";
    }

    public function redirectFor(string $path): ?object
    {
        try {
            $source = '/'.ltrim($path, '/');
            $redirect = DB::table('redirects')->where('source', $source)->where('is_active', true)->first();
            if ($redirect && ! Str::startsWith($redirect->target, $source)) {
                DB::table('redirects')->where('id', $redirect->id)->increment('hit_count', 1, ['last_hit_at' => now()]);

                return $redirect;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public function auditHtml(string $html, string $subjectType, int $subjectId): int
    {
        $findings = [];
        if (! preg_match('/<title>[^<]{10,}<\/title>/i', $html)) {
            $findings[] = ['type' => 'seo.title', 'message' => 'Missing or very short title tag.'];
        }
        if (! preg_match('/<meta\s+name=["\']description["\']/i', $html)) {
            $findings[] = ['type' => 'seo.description', 'message' => 'Missing meta description.'];
        }
        if (preg_match_all('/<img\b(?![^>]*\balt=)/i', $html)) {
            $findings[] = ['type' => 'a11y.alt', 'message' => 'One or more images are missing alt text.'];
        }
        if (preg_match('/<a\b[^>]*>\s*<\/a>/i', $html)) {
            $findings[] = ['type' => 'a11y.link_name', 'message' => 'One or more links have no accessible name.'];
        }

        $score = max(0, 100 - (count($findings) * 20));

        return (int) DB::table('seo_audits')->insertGetId([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'findings' => json_encode($findings, JSON_THROW_ON_ERROR),
            'score' => $score,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return Collection<int, \stdClass> */
    public function activeRedirects(): Collection
    {
        return DB::table('redirects')->where('is_active', true)->orderBy('source')->get();
    }
}
