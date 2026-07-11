<?php

declare(strict_types=1);

namespace App\Domains\Installer\Support;

use App\Domains\Core\Support\Version;

final class InstallState
{
    public static function lockPath(): string
    {
        return (string) config('diamondcms.installer_lock_path', storage_path('app/installed.lock'));
    }

    public static function isInstalled(): bool
    {
        return is_file(self::lockPath());
    }

    public static function markInstalled(array $meta = []): void
    {
        $payload = array_merge([
            'installed_at' => now()->toIso8601String(),
            'version' => Version::current(),
        ], $meta);

        $dir = dirname(self::lockPath());
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(self::lockPath(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function clearLock(): void
    {
        if (is_file(self::lockPath())) {
            unlink(self::lockPath());
        }
    }

    public static function meta(): ?array
    {
        if (! self::isInstalled()) {
            return null;
        }

        $raw = file_get_contents(self::lockPath());

        return json_decode((string) $raw, true) ?: [];
    }
}
