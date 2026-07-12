<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AnalyticsManager
{
    public function track(string $eventType, array $payload = [], ?Request $request = null): void
    {
        $request ??= request();

        DB::table('analytics_events')->insert([
            'event_type' => $eventType,
            'path' => isset($payload['path']) ? Str::limit((string) $payload['path'], 500, '') : Str::limit($request->path(), 500, ''),
            'page_id' => $payload['page_id'] ?? null,
            'resume_variant_id' => $payload['resume_variant_id'] ?? null,
            'visitor_hash' => $this->visitorHash($request),
            'session_id' => substr((string) $request->session()->getId(), 0, 64) ?: null,
            'referrer' => Str::limit((string) ($payload['referrer'] ?? $request->headers->get('referer', '')), 500, '') ?: null,
            'user_agent' => Str::limit((string) $request->userAgent(), 255, '') ?: null,
            'meta' => isset($payload['meta']) ? json_encode($payload['meta'], JSON_THROW_ON_ERROR) : null,
            'created_at' => now(),
        ]);
    }

    public function trackPageView(object $page, ?Request $request = null): void
    {
        $this->track('page_view', [
            'path' => '/'.ltrim((string) $page->slug, '/'),
            'page_id' => (int) $page->id,
        ], $request);
    }

    public function trackResumeDownload(int $variantId, ?Request $request = null): void
    {
        $this->track('resume_download', [
            'path' => '/resume/download',
            'resume_variant_id' => $variantId,
        ], $request);
    }

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        $since7 = now()->subDays(7);
        $since30 = now()->subDays(30);
        $today = now()->startOfDay();

        $pageViews7 = (int) DB::table('analytics_events')
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', $since7)
            ->count();

        $pageViewsToday = (int) DB::table('analytics_events')
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', $today)
            ->count();

        $unique7 = (int) DB::table('analytics_events')
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', $since7)
            ->whereNotNull('visitor_hash')
            ->distinct('visitor_hash')
            ->count('visitor_hash');

        $resumeDownloads = (int) DB::table('analytics_events')
            ->where('event_type', 'resume_download')
            ->count();

        $resumeDownloads7 = (int) DB::table('analytics_events')
            ->where('event_type', 'resume_download')
            ->where('created_at', '>=', $since7)
            ->count();

        $topPages = DB::table('analytics_events as e')
            ->leftJoin('pages as p', 'p.id', '=', 'e.page_id')
            ->selectRaw('e.page_id, e.path, p.title, COUNT(*) as visits')
            ->where('e.event_type', 'page_view')
            ->where('e.created_at', '>=', $since30)
            ->groupBy('e.page_id', 'e.path', 'p.title')
            ->orderByDesc('visits')
            ->limit(8)
            ->get()
            ->map(fn (object $row) => [
                'page_id' => $row->page_id,
                'path' => $row->path,
                'title' => $row->title ?: ($row->path ?: 'Unknown'),
                'visits' => (int) $row->visits,
            ])
            ->all();

        $daily = DB::table('analytics_events')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as visits')
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', $since7)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn (object $row) => [
                'day' => $row->day,
                'visits' => (int) $row->visits,
            ])
            ->all();

        return [
            'page_views_today' => $pageViewsToday,
            'page_views_7d' => $pageViews7,
            'unique_visitors_7d' => $unique7,
            'resume_downloads' => $resumeDownloads,
            'resume_downloads_7d' => $resumeDownloads7,
            'top_pages' => $topPages,
            'daily_views' => $daily,
        ];
    }

    private function visitorHash(Request $request): string
    {
        $salt = (string) config('app.key');
        $day = now()->format('Y-m-d');
        $ua = (string) $request->userAgent();
        $ip = (string) $request->ip();

        return hash('sha256', $salt.'|'.$day.'|'.$ip.'|'.$ua);
    }
}
