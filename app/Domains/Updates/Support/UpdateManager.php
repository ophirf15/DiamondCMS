<?php

declare(strict_types=1);

namespace App\Domains\Updates\Support;

use App\Domains\Backups\Support\BackupManager;
use App\Domains\Core\Support\Version;
use App\Domains\Health\Services\HealthCheckService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

final class UpdateManager
{
    /**
     * Paths never replaced by a software update (content + host secrets + runtime).
     *
     * @var array<int, string>
     */
    private array $preservePrefixes = [
        '.env',
        'storage/',
        'public/storage',
        'bootstrap/cache/',
    ];

    public function currentVersion(): string
    {
        return Version::current();
    }

    /**
     * @return array{repo: string, current: string, latest: string|null, newer: bool, notes: string, html_url: string|null, assets: array<int, array{name: string, url: string, size: int}>, checksum_url: string|null, zip_url: string|null, zip_name: string|null}
     */
    public function checkLatest(): array
    {
        $repo = (string) config('diamondcms.updates.github_repo', 'ophiryahalom/DiamondCMS');
        $api = 'https://api.github.com/repos/'.$repo.'/releases/latest';
        $headers = ['Accept' => 'application/vnd.github+json', 'User-Agent' => 'DiamondCMS-Updater'];
        $token = config('diamondcms.updates.github_token');
        if (is_string($token) && $token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        $release = Http::timeout(20)->withHeaders($headers)->get($api)->throw()->json();
        $latest = ltrim((string) ($release['tag_name'] ?? ''), 'v');
        $assets = collect($release['assets'] ?? [])->map(fn (array $asset) => [
            'name' => (string) ($asset['name'] ?? ''),
            'url' => (string) ($asset['browser_download_url'] ?? ''),
            'size' => (int) ($asset['size'] ?? 0),
        ])->filter(fn (array $asset) => $asset['name'] !== '' && $asset['url'] !== '')->values();

        $zip = $assets->first(fn (array $asset) => (bool) preg_match('/^diamondcms-.*\.zip$/i', $asset['name'])
            && ! str_ends_with(strtolower($asset['name']), '.sha256'));
        $checksum = $assets->first(fn (array $asset) => str_ends_with(strtolower($asset['name']), '.sha256')
            || str_ends_with(strtolower($asset['name']), '.zip.sha256'));

        $current = $this->currentVersion();

        return [
            'repo' => $repo,
            'current' => $current,
            'latest' => $latest !== '' ? $latest : null,
            'newer' => $latest !== '' && version_compare($latest, $current, '>'),
            'notes' => (string) ($release['body'] ?? ''),
            'html_url' => $release['html_url'] ?? null,
            'assets' => $assets->all(),
            'zip_url' => is_array($zip) ? $zip['url'] : null,
            'zip_name' => is_array($zip) ? $zip['name'] : null,
            'checksum_url' => is_array($checksum) ? $checksum['url'] : null,
        ];
    }

    /**
     * Download latest GitHub release ZIP, verify checksum when available, stage it.
     *
     * @return array{id: int, version: string, checksum: string, stage_path: string}
     */
    public function downloadLatest(?int $userId = null): array
    {
        $latest = $this->checkLatest();
        if (! ($latest['newer'] ?? false)) {
            throw new RuntimeException('No newer GitHub release is available.');
        }
        if (! is_string($latest['zip_url'] ?? null) || $latest['zip_url'] === '') {
            throw new RuntimeException('Latest release has no diamondcms-*.zip asset. Attach the build-release ZIP to the GitHub release.');
        }

        $version = (string) $latest['latest'];
        $zipResponse = Http::timeout(120)->withHeaders(['User-Agent' => 'DiamondCMS-Updater'])->get($latest['zip_url']);
        if (! $zipResponse->successful()) {
            throw new RuntimeException('Unable to download release ZIP from GitHub.');
        }

        $expected = null;
        if (is_string($latest['checksum_url'] ?? null) && $latest['checksum_url'] !== '') {
            $sumBody = Http::timeout(30)->withHeaders(['User-Agent' => 'DiamondCMS-Updater'])->get($latest['checksum_url'])->body();
            if (preg_match('/\b([a-f0-9]{64})\b/i', $sumBody, $match)) {
                $expected = strtolower($match[1]);
            }
        }

        Storage::disk('local')->makeDirectory('updates/staged/'.$version);
        $stagePath = 'updates/staged/'.$version.'/release.zip';
        Storage::disk('local')->put($stagePath, $zipResponse->body());
        $absolute = Storage::disk('local')->path($stagePath);
        $actual = strtolower((string) hash_file('sha256', $absolute));

        if ($expected !== null && ! hash_equals($expected, $actual)) {
            Storage::disk('local')->delete($stagePath);
            throw new RuntimeException('Downloaded release checksum does not match the GitHub .sha256 asset.');
        }

        $id = (int) DB::table('update_logs')->insertGetId([
            'version' => $version,
            'status' => 'staged',
            'source_url' => $latest['zip_url'],
            'checksum' => $actual,
            'stage_path' => $stagePath,
            'notes' => json_encode([
                'from' => $this->currentVersion(),
                'repo' => $latest['repo'],
                'html_url' => $latest['html_url'],
                'zip_name' => $latest['zip_name'],
                'checksum_verified' => $expected !== null,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'version' => $version,
            'checksum' => $actual,
            'stage_path' => $stagePath,
        ];
    }

    /**
     * Stage a manually uploaded release ZIP (recovery path).
     *
     * @return array{id: int, version: string, checksum: string, stage_path: string}
     */
    public function stageUpload(UploadedFile $file, string $version, ?string $expectedChecksum = null, ?int $userId = null): array
    {
        abort_unless(strtolower($file->getClientOriginalExtension()) === 'zip', 422, 'Upload a .zip release package.');

        $version = ltrim($version, 'v');
        Storage::disk('local')->makeDirectory('updates/staged/'.$version);
        $stagePath = 'updates/staged/'.$version.'/release.zip';
        $stored = $file->storeAs('updates/staged/'.$version, 'release.zip', 'local');
        if (! is_string($stored) || $stored === '') {
            throw new RuntimeException('Unable to store uploaded release ZIP.');
        }

        $absolute = Storage::disk('local')->path($stagePath);
        $actual = strtolower((string) hash_file('sha256', $absolute));
        if (is_string($expectedChecksum) && $expectedChecksum !== '' && ! hash_equals(strtolower($expectedChecksum), $actual)) {
            Storage::disk('local')->delete($stagePath);
            throw new RuntimeException('Uploaded release checksum verification failed.');
        }

        $id = (int) DB::table('update_logs')->insertGetId([
            'version' => $version,
            'status' => 'staged',
            'source_url' => 'upload:'.$file->getClientOriginalName(),
            'checksum' => $actual,
            'stage_path' => $stagePath,
            'notes' => json_encode(['from' => $this->currentVersion(), 'source' => 'upload'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'version' => $version,
            'checksum' => $actual,
            'stage_path' => $stagePath,
        ];
    }

    /** @deprecated Prefer downloadLatest() or stageUpload() */
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

    /** @deprecated Prefer stageUpload() */
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
        Storage::disk('local')->put($stagePath, (string) file_get_contents($sourcePath));

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

    /**
     * Apply a staged release without touching site content (DB rows, media, .env).
     *
     * @return array{ok: bool, version: string, backup_id: int, migrated: bool, health: array<string, mixed>}
     */
    public function apply(int $updateId, ?int $userId = null): array
    {
        $update = DB::table('update_logs')->where('id', $updateId)->first();
        abort_unless($update && $update->status === 'staged', 404);
        abort_unless(is_string($update->stage_path) && Storage::disk('local')->exists($update->stage_path), 422, 'Staged ZIP is missing.');

        $zipAbsolute = Storage::disk('local')->path($update->stage_path);
        $fromVersion = $this->currentVersion();
        $backupId = app(BackupManager::class)->backup('pre-update', $userId);

        DB::table('update_logs')->where('id', $updateId)->update([
            'status' => 'applying',
            'notes' => json_encode(array_merge(json_decode((string) $update->notes, true) ?: [], [
                'backup_id' => $backupId,
                'from' => $fromVersion,
            ]), JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        Artisan::call('down', ['--retry' => 60, '--secret' => Str::random(16)]);

        $extractDir = Storage::disk('local')->path('updates/extract/'.$update->version.'-'.Str::lower(Str::random(6)));
        @mkdir($extractDir, 0775, true);

        try {
            $this->extractReleaseZip($zipAbsolute, $extractDir);
            $this->copyReleaseOverApp($extractDir, base_path());
            Artisan::call('migrate', ['--force' => true]);
            try {
                Artisan::call('storage:link');
            } catch (Throwable) {
            }
            Artisan::call('optimize:clear');

            $health = app(HealthCheckService::class)->detailedStatus();
            if (($health['status'] ?? '') !== 'ok') {
                throw new RuntimeException('Post-update health check failed.');
            }

            // Keep last-good package for emergency re-stage.
            Storage::disk('local')->put('updates/last-good/release.zip', (string) file_get_contents($zipAbsolute));
            Storage::disk('local')->put('updates/last-good/version.txt', (string) $update->version);

            DB::table('update_logs')->where('id', $updateId)->update([
                'status' => 'completed',
                'updated_at' => now(),
            ]);

            return [
                'ok' => true,
                'version' => (string) $update->version,
                'backup_id' => $backupId,
                'migrated' => true,
                'health' => $health,
            ];
        } catch (Throwable $exception) {
            DB::table('update_logs')->where('id', $updateId)->update([
                'status' => 'failed',
                'notes' => json_encode(array_merge(json_decode((string) $update->notes, true) ?: [], [
                    'error' => $exception->getMessage(),
                    'backup_id' => $backupId,
                ]), JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

            throw $exception;
        } finally {
            try {
                Artisan::call('up');
            } catch (Throwable) {
            }
            $this->removeDirectory($extractDir);
        }
    }

    private function extractReleaseZip(string $zipAbsolute, string $extractDir): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipAbsolute) !== true) {
            throw new RuntimeException('Unable to open staged release ZIP.');
        }
        $zip->extractTo($extractDir);
        $zip->close();
    }

    private function copyReleaseOverApp(string $extractDir, string $appRoot): void
    {
        $sourceRoot = $this->detectPackageRoot($extractDir);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($sourceRoot) + 1));
            if ($relative === '' || $this->shouldPreserve($relative)) {
                continue;
            }

            $target = $appRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0775, true);
                }

                continue;
            }

            $dir = dirname($target);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            if (! @copy($item->getPathname(), $target)) {
                throw new RuntimeException('Failed to update file: '.$relative);
            }
        }
    }

    private function detectPackageRoot(string $extractDir): string
    {
        if (is_file($extractDir.DIRECTORY_SEPARATOR.'artisan')) {
            return $extractDir;
        }

        $children = array_values(array_filter(scandir($extractDir) ?: [], fn (string $name) => ! in_array($name, ['.', '..'], true)));
        if (count($children) === 1 && is_dir($extractDir.DIRECTORY_SEPARATOR.$children[0]) && is_file($extractDir.DIRECTORY_SEPARATOR.$children[0].DIRECTORY_SEPARATOR.'artisan')) {
            return $extractDir.DIRECTORY_SEPARATOR.$children[0];
        }

        throw new RuntimeException('Release ZIP does not look like a DiamondCMS package (missing artisan).');
    }

    private function shouldPreserve(string $relative): bool
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        foreach ($this->preservePrefixes as $prefix) {
            $prefix = trim($prefix, '/');
            if ($relative === $prefix || str_starts_with($relative, $prefix.'/')) {
                return true;
            }
        }

        // Never let an update wipe host PHP handler / rewrite that may differ from the package.
        if ($relative === '.htaccess') {
            return true;
        }

        return false;
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($path);
    }
}
