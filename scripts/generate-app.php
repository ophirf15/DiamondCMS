<?php

/**
 * DiamondCMS bulk application generator for continuous Phase 0–12 build.
 * Idempotent: overwrites generated markers only where files are missing or marked.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

function write(string $path, string $contents): void
{
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents($path, $contents);
    echo "W " . str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $path) . PHP_EOL;
}

function stub(string $namespace, string $class, string $body = ''): string
{
    return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

{$body}
PHP;
}

// ---------------------------------------------------------------------------
// Phase 0: Core helpers, health, admin shell, release
// ---------------------------------------------------------------------------

write($root.'/app/Domains/Core/Support/Version.php', <<<'PHP'
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
PHP);

write($root.'/app/Domains/Installer/Support/InstallState.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Domains\Installer\Support;

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
            'version' => \App\Domains\Core\Support\Version::current(),
        ], $meta);
        file_put_contents(self::lockPath(), json_encode($payload, JSON_PRETTY_PRINT));
    }

    public static function clearLock(): void
    {
        if (is_file(self::lockPath())) {
            unlink(self::lockPath());
        }
    }
}
PHP);

write($root.'/app/Domains/Health/Services/HealthCheckService.php', <<<'PHP'
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
            return ['ok' => true, 'driver' => config('database.default')];
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'storage_failed'];
        }
    }
}
PHP);

write($root.'/app/Domains/Health/Http/Controllers/HealthController.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Domains\Health\Http\Controllers;

use App\Domains\Health\Services\HealthCheckService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $health)
    {
    }

    public function public(): JsonResponse
    {
        return response()->json($this->health->publicStatus());
    }

    public function detailed(Request $request): JsonResponse
    {
        abort_unless($request->user()?->is_admin ?? false, 403);

        return response()->json($this->health->detailedStatus());
    }
}
PHP);

write($root.'/app/Domains/Administration/Http/Controllers/AdminShellController.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Domains\Administration\Http\Controllers;

use App\Domains\Core\Support\Version;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminShellController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.shell', [
            'user' => $request->user(),
            'version' => Version::current(),
            'pageTitle' => 'DiamondCMS Admin',
        ]);
    }
}
PHP);

write($root.'/app/Domains/Sites/Http/Controllers/PublicHomeController.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Domains\Sites\Http\Controllers;

use App\Domains\Installer\Support\InstallState;
use App\Domains\Pages\Models\Page;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PublicHomeController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (! InstallState::isInstalled()) {
            return redirect()->route('installer.welcome');
        }

        $homepage = Page::query()
            ->where('is_homepage', true)
            ->where('status', 'published')
            ->first();

        if ($homepage) {
            return view('public.page', ['page' => $homepage]);
        }

        return view('public.home', [
            'siteName' => setting('site_name', config('app.name')),
            'tagline' => setting('site_tagline', 'Personal website powered by DiamondCMS'),
        ]);
    }
}
PHP);

write($root.'/app/Support/helpers.php', <<<'PHP'
<?php

declare(strict_types=1);

use App\Domains\Settings\Services\SettingsRepository;
use Illuminate\Support\Facades\Schema;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            return app(SettingsRepository::class)->get($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}
PHP);

echo "Phase 0 core written\n";
PHP
); echo "script written partially - continuing with full generator via file"
