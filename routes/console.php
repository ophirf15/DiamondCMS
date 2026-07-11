<?php

use App\Domains\Installer\Support\InstallState;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('diamondcms:install {--name=DiamondCMS} {--url=http://localhost} {--admin=admin@example.com} {--password=}', function () {
    if (InstallState::isInstalled()) {
        $this->warn('DiamondCMS is already installed.');

        return 0;
    }

    $password = $this->option('password') ?: str()->password(16);
    Artisan::call('key:generate', ['--force' => true]);
    Artisan::call('migrate', ['--force' => true]);

    $admin = User::updateOrCreate(
        ['email' => $this->option('admin')],
        ['name' => 'Administrator', 'password' => $password, 'is_admin' => true, 'is_disabled' => false],
    );
    DB::table('settings')->updateOrInsert(['key' => 'site_name'], ['value' => json_encode($this->option('name')), 'group' => 'general', 'is_public' => true, 'updated_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('settings')->updateOrInsert(['key' => 'site_url'], ['value' => json_encode($this->option('url')), 'group' => 'general', 'is_public' => true, 'updated_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
    InstallState::markInstalled(['admin_email' => $admin->email, 'source' => 'cli']);

    $this->info('DiamondCMS installed.');
    if (! $this->option('password')) {
        $this->warn('Generated admin password: '.$password);
    }

    return 0;
})->purpose('Install DiamondCMS for local development.');

Artisan::command('diamondcms:publish-scheduled', function () {
    $count = DB::table('pages')
        ->where('status', 'scheduled')
        ->where('scheduled_for', '<=', now())
        ->update(['status' => 'published', 'published_at' => now(), 'updated_at' => now()]);

    $this->info("Published {$count} scheduled page(s).");

    return 0;
})->purpose('Publish scheduled pages from cron.');
