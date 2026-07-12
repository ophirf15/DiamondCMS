<?php

declare(strict_types=1);

use App\Domains\Core\Support\Version;
use App\Domains\Design\Support\DesignManager;
use App\Domains\Design\Support\MenuManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

if (! function_exists('diamondcms_version')) {
    function diamondcms_version(): string
    {
        return Version::current();
    }
}

if (! function_exists('diamondcms_asset')) {
    function diamondcms_asset(string $path): string
    {
        return asset(ltrim($path, '/'));
    }
}

if (! function_exists('diamondcms_setting')) {
    function diamondcms_setting(string $key, mixed $default = null): mixed
    {
        try {
            $raw = DB::table('settings')->where('key', $key)->value('value');
        } catch (Throwable) {
            return $default;
        }

        if ($raw === null) {
            return $default;
        }

        $decoded = json_decode((string) $raw, true);

        return $decoded === null && ! is_numeric($raw) ? $default : $decoded;
    }
}

if (! function_exists('diamondcms_site_name')) {
    function diamondcms_site_name(): string
    {
        $name = diamondcms_setting('site_name');

        return is_string($name) && $name !== ''
            ? $name
            : (string) config('app.name', config('diamondcms.name', 'DiamondCMS'));
    }
}

if (! function_exists('diamondcms_menu')) {
    /** @return array<int, array<string, mixed>> */
    function diamondcms_menu(string $location): array
    {
        try {
            return MenuManager::publicItems($location);
        } catch (Throwable) {
            return [];
        }
    }
}

if (! function_exists('diamondcms_logo_url')) {
    function diamondcms_logo_url(): string
    {
        try {
            return DesignManager::logoUrl();
        } catch (Throwable) {
            return '/brand/logo-primary-gold.svg';
        }
    }
}

if (! function_exists('diamondcms_custom_head')) {
    function diamondcms_custom_head(): HtmlString
    {
        try {
            $settings = cache()->remember('diamondcms.custom_head', 60, fn () => [
                'css' => json_decode((string) DB::table('settings')->where('key', 'custom_css')->value('value'), true) ?: '',
                'js' => json_decode((string) DB::table('settings')->where('key', 'custom_js_head')->value('value'), true) ?: '',
            ]);
        } catch (Throwable) {
            $settings = ['css' => '', 'js' => ''];
        }

        return new HtmlString("<style id=\"diamondcms-custom-css\">\n".$settings['css']."\n</style>\n".$settings['js']);
    }
}
