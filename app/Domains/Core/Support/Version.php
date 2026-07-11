<?php

declare(strict_types=1);

namespace App\Domains\Core\Support;

final class Version
{
    public static function current(): string
    {
        $file = base_path('VERSION');
        if (is_readable($file)) {
            return trim((string) file_get_contents($file)) ?: '0.1.0';
        }

        return (string) config('diamondcms.version', '0.1.0');
    }

    public static function schema(): int
    {
        return (int) config('diamondcms.schema_version', 1);
    }

    public static function builderSchema(): int
    {
        return (int) config('diamondcms.builder_schema_version', 1);
    }
}
