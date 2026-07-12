<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) @file_get_contents($root.'/VERSION')) ?: date('Y.m.d');
$releaseDir = $root.'/storage/app/releases/diamondcms-'.$version;
$zipPath = $root.'/storage/app/releases/diamondcms-'.$version.'.zip';
$excludes = array_flip(config_excludes($root));

run('composer install --no-dev --prefer-dist --optimize-autoloader', $root);
run('npm ci', $root);
run('npm run build', $root);

remove_dir($releaseDir);
@mkdir($releaseDir, 0775, true);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);

foreach ($iterator as $item) {
    $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($root) + 1));
    if (is_excluded($relative, $excludes)) {
        continue;
    }

    $target = $releaseDir.'/'.$relative;
    if ($item->isDir()) {
        @mkdir($target, 0775, true);

        continue;
    }

    @mkdir(dirname($target), 0775, true);
    copy($item->getPathname(), $target);
}

file_put_contents($releaseDir.'/release-manifest.json', json_encode([
    'product' => 'DiamondCMS',
    'version' => $version,
    'built_at' => gmdate(DATE_ATOM),
    'requires' => [
        'php' => '^8.3',
        'database' => 'MySQL/MariaDB',
    ],
    'excludes' => array_keys($excludes),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

remove_file($zipPath);
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    throw new RuntimeException('Unable to create release zip.');
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($releaseDir, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if ($file->isFile()) {
        $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($releaseDir) + 1));
    }
}
$zip->close();

$checksum = hash_file('sha256', $zipPath);
file_put_contents($zipPath.'.sha256', $checksum.'  '.basename($zipPath).PHP_EOL);

echo "Release built: {$zipPath}".PHP_EOL;
echo "SHA-256: {$checksum}".PHP_EOL;

function run(string $command, string $cwd): void
{
    passthru('cd '.escapeshellarg($cwd).' && '.$command, $code);
    if ($code !== 0) {
        throw new RuntimeException("Command failed: {$command}");
    }
}

/** @return array<int, string> */
function config_excludes(string $root): array
{
    return [
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
    ];
}

function is_excluded(string $relative, array $excludes): bool
{
    foreach (array_keys($excludes) as $exclude) {
        $exclude = trim((string) $exclude, '/');
        if ($relative === $exclude || str_starts_with($relative, $exclude.'/')) {
            return true;
        }
    }

    return false;
}

function remove_dir(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($path);
}

function remove_file(string $path): void
{
    if (is_file($path)) {
        unlink($path);
    }
}
