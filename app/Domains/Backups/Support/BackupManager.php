<?php

declare(strict_types=1);

namespace App\Domains\Backups\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

final class BackupManager
{
    /**
     * Content tables cloned for a full site package.
     * Secrets (mail_settings, ai_providers, users, .env) stay excluded.
     *
     * @var array<int, string>
     */
    private array $exportTables = [
        'settings',
        'pages',
        'page_revisions',
        'menus',
        'menu_items',
        'builder_templates',
        'media_folders',
        'media_items',
        'media_tags',
        'media_item_tag',
        'media_usages',
        'resume_profiles',
        'resume_sections',
        'resume_variants',
        'resume_share_links',
        'resume_imports',
        'portfolio_categories',
        'projects',
        'project_relations',
        'personal_contents',
        'testimonials',
        'galleries',
        'timeline_entries',
        'forms',
        'form_submissions',
        'email_templates',
        'redirects',
        'design_revisions',
    ];

    public function backup(string $type = 'full', ?int $userId = null): int
    {
        $manifest = $this->manifest(includeMedia: false);
        $payload = json_encode(['manifest' => $manifest, 'tables' => $this->tableDump()], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $path = 'backups/diamondcms-'.$type.'-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, $payload);
        $absolute = Storage::disk('local')->path($path);

        return (int) DB::table('backups')->insertGetId([
            'type' => $type,
            'disk' => 'local',
            'path' => $path,
            'checksum' => hash_file('sha256', $absolute),
            'size' => filesize($absolute) ?: 0,
            'manifest' => json_encode($manifest, JSON_THROW_ON_ERROR),
            'status' => 'completed',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Full portable site package: every content table + every media/upload binary.
     *
     * @return array{
     *     path: string,
     *     filename: string,
     *     relative: string,
     *     size: int,
     *     media_files: int,
     *     media_library_files: int,
     *     other_files: int,
     *     missing_media: array<int, string>,
     *     checksum: string
     * }
     */
    public function exportSite(?int $userId = null): array
    {
        $backupId = $this->backup('export', $userId);
        $backup = DB::table('backups')->where('id', $backupId)->firstOrFail();

        $filename = 'diamondcms-site-'.now()->format('Ymd-His').'.zip';
        $relative = 'exports/'.$filename;
        Storage::disk('local')->makeDirectory('exports');
        $zipPath = Storage::disk('local')->path($relative);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create site export ZIP.');
        }

        $contentJson = Storage::disk('local')->get($backup->path);
        if (! is_string($contentJson) || $contentJson === '') {
            throw new \RuntimeException('Unable to read content export payload.');
        }
        $zip->addFromString('content.json', $contentJson);

        $packed = $this->packAllSiteFiles($zip);
        $mediaCount = $packed['count'];

        $manifest = array_merge(
            $this->manifest(includeMedia: true, mediaFiles: $mediaCount),
            [
                'media_library_files' => $packed['media_library_files'],
                'other_files' => $packed['other_files'],
                'missing_media' => $packed['missing'],
            ],
        );
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $zip->addFromString(
            'README.txt',
            "DiamondCMS complete site package\n"
            ."================================\n"
            ."Includes a full copy of site content AND all media binaries:\n"
            ."- pages, menus, theme/settings, portfolio, resumes, forms, submissions\n"
            ."- email templates, redirects, design revisions\n"
            ."- entire media library (originals + image variants)\n"
            ."- form uploads and other private upload files\n\n"
            ."Excluded (reconfigure on the target host): .env, users/passwords,\n"
            ."SMTP credentials, AI API keys.\n\n"
            ."Restore: Admin → System → Import site package (Replace mode).\n"
        );
        $zip->close();

        $size = filesize($zipPath) ?: 0;
        $checksum = hash_file('sha256', $zipPath) ?: '';

        DB::table('backups')->where('id', $backupId)->update([
            'manifest' => json_encode(array_merge($manifest, [
                'export_zip' => $relative,
                'export_checksum' => $checksum,
                'export_size' => $size,
            ]), JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        return [
            'path' => $zipPath,
            'filename' => $filename,
            'relative' => $relative,
            'size' => $size,
            'media_files' => $mediaCount,
            'media_library_files' => $packed['media_library_files'],
            'other_files' => $packed['other_files'],
            'missing_media' => $packed['missing'],
            'checksum' => $checksum,
        ];
    }

    public function dryRunImport(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'errors' => ['Unable to open import ZIP.']];
        }

        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true) ?: [];
        $content = json_decode((string) $zip->getFromName('content.json'), true) ?: [];
        $mediaFiles = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_starts_with($name, 'files/') && ! str_ends_with($name, '/')) {
                $mediaFiles++;
            }
        }
        $zip->close();

        $warnings = [
            'Secrets (.env, SMTP, AI keys, users) are not imported — reconfigure on this host.',
        ];
        if ($mediaFiles === 0 && ! ($manifest['includes_media'] ?? false)) {
            $warnings[] = 'This package has no media files. Images may 404 unless you copy storage separately.';
        }

        return [
            'ok' => isset($manifest['version'], $content['tables']),
            'manifest' => $manifest,
            'tables' => array_keys($content['tables'] ?? []),
            'media_files' => $mediaFiles,
            'mode_support' => ['merge', 'replace'],
            'warnings' => $warnings,
        ];
    }

    public function recordImport(string $path, string $mode, array $report, ?int $userId = null): int
    {
        $backupId = $this->backup('pre-import', $userId);

        return (int) DB::table('import_jobs')->insertGetId([
            'mode' => $mode,
            'status' => 'dry-run',
            'source_path' => $path,
            'pre_import_backup_id' => $backupId,
            'report' => json_encode($report, JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function restore(int $backupId): array
    {
        $backup = DB::table('backups')->where('id', $backupId)->first();
        abort_unless($backup !== null, 404);

        $payload = json_decode(Storage::disk($backup->disk)->get($backup->path), true) ?: [];
        $tables = $payload['tables'] ?? [];

        $this->replaceTables($tables);

        $restoredMedia = 0;
        $manifest = json_decode((string) $backup->manifest, true) ?: [];
        $exportZip = $manifest['export_zip'] ?? null;
        if (is_string($exportZip) && Storage::disk('local')->exists($exportZip)) {
            $restoredMedia = $this->restoreFilesFromZip(Storage::disk('local')->path($exportZip));
        }

        return [
            'ok' => true,
            'tables' => array_keys($tables),
            'media_files' => $restoredMedia,
            'restored_from' => $backup->path,
        ];
    }

    public function applyImport(string $path, string $mode = 'replace', ?int $userId = null): array
    {
        abort_unless(in_array($mode, ['merge', 'replace'], true), 422);

        $report = $this->dryRunImport($path);
        if (! ($report['ok'] ?? false)) {
            return $report;
        }

        $backupId = $this->backup('pre-import', $userId);
        $tables = $this->importTables($path);

        try {
            $mode === 'replace'
                ? $this->replaceTables($tables)
                : $this->mergeTables($tables);
            $mediaFiles = $this->restoreFilesFromZip($path);
        } catch (\Throwable $exception) {
            $this->restore($backupId);
            throw $exception;
        }

        $jobId = (int) DB::table('import_jobs')->insertGetId([
            'mode' => $mode,
            'status' => 'completed',
            'source_path' => $path,
            'pre_import_backup_id' => $backupId,
            'report' => json_encode(array_merge($report, ['media_files_restored' => $mediaFiles]), JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'ok' => true,
            'job_id' => $jobId,
            'pre_import_backup_id' => $backupId,
            'tables' => array_keys($tables),
            'media_files' => $mediaFiles,
        ];
    }

    /**
     * Store an uploaded site package and apply it (replace by default).
     *
     * @return array<string, mixed>
     */
    public function importUploadedPackage(UploadedFile $file, string $mode = 'replace', ?int $userId = null): array
    {
        Storage::disk('local')->makeDirectory('imports');
        $filename = 'import-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';
        $stored = $file->storeAs('imports', $filename, 'local');
        if (! is_string($stored) || $stored === '') {
            throw new \RuntimeException('Unable to store uploaded site package.');
        }

        return $this->applyImport(Storage::disk('local')->path($stored), $mode, $userId);
    }

    public function exportAbsolutePath(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        abort_unless(str_starts_with($relative, 'exports/'), 404);
        abort_unless(Storage::disk('local')->exists($relative), 404);

        return Storage::disk('local')->path($relative);
    }

    private function manifest(bool $includeMedia = false, int $mediaFiles = 0): array
    {
        return [
            'product' => 'DiamondCMS',
            'version' => trim((string) @file_get_contents(base_path('VERSION'))) ?: '0.1.0',
            'created_at' => now()->toIso8601String(),
            'secrets_excluded' => true,
            'includes_media' => $includeMedia,
            'media_files' => $mediaFiles,
            'tables' => array_values(array_filter($this->exportTables, fn (string $table) => Schema::hasTable($table))),
        ];
    }

    private function tableDump(): array
    {
        return collect($this->exportTables)
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->get()->map(fn (object $row) => (array) $row)->all()])
            ->all();
    }

    private function importTables(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open import ZIP.');
        }

        $content = json_decode((string) $zip->getFromName('content.json'), true) ?: [];
        $zip->close();

        return collect($content['tables'] ?? [])
            ->only($this->exportTables)
            ->filter(fn ($rows, string $table): bool => Schema::hasTable($table) && is_array($rows))
            ->all();
    }

    /**
     * Pack every public media file + private uploads (excluding backup/export operational dirs).
     *
     * @return array{count: int, media_library_files: int, other_files: int, missing: array<int, string>}
     */
    private function packAllSiteFiles(ZipArchive $zip): array
    {
        /** @var array<string, true> $added */
        $added = [];
        $missing = [];

        $this->addDiskFilesToZip(
            $zip,
            'public',
            'files/public/',
            $added,
            onlyPrefixes: null,
            excludePrefixes: null,
        );

        $this->addDiskFilesToZip(
            $zip,
            'local',
            'files/local/',
            $added,
            onlyPrefixes: null,
            excludePrefixes: ['backups', 'exports', 'imports'],
        );

        $this->addMediaLibraryPathsToZip($zip, $added, $missing);

        $mediaLibraryTracked = 0;
        foreach (array_keys($added) as $zipName) {
            if (str_starts_with($zipName, 'files/public/')) {
                $mediaLibraryTracked++;
            }
        }

        return [
            'count' => count($added),
            'media_library_files' => $mediaLibraryTracked,
            'other_files' => max(0, count($added) - $mediaLibraryTracked),
            'missing' => $missing,
        ];
    }

    /**
     * Ensure every media_items original + variant path is in the ZIP (even if missed by allFiles()).
     *
     * @param  array<string, true>  $added
     * @param  array<int, string>  $missing
     */
    private function addMediaLibraryPathsToZip(ZipArchive $zip, array &$added, array &$missing): void
    {
        if (! Schema::hasTable('media_items')) {
            return;
        }

        foreach (DB::table('media_items')->get(['disk', 'path', 'variants']) as $item) {
            $disk = (string) ($item->disk ?: 'public');
            $paths = [(string) $item->path];
            $variants = is_string($item->variants) ? json_decode($item->variants, true) : $item->variants;
            if (is_array($variants)) {
                foreach ($variants as $variantPath) {
                    if (is_string($variantPath) && $variantPath !== '') {
                        $paths[] = $variantPath;
                    }
                }
            }

            foreach (array_unique($paths) as $path) {
                $path = ltrim(str_replace('\\', '/', $path), '/');
                if ($path === '' || str_contains($path, '..')) {
                    continue;
                }

                $zipName = 'files/'.$disk.'/'.$path;
                if (isset($added[$zipName])) {
                    continue;
                }

                if (! array_key_exists($disk, config('filesystems.disks', [])) || ! Storage::disk($disk)->exists($path)) {
                    $missing[] = $disk.':'.$path;

                    continue;
                }

                $absolute = Storage::disk($disk)->path($path);
                if (! is_file($absolute)) {
                    $missing[] = $disk.':'.$path;

                    continue;
                }

                // Prefer embedding bytes so the ZIP stays valid even if source files move before close().
                $contents = Storage::disk($disk)->get($path);
                if (! is_string($contents)) {
                    $missing[] = $disk.':'.$path;

                    continue;
                }
                $zip->addFromString($zipName, $contents);
                $added[$zipName] = true;
            }
        }
    }

    /**
     * @param  array<string, true>  $added
     * @param  array<int, string>|null  $onlyPrefixes
     * @param  array<int, string>|null  $excludePrefixes
     */
    private function addDiskFilesToZip(
        ZipArchive $zip,
        string $disk,
        string $zipPrefix,
        array &$added,
        ?array $onlyPrefixes = null,
        ?array $excludePrefixes = null,
    ): int {
        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            return 0;
        }

        $count = 0;
        foreach (Storage::disk($disk)->allFiles() as $file) {
            $file = ltrim(str_replace('\\', '/', $file), '/');
            if ($file === '' || str_contains($file, '..')) {
                continue;
            }

            if ($onlyPrefixes !== null) {
                $allowed = false;
                foreach ($onlyPrefixes as $prefix) {
                    $prefix = rtrim($prefix, '/');
                    if (str_starts_with($file, $prefix.'/') || $file === $prefix) {
                        $allowed = true;
                        break;
                    }
                }
                if (! $allowed) {
                    continue;
                }
            }

            if ($excludePrefixes !== null) {
                $excluded = false;
                foreach ($excludePrefixes as $prefix) {
                    $prefix = rtrim($prefix, '/');
                    if (str_starts_with($file, $prefix.'/') || $file === $prefix) {
                        $excluded = true;
                        break;
                    }
                }
                if ($excluded) {
                    continue;
                }
            }

            $zipName = $zipPrefix.$file;
            if (isset($added[$zipName])) {
                continue;
            }

            $absolute = Storage::disk($disk)->path($file);
            if (! is_file($absolute)) {
                continue;
            }

            $contents = @file_get_contents($absolute);
            if ($contents === false) {
                continue;
            }

            $zip->addFromString($zipName, $contents);
            $added[$zipName] = true;
            $count++;
        }

        return $count;
    }

    private function restoreFilesFromZip(string $path): int
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return 0;
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }

            $disk = null;
            $relative = null;
            if (str_starts_with($name, 'files/public/')) {
                $disk = 'public';
                $relative = substr($name, strlen('files/public/'));
            } elseif (str_starts_with($name, 'files/local/')) {
                $disk = 'local';
                $relative = substr($name, strlen('files/local/'));
            }

            if ($disk === null || $relative === null || $relative === '' || str_contains($relative, '..')) {
                continue;
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                continue;
            }

            Storage::disk($disk)->put($relative, $contents);
            $count++;
        }

        $zip->close();

        return $count;
    }

    private function replaceTables(array $tables): void
    {
        DB::transaction(function () use ($tables): void {
            try {
                Schema::disableForeignKeyConstraints();
                foreach (array_reverse($this->exportTables) as $table) {
                    if (isset($tables[$table]) && Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }

            $this->mergeTables($tables);
        });
    }

    private function mergeTables(array $tables): void
    {
        DB::transaction(function () use ($tables): void {
            foreach ($this->exportTables as $table) {
                if (! isset($tables[$table]) || ! Schema::hasTable($table)) {
                    continue;
                }

                foreach ($tables[$table] as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    if (array_key_exists('id', $row)) {
                        DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                    } else {
                        DB::table($table)->insert($row);
                    }
                }
            }
        });
    }
}
