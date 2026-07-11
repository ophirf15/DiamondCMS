<?php

use App\Domains\Activity\Support\ActivityLogger;
use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Forms\Support\FormManager;
use App\Domains\Health\Http\Controllers\HealthController;
use App\Domains\Installer\Support\InstallState;
use App\Domains\Portfolio\Support\PortfolioManager;
use App\Domains\Resume\Support\ResumeManager;
use App\Domains\SEO\Support\SeoManager;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

Route::get('/', function () {
    if (! Schema::hasTable('pages')) {
        return view('public.landing');
    }

    $home = DB::table('pages')->where('slug', 'home')->where('status', 'published')->first();

    return $home ? view('public.page', ['page' => $home, 'content' => BuilderDocument::render(json_decode($home->builder_json, true) ?: BuilderDocument::empty($home->title))]) : view('public.landing');
})->name('home');

Route::get('/health', [HealthController::class, 'public'])->name('health.public');
Route::get('/admin/health', function (Request $request, HealthController $health) {
    if (! $request->user()) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    abort_unless($request->user()->canAccessAdmin(), 403);

    return $health->detailed($request);
})->name('health.admin');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            ActivityLogger::log('auth.login_failed', null, ['email' => $request->string('email')->toString()], $request);

            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        if (! $user->canAccessAdmin()) {
            auth()->logout();

            return back()->withErrors(['email' => 'This account cannot access DiamondCMS.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLogger::log('auth.login', $user, [], $request);

        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            $request->session()->put('2fa:user:id', $user->id);
            auth()->logout();

            return redirect()->route('two-factor.challenge');
        }

        return redirect()->intended(route('admin.dashboard'));
    })->middleware('throttle:6,1');

    Route::get('/two-factor/challenge', fn () => view('auth.two-factor'))->name('two-factor.challenge');
    Route::post('/two-factor/challenge', function (Request $request) {
        $request->validate(['code' => ['required', 'string']]);
        $user = User::findOrFail($request->session()->get('2fa:user:id'));
        $google2fa = app('pragmarx.google2fa');

        if (! $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'The two-factor code is invalid.']);
        }

        auth()->login($user);
        $request->session()->forget('2fa:user:id');
        $request->session()->regenerate();
        ActivityLogger::log('auth.two_factor_passed', $user, [], $request);

        return redirect()->intended(route('admin.dashboard'));
    })->middleware('throttle:6,1');

    Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('password.request');
    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If the account exists, a password reset link has been sent.');
    })->name('password.email')->middleware('throttle:6,1');

    Route::get('/reset-password/{token}', fn (string $token, Request $request) => view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]))->name('password.reset');
    Route::post('/reset-password', function (Request $request) {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:12'],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password reset. Sign in with the new password.')
            : back()->withErrors(['email' => __($status)]);
    })->name('password.update');
});

