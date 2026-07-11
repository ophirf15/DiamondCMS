<?php

declare(strict_types=1);

namespace App\Domains\Backups\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

final class BackupManager
{
    /** @var array<int, string> */
    private array $exportTables = [
        'settings', 'pages', 'page_revisions', 'menus', 'menu_items', 'builder_templates',
        'media_folders', 'media_items', 'resume_profiles', 'resume_sections', 'resume_variants',
        'portfolio_categories', 'projects', 'project_relations', 'personal_contents',
        'testimonials', 'galleries', 'timeline_entries', 'forms',
    ];

    public function backup(string $type = 'full', ?int $userId = null): int
    {
        $manifest = $this->manifest();
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

    public function exportSite(?int $userId = null): string
    {
        $backupId = $this->backup('export', $userId);
        $backup = DB::table('backups')->where('id', $backupId)->first();
        $zipPath = storage_path('app/exports/diamondcms-export-'.now()->format('Ymd-His').'.zip');
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0775, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(Storage::disk('local')->path($backup->path), 'content.json');
        $zip->addFromString('manifest.json', json_encode($this->manifest(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $zip->addFromString('README.txt', "DiamondCMS export. Secrets, API keys, SMTP passwords, and .env are excluded.\n");
        $zip->close();

        return $zipPath;
    }

    public function dryRunImport(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'errors' => ['Unable to open import ZIP.']];
        }

        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true) ?: [];
        $content = json_decode((string) $zip->getFromName('content.json'), true) ?: [];
        $zip->close();

        return [
            'ok' => isset($manifest['version'], $content['tables']),
            'manifest' => $manifest,
            'tables' => array_keys($content['tables'] ?? []),
            'mode_support' => ['merge', 'replace'],
            'warnings' => ['Secrets remain local and must be reconfigured after import.'],
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
        abort_unless($backup, 404);

        $payload = json_decode(Storage::disk($backup->disk)->get($backup->path), true) ?: [];
        $tables = $payload['tables'] ?? [];

        $this->replaceTables($tables);

        return [
            'ok' => true,
            'tables' => array_keys($tables),
            'restored_from' => $backup->path,
        ];
    }

    public function applyImport(string $path, string $mode = 'merge', ?int $userId = null): array
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
        } catch (\Throwable $exception) {
            $this->restore($backupId);
            throw $exception;
        }

        $jobId = (int) DB::table('import_jobs')->insertGetId([
            'mode' => $mode,
            'status' => 'completed',
            'source_path' => $path,
            'pre_import_backup_id' => $backupId,
            'report' => json_encode($report, JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'ok' => true,
            'job_id' => $jobId,
            'pre_import_backup_id' => $backupId,
            'tables' => array_keys($tables),
        ];
    }

    private function manifest(): array
    {
        return [
            'product' => 'DiamondCMS',
            'version' => trim((string) @file_get_contents(base_path('VERSION'))) ?: '0.1.0',
            'created_at' => now()->toIso8601String(),
            'secrets_excluded' => true,
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
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open import ZIP.');
        }

        $content = json_decode((string) $zip->getFromName('content.json'), true) ?: [];
        $zip->close();

        return collect($content['tables'] ?? [])
            ->only($this->exportTables)
            ->filter(fn (array $rows, string $table): bool => Schema::hasTable($table))
            ->all();
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
