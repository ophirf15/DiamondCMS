<?php

declare(strict_types=1);

namespace App\Domains\Updates\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class UpdateManager
{
    public function checkRelease(string $apiUrl): array
    {
        $release = Http::timeout(15)->acceptJson()->get($apiUrl)->throw()->json();

        return [
            'version' => ltrim((string) ($release['tag_name'] ?? ''), 'v'),
            'notes' => $release['body'] ?? '',
            'assets' => collect($release['assets'] ?? [])->map(fn (array $asset) => [
                'name' => $asset['name'] ?? '',
                'url' => $asset['browser_download_url'] ?? '',
            ])->values()->all(),
        ];
    }

    public function stage(string $sourcePath, string $expectedChecksum, string $version): int
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException('Release ZIP was not found.');
        }

        $actual = hash_file('sha256', $sourcePath);
        if (! hash_equals(strtolower($expectedChecksum), strtolower($actual))) {
            throw new RuntimeException('Release checksum verification failed.');
        }

        $stagePath = 'updates/staged/'.$version.'/release.zip';
        Storage::disk('local')->put($stagePath, file_get_contents($sourcePath));

        return (int) DB::table('update_logs')->insertGetId([
            'version' => $version,
            'status' => 'staged',
            'source_url' => $sourcePath,
            'checksum' => $actual,
            'stage_path' => $stagePath,
            'notes' => json_encode(['rollback' => 'Create backup before activation; restore staged files on failed health check.'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