Route::post('/logout', function (Request $request) {
    ActivityLogger::log('auth.logout', $request->user(), [], $request);
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::prefix('install')->name('install.')->group(function (): void {
    Route::get('/', function () {
        return InstallState::isInstalled()
            ? redirect()->route('login')->with('status', 'DiamondCMS is already installed.')
            : view('installer.wizard', ['requirements' => installer_requirements()]);
    })->name('wizard');

    Route::post('/requirements', function () {
        return response()->json(installer_requirements());
    })->name('requirements');

    Route::post('/database', function (Request $request) {
        abort_if(InstallState::isInstalled(), 403);
        $data = $request->validate([
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'integer'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        config(['database.connections.install_check' => [
            'driver' => 'mysql',
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);
        DB::connection('install_check')->getPdo();
        session(['installer.db' => $data]);

        return back()->with('status', 'Database connection verified.');
    })->name('database');

    Route::post('/finish', function (Request $request) {
        abort_if(InstallState::isInstalled(), 403);
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'base_url' => ['required', 'url'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'confirmed', 'min:12'],
        ]);

        installer_write_env(array_merge(session('installer.db', []), [
            'APP_NAME' => $data['site_name'],
            'APP_URL' => $data['base_url'],
        ]));
        Artisan::call('key:generate', ['--force' => true]);
        Artisan::call('migrate', ['--force' => true]);

        $admin = User::updateOrCreate(
            ['email' => $data['admin_email']],
            ['name' => $data['admin_name'], 'password' => $data['admin_password'], 'is_admin' => true, 'is_disabled' => false],
        );
        DB::table('settings')->updateOrInsert(['key' => 'site_name'], ['value' => json_encode($data['site_name']), 'group' => 'general', 'is_public' => true, 'updated_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        InstallState::markInstalled(['admin_email' => $admin->email]);
        ActivityLogger::log('installer.completed', $admin, [], $request);

        return redirect()->route('login')->with('status', 'DiamondCMS installation is complete.');
    })->name('finish');

    Route::post('/recovery/clear-lock', function (Request $request) {
        $request->validate(['recovery_key' => ['required', 'string']]);
        abort_unless(hash_equals((string) env('DIAMONDCMS_RECOVERY_KEY'), $request->string('recovery_key')->toString()), 403);
        InstallState::clearLock();

        return back()->with('status', 'Install lock cleared.');
    })->name('recovery.clear-lock');
});

Route::get('/preview/{token}', function (string $token) {
    $preview = DB::table('preview_tokens')->where('token', $token)->where(function ($query): void {
        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })->firstOrFail();
    $page = DB::table('pages')->where('id', $preview->page_id)->firstOrFail();

    return view('public.page', ['page' => $page, 'content' => BuilderDocument::render(json_decode($page->builder_json, true) ?: BuilderDocument::empty($page->title))]);
})->name('preview');

Route::post('/scheduler/publish', function (Request $request) {
    $token = (string) config('diamondcms.scheduler_token');
    abort_if($token && ! hash_equals($token, $request->string('token')->toString()), 403);
    $count = DB::table('pages')->where('status', 'scheduled')->where('scheduled_for', '<=', now())->update(['status' => 'published', 'published_at' => now(), 'updated_at' => now()]);

    return response()->json(['published' => $count]);
})->name('scheduler.publish');

Route::get('/resume/{slug}', function (string $slug) {
    $variant = DB::table('resume_variants')->where('slug', $slug)->where('visibility', 'public')->firstOrFail();
    $profile = DB::table('resume_profiles')->where('id', $variant->resume_profile_id)->firstOrFail();
    $sections = DB::table('resume_sections')->where('resume_profile_id', $profile->id)->orderBy('sort_order')->get();

    return view('public.resume', compact('variant', 'profile', 'sections'));
})->name('resume.public');

Route::get('/resume/share/{token}', function (string $token) {
    $share = DB::table('resume_share_links')->where('token', $token)->where(function ($query): void {
        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })->firstOrFail();
    $variant = DB::table('resume_variants')->where('id', $share->resume_variant_id)->firstOrFail();
    $profile = DB::table('resume_profiles')->where('id', $variant->resume_profile_id)->firstOrFail();
    $sections = DB::table('resume_sections')->where('resume_profile_id', $profile->id)->orderBy('sort_order')->get();

    return view('public.resume', compact('variant', 'profile', 'sections'));
})->name('resume.share');

Route::get('/resume/{slug}/print', function (string $slug, ResumeManager $resumes) {
    $variant = DB::table('resume_variants')->where('slug', $slug)->where('visibility', 'public')->firstOrFail();

    return $resumes->pdfResponse((int) $variant->id);
})->name('resume.print');

Route::get('/projects', function (Request $request, PortfolioManager $portfolio) {
    return view('public.projects.index', ['projects' => $portfolio->publicProjects($request->only(['category', 'skill', 'year', 'type', 'status', 'featured']))]);
})->name('projects.index');

Route::get('/projects/{slug}', function (string $slug, PortfolioManager $portfolio) {
    return view('public.projects.show', ['project' => $portfolio->publicProject($slug)]);
})->name('projects.show');

Route::get('/forms/{slug}', function (string $slug, FormManager $forms) {
    return view('public.forms.show', ['form' => $forms->publicForm($slug)]);
})->name('forms.show');

Route::post('/forms/{slug}', function (string $slug, Request $request, FormManager $forms) {
    $forms->submit($slug, $request);
    $form = $forms->publicForm($slug);

    return $form->redirect_url ? redirect()->to($form->redirect_url) : back()->with('status', $form->success_message);
})->middleware('throttle:20,1')->name('forms.submit');

Route::get('/sitemap.xml', fn (SeoManager $seo) => response($seo->sitemap(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']))->name('sitemap');
Route::get('/robots.txt', fn (SeoManager $seo) => response($seo->robots(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('robots');

Route::get('/404', fn () => response()->view('public.404', [], 404))->name('not-found');

Route::get('/{slug}', function (string $slug, Request $request) {
    if ($redirect = app(SeoManager::class)->redirectFor($slug)) {
        return redirect()->to($redirect->target, (int) $redirect->status_code);
    }

    $page = DB::table('pages')->where('slug', $slug)->where('status', 'published')->firstOrFail();
    if ($page->password_hash && ! Hash::check((string) $request->session()->get('page-password-'.$page->id), $page->password_hash)) {
        return view('public.password', ['page' => $page]);
    }

    return view('public.page', ['page' => $page, 'content' => BuilderDocument::render(json_decode($page->builder_json, true) ?: BuilderDocument::empty($page->title))]);
})->name('page.show');

Route::post('/{slug}/password', function (string $slug, Request $request) {
    $page = DB::table('pages')->where('slug', $slug)->where('status', 'published')->firstOrFail();
    $request->validate(['password' => ['required', 'string']]);
    abort_unless($page->password_hash && Hash::check($request->string('password')->toString(), $page->password_hash), 403);
    $request->session()->put('page-password-'.$page->id, $request->string('password')->toString());

    return redirect()->route('page.show', $slug);
})->name('page.password');

if (! function_exists('installer_requirements')) {
    function installer_requirements(): array
    {
        return [
            'php' => ['ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'current' => PHP_VERSION, 'required' => '>=8.3'],
            'extensions' => collect(['pdo_mysql', 'mbstring', 'openssl', 'gd', 'zip', 'intl', 'fileinfo'])->mapWithKeys(fn (string $extension) => [$extension => extension_loaded($extension)])->all(),
            'writable' => [
                'storage' => is_writable(storage_path()),
                'cache' => is_writable(base_path('bootstrap/cache')),
            ],
            'rewrite' => ['ok' => true, 'note' => 'Verified by reaching the installer route.'],
        ];
    }
}

if (! function_exists('installer_write_env')) {
    function installer_write_env(array $values): void
    {
        $path = base_path('.env');
        $contents = is_file($path) ? file_get_contents($path) : file_get_contents(base_path('.env.example'));
        $map = [
            'APP_NAME' => $values['APP_NAME'] ?? 'DiamondCMS',
            'APP_URL' => $values['APP_URL'] ?? url('/'),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $values['db_host'] ?? '127.0.0.1',
            'DB_PORT' => $values['db_port'] ?? '3306',
            'DB_DATABASE' => $values['db_database'] ?? 'diamondcms',
            'DB_USERNAME' => $values['db_username'] ?? 'diamondcms',
            'DB_PASSWORD' => $values['db_password'] ?? '',
        ];

        foreach ($map as $key => $value) {
            $line = $key.'="'.str_replace('"', '\"', (string) $value).'"';
            $contents = preg_match('/^'.$key.'=.*/m', (string) $contents)
                ? preg_replace('/^'.$key.'=.*/m', $line, (string) $contents)
                : rtrim((string) $contents).PHP_EOL.$line.PHP_EOL;
        }

        Storage::disk('local')->put('installer-recovery.json', json_encode(['updated_at' => now(), 'keys' => array_keys($map)], JSON_PRETTY_PRINT));
        file_put_contents($path, $contents);
    }
}
