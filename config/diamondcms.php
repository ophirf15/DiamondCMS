<?php

declare(strict_types=1);

return [
    'name' => 'DiamondCMS',
    'version' => trim((string) @file_get_contents(base_path('VERSION'))) ?: '0.1.0',
    'schema_version' => 1,
    'builder_schema_version' => 1,
    'installer_lock_path' => storage_path('app/installed.lock'),
    'scheduler_token' => env('DIAMONDCMS_SCHEDULER_TOKEN'),
    'recovery_key' => env('DIAMONDCMS_RECOVERY_KEY'),
    'media' => [
        'max_upload_kb' => 51200,
        'svg_enabled' => false,
    ],
    'release' => [
        'exclude' => [
            '.env',
            '.git',
            '.github',
            'node_modules',
            'tests',
            'DiamondCMS_Phased_Plan',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/app/public',
            'storage/app/releases',
            '.phpunit.result.cache',
            'phpunit.xml',
            'vite.config.js',
            'vite.config.ts',
            'tsconfig.json',
            'package.json',
            'package-lock.json',
        ],
    ],
    'health' => [
        'public_keys' => ['status', 'app', 'version', 'timestamp'],
    ],
];
