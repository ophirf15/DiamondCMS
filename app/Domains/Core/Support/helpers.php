<?php

declare(strict_types=1);

use App\Domains\Core\Support\Version;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

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

if (! function_exists('diamondcms_custom_head')) {
    function diamondcms_custom_head(): HtmlString
    {
        try {
            $settings = cache()->remember('diamondcms.custom_head', 60, fn () => [
                'css' => json_decode((string) DB::table('settings')->where('key', 'custom_css')->value('value'), true) ?: '',
                'js' => json_decode((string) DB::table('settings')->where('key', 'custom_js_head')->value('value'), true) ?: '',
            ]);
        } catch (\Throwable) {
            $settings = ['css' => '', 'js' => ''];
        }

        return new HtmlString("<style id=\"diamondcms-custom-css\">\n".$settings['css']."\n</style>\n".$settings['js']);
    }
}
