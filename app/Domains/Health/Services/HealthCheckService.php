<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Core\Support\Version;
use App\Domains\Installer\Support\InstallState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class HealthCheckService
{
    public function publicStatus(): array
    {
        return [
            'status' => 'ok',
            'app' => config('diamondcms.name', 'DiamondCMS'),
            'version' => Version::current(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function detailedStatus(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'installed' => InstallState::isInstalled(),
            'php' => PHP_VERSION,
            'extensions' => [
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'gd' => extension_loaded('gd'),
                'zip' => extension_loaded('zip'),
                'intl' => extension_loaded('intl'),
            ],
        ];

        $ok = ($checks['database']['ok'] ?? false) && ($checks['storage']['ok'] ?? false);

        return array_merge($this->publicStatus(), [
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['ok' => true, 'driver' => (string) config('database.default')];
        } catch (Throwable) {
            return ['ok' => false, 'error' => 'connection_failed'];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $disk->put('.healthcheck', 'ok');
            $disk->delete('.healthcheck');
            $writable = is_writable(storage_path()) && is_writable(storage_path('framework'));

            return ['ok' => $writable, 'writable' => $writable];
        } catch (Throwable) {
            return ['ok' => false, 'error' => 'storage_failed'];
        }
    }
}
