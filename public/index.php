<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Shared hosts / incomplete ZIPs may omit empty storage dirs Laravel requires at boot.
foreach ([
    'storage/framework/views',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/logs',
    'bootstrap/cache',
] as $relative) {
    $path = __DIR__.'/../'.$relative;
    if (! is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

// Fresh shared-host deploys often have no APP_KEY yet — generate one before Laravel boots.
$envPath = __DIR__.'/../.env';
$envExample = __DIR__.'/../.env.example';
if (! is_file($envPath) && is_file($envExample)) {
    @copy($envExample, $envPath);
}
if (is_file($envPath)) {
    $envContents = (string) file_get_contents($envPath);
    if (! preg_match('/^APP_KEY=\s*base64:.+/m', $envContents)) {
        $generatedKey = 'base64:'.base64_encode(random_bytes(32));
        if (preg_match('/^APP_KEY=.*/m', $envContents)) {
            $envContents = preg_replace('/^APP_KEY=.*/m', 'APP_KEY='.$generatedKey, $envContents) ?? $envContents;
        } else {
            $envContents = rtrim($envContents).PHP_EOL.'APP_KEY='.$generatedKey.PHP_EOL;
        }
        @file_put_contents($envPath, $envContents);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
