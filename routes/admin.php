<?php

declare(strict_types=1);

use App\Domains\Activity\Support\ActivityLogger;
use App\Domains\AI\Support\AiManager;
use App\Domains\Analytics\Support\AnalyticsManager;
use App\Domains\Backups\Support\BackupManager;
use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Builder\Support\StarterTemplates;
use App\Domains\Design\Support\DesignManager;
use App\Domains\Design\Support\MenuManager;
use App\Domains\Forms\Support\FormManager;
use App\Domains\Installer\Support\InstallState;
use App\Domains\Mail\Support\MailSettingsManager;
use App\Domains\Media\Support\MediaManager;
use App\Domains\Portfolio\Support\PortfolioManager;
use App\Domains\Resume\Support\ResumeManager;
use App\Domains\SEO\Support\SeoManager;
use App\Domains\Updates\Support\UpdateManager;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));

    Route::post('/portfolio/categories', function (Request $request, PortfolioManager $portfolio) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        return response()->json(['id' => $portfolio->createCategory($data)]);
    })->name('portfolio.categories.store');

    Route::post('/portfolio/projects', function (Request $request, PortfolioManager $portfolio) {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:portfolio_categories,id'],
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'visibility' => ['nullable', 'in:private,public,unlisted'],
            'is_featured' => ['nullable', 'boolean'],
            'summary' => ['nullable', 'string'],
            'case_study' => ['nullable', 'string'],
            'skills' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'year' => ['nullable', 'integer'],
            'started_on' => ['nullable', 'date'],
            'completed_on' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'url' => ['nullable', 'url'],
            'builder_json' => ['nullable', 'array'],
        ]);

        return response()->json(['id' => $portfolio->createProject($data, $request->user()->id)]);
    })->name('portfolio.projects.store');

    Route::get('/forms', function (FormManager $forms) {
        return response()->json($forms->list());
    })->name('forms.index');

    Route::post('/forms', function (Request $request, FormManager $forms) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'schema' => ['required', 'array'],
            'notifications' => ['nullable', 'array'],
            'spam_config' => ['nullable', 'array'],
            'success_message' => ['nullable', 'string'],
            'redirect_url' => ['nullable', 'url'],
            'retention_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $id = $forms->createForm($data, $request->user()->id);

        return response()->json($forms->find($id), 201);
    })->name('forms.store');

    Route::get('/forms/{form}', function (int $form, FormManager $forms) {
        return response()->json($forms->find($form));
    })->name('forms.show');

    Route::put('/forms/{form}', function (int $form, Request $request, FormManager $forms) {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:190'],
            'slug' => ['sometimes', 'string', 'max:190'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'schema' => ['sometimes', 'array'],
            'notifications' => ['nullable', 'array'],
            'spam_config' => ['nullable', 'array'],
            'success_message' => ['nullable', 'string'],
            'redirect_url' => ['nullable', 'url'],
            'retention_days' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($forms->updateForm($form, $data, $request->user()->id));
    })->name('forms.update');

    Route::get('/forms/{form}/submissions', function (int $form, FormManager $forms) {
        return response()->json($forms->submissions($form));
    })->name('forms.submissions');

    Route::get('/forms/{form}/submissions.csv', function (int $form, FormManager $forms) {
        return response($forms->csv($form), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="form-'.$form.'-submissions.csv"',
        ]);
    })->name('forms.submissions.csv');

    Route::post('/forms/ensure-contact', function (Request $request, FormManager $forms) {
        return response()->json($forms->ensureContactForm($request->user()->id));
    })->name('forms.ensure-contact');

    Route::get('/mail/settings', function (MailSettingsManager $mail) {
        return response()->json($mail->publicConfig());
    })->name('mail.settings.show');

    Route::post('/mail/settings', function (Request $request, MailSettingsManager $mail) {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:190'],
            'port' => ['nullable', 'integer'],
            'username' => ['nullable', 'string', 'max:190'],
            'password' => ['nullable', 'string'],
            'encryption' => ['nullable', 'in:tls,ssl,null'],
            'from_address' => ['required', 'email'],
            'from_name' => ['nullable', 'string', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return response()->json(['id' => $mail->save($data, $request->user()->id)]);
    })->name('mail.settings.store');

    Route::post('/mail/test', function (Request $request, MailSettingsManager $mail) {
        $data = $request->validate(['recipient' => ['required', 'email']]);

        return response()->json(['delivery_log_id' => $mail->testSend($data['recipient'])]);
    })->name('mail.test');

    Route::post('/ai/providers', function (Request $request, AiManager $ai) {
        $data = $request->validate([
            'provider' => ['required', 'in:openai,anthropic,gemini'],
            'name' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string'],
            'base_url' => ['nullable', 'url'],
            'models' => ['nullable', 'array'],
            'default_model' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
            'monthly_token_limit' => ['nullable', 'integer'],
            'monthly_cost_limit' => ['nullable', 'numeric'],
        ]);

        return response()->json(['id' => $ai->saveProvider($data, $request->user()->id)]);
    })->name('ai.providers.store');

    Route::post('/ai/generate-draft-page', function (Request $request, AiManager $ai) {
        $data = $request->validate(['title' => ['required', 'string', 'max:190'], 'summary' => ['nullable', 'string']]);

        return response()->json(['generation_id' => $ai->generateDraftPage($data, $request->user()->id)]);
    })->name('ai.generate-draft-page');

    Route::post('/ai/generations/{generation}/approve', function (int $generation, Request $request, AiManager $ai) {
        return response()->json(['page_id' => $ai->approveGeneration($generation, $request->user()->id)]);
    })->name('ai.generations.approve');

    Route::post('/redirects', function (Request $request) {
        $data = $request->validate([
            'source' => ['required', 'string', 'starts_with:/'],
            'target' => ['required', 'string'],
            'status_code' => ['nullable', 'integer', 'in:301,302,307,308'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $id = DB::table('redirects')->insertGetId([
            'source' => $data['source'],
            'target' => $data['target'],
            'status_code' => $data['status_code'] ?? 301,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id]);
    })->name('redirects.store');

    Route::post('/seo/audit', function (Request $request, SeoManager $seo) {
        $data = $request->validate(['html' => ['required', 'string'], 'subject_type' => ['required', 'string'], 'subject_id' => ['required', 'integer']]);

        return response()->json(['id' => $seo->auditHtml($data['html'], $data['subject_type'], (int) $data['subject_id'])]);
    })->name('seo.audit');

    Route::post('/backups', fn (Request $request, BackupManager $backups) => response()->json([
        'id' => $backups->backup($request->string('type', 'full')->toString(), $request->user()->id),
    ]))->name('backups.store');

    Route::post('/exports', function (Request $request, BackupManager $backups) {
        $export = $backups->exportSite($request->user()->id);

        return response()->json($export);
    })->name('exports.store');

    Route::post('/imports/dry-run', function (Request $request, BackupManager $backups) {
        $data = $request->validate(['path' => ['required', 'string'], 'mode' => ['nullable', 'in:dry-run,merge,replace']]);
        $report = $backups->dryRunImport($data['path']);
        $jobId = $backups->recordImport($data['path'], $data['mode'] ?? 'dry-run', $report, $request->user()->id);

        return response()->json(['id' => $jobId, 'report' => $report]);
    })->name('imports.dry-run');

    Route::post('/imports/apply', function (Request $request, BackupManager $backups) {
        $data = $request->validate(['path' => ['required', 'string'], 'mode' => ['required', 'in:merge,replace']]);

        return response()->json($backups->applyImport($data['path'], $data['mode'], $request->user()->id));
    })->name('imports.apply');

    Route::post('/backups/{backup}/restore', fn (int $backup, BackupManager $backups) => response()->json(
        $backups->restore($backup),
    ))->name('backups.restore');

    Route::post('/updates/stage', function (Request $request, UpdateManager $updates) {
        $data = $request->validate(['path' => ['required', 'string'], 'checksum' => ['required', 'string', 'size:64'], 'version' => ['required', 'string']]);

        return response()->json(['id' => $updates->stage($data['path'], $data['checksum'], $data['version'])]);
    })->name('updates.stage');
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', function () {
        return view('admin.shell', [
            'boot' => [
                'user' => auth()->user()->only(['id', 'name', 'email']),
                'stats' => [
                    'pages' => DB::table('pages')->count(),
                    'media' => DB::table('media_items')->count(),
                    'resumes' => DB::table('resume_profiles')->count(),
                    'scheduled' => DB::table('pages')->where('status', 'scheduled')->count(),
                ],
                'checklist' => [
                    'installed' => InstallState::isInstalled(),
                    'homepage' => DB::table('pages')->where('slug', 'home')->exists(),
                    'branding' => DB::table('settings')->where('key', 'design_tokens')->exists(),
                ],
            ],
        ]);
    })->name('dashboard');

    Route::view('/builder/{page?}', 'admin.shell')->name('builder');
    Route::get('/live/{page}', function (int $page) {
        $row = DB::table('pages')->where('id', $page)->whereNull('deleted_at')->firstOrFail();
        $document = json_decode((string) $row->builder_json, true) ?: [];
        $menuItems = collect(diamondcms_menu('header'))->map(fn (array $item) => [
            'label' => $item['label'],
            'url' => $item['url'],
        ])->values()->all();
        $footerItems = collect(diamondcms_menu('footer'))->map(fn (array $item) => [
            'label' => $item['label'],
            'url' => $item['url'],
        ])->values()->all();
        $chrome = DesignManager::chrome();

        $publicUrl = $row->status === 'published'
            ? url('/'.$row->slug)
            : url('/');

        $homepageSlug = diamondcms_setting('homepage_slug', 'home');
        if (is_string($homepageSlug) && $homepageSlug !== '' && $row->slug === $homepageSlug && $row->status === 'published') {
            $publicUrl = url('/');
        }

        return view('admin.live', [
            'page' => $row,
            'boot' => [
                'page' => $row,
                'siteName' => diamondcms_site_name(),
                'logoUrl' => diamondcms_logo_url(),
                'menuItems' => $menuItems,
                'footerItems' => $footerItems,
                'adminUrl' => route('admin.dashboard'),
                'publicUrl' => $publicUrl,
                'shell' => $document['meta']['shell'] ?? 'default',
                'chrome' => [
                    'headerStyle' => DesignManager::headerStyle(),
                    'footerStyle' => DesignManager::footerStyle(),
                    'buttonStyle' => DesignManager::buttonStyle(),
                    'footerShowLogo' => (bool) ($chrome['footerShowLogo'] ?? true),
                    'footerShowSiteName' => (bool) ($chrome['footerShowSiteName'] ?? true),
                    'footerTagline' => (string) ($chrome['footerTagline'] ?? ''),
                    'footerShowCredit' => (bool) ($chrome['footerShowCredit'] ?? true),
                    'footerCreditText' => (string) ($chrome['footerCreditText'] ?? 'Powered by DiamondCMS'),
                    'footerCreditUrl' => (string) ($chrome['footerCreditUrl'] ?? ''),
                    'footerSocials' => is_array($chrome['footerSocials'] ?? null) ? array_values($chrome['footerSocials']) : [],
                    'footerSocialStyle' => (string) ($chrome['footerSocialStyle'] ?? 'icons'),
                ],
                'visitorToggle' => DesignManager::visitorToggleEnabled(),
                'themeDefault' => DesignManager::resolvedDefaultTheme(),
                'themeLock' => DesignManager::themeLocked(),
            ],
        ]);
    })->name('live');
    Route::view('/media', 'admin.shell')->name('media');
    Route::view('/resumes', 'admin.shell')->name('resumes');
    Route::view('/settings', 'admin.shell')->name('settings');

    Route::prefix('api')->name('api.')->group(function (): void {
        Route::get('/dashboard', function (AnalyticsManager $analytics) {
            $payload = [
                'pages' => DB::table('pages')->whereNull('deleted_at')->count(),
                'published' => DB::table('pages')->whereNull('deleted_at')->where('status', 'published')->count(),
                'drafts' => DB::table('pages')->whereNull('deleted_at')->where('status', 'draft')->count(),
                'media' => DB::table('media_items')->whereNull('deleted_at')->count(),
                'recent_activity' => DB::table('activity_logs')->latest()->limit(10)->get(),
            ];
            try {
                $payload['analytics'] = $analytics->dashboard();
            } catch (Throwable) {
                $payload['analytics'] = [
                    'page_views_today' => 0,
                    'page_views_7d' => 0,
                    'unique_visitors_7d' => 0,
                    'resume_downloads' => 0,
                    'resume_downloads_7d' => 0,
                    'top_pages' => [],
                    'daily_views' => [],
                ];
            }

            return response()->json($payload);
        });

        Route::get('/settings', fn () => response()->json(DB::table('settings')->orderBy('group')->orderBy('key')->get()));
        Route::put('/settings/{key}', function (string $key, Request $request) {
            $data = $request->validate(['value' => ['nullable'], 'group' => ['nullable', 'string'], 'is_public' => ['boolean']]);
            DB::table('settings')->updateOrInsert(['key' => $key], [
                'value' => json_encode($data['value'] ?? null, JSON_THROW_ON_ERROR),
                'group' => $data['group'] ?? 'general',
                'is_public' => $request->boolean('is_public'),
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
            ActivityLogger::log('settings.updated', null, ['key' => $key], $request);

            return response()->json(['ok' => true]);
        });

        Route::get('/mail', fn (MailSettingsManager $mail) => response()->json($mail->publicConfig()));
        Route::put('/mail', function (Request $request, MailSettingsManager $mail) {
            $data = $request->validate([
                'host' => ['required', 'string', 'max:190'],
                'port' => ['nullable', 'integer'],
                'username' => ['nullable', 'string', 'max:190'],
                'password' => ['nullable', 'string'],
                'encryption' => ['nullable', 'in:tls,ssl,null'],
                'from_address' => ['required', 'email'],
                'from_name' => ['nullable', 'string', 'max:190'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            return response()->json(['id' => $mail->save($data, $request->user()->id), 'config' => $mail->publicConfig()]);
        });
        Route::post('/mail/test', function (Request $request, MailSettingsManager $mail) {
            $data = $request->validate(['recipient' => ['required', 'email']]);

            return response()->json(['delivery_log_id' => $mail->testSend($data['recipient'])]);
        });

        Route::get('/forms', fn (FormManager $forms) => response()->json($forms->list()));
        Route::post('/forms', function (Request $request, FormManager $forms) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:190'],
                'slug' => ['nullable', 'string', 'max:190'],
                'status' => ['nullable', 'in:draft,published,archived'],
                'schema' => ['required', 'array'],
                'notifications' => ['nullable', 'array'],
                'spam_config' => ['nullable', 'array'],
                'success_message' => ['nullable', 'string'],
                'redirect_url' => ['nullable', 'url'],
                'retention_days' => ['nullable', 'integer', 'min:1'],
            ]);
            $id = $forms->createForm($data, $request->user()->id);

            return response()->json($forms->find($id), 201);
        });
        Route::post('/forms/ensure-contact', function (Request $request, FormManager $forms) {
            return response()->json($forms->ensureContactForm($request->user()->id));
        });
        Route::get('/forms/{form}', fn (int $form, FormManager $forms) => response()->json($forms->find($form)));
        Route::put('/forms/{form}', function (int $form, Request $request, FormManager $forms) {
            $data = $request->validate([
                'name' => ['sometimes', 'string', 'max:190'],
                'slug' => ['sometimes', 'string', 'max:190'],
                'status' => ['sometimes', 'in:draft,published,archived'],
                'schema' => ['sometimes', 'array'],
                'notifications' => ['nullable', 'array'],
                'spam_config' => ['nullable', 'array'],
                'success_message' => ['nullable', 'string'],
                'redirect_url' => ['nullable', 'url'],
                'retention_days' => ['nullable', 'integer', 'min:1'],
            ]);

            return response()->json($forms->updateForm($form, $data, $request->user()->id));
        });
        Route::get('/forms/{form}/submissions', fn (int $form, FormManager $forms) => response()->json($forms->submissions($form)));

        Route::get('/pages', function (Request $request) {
            $query = DB::table('pages')->whereNull('deleted_at')->orderByDesc('updated_at');
            if ($search = $request->string('search')->toString()) {
                $query->where(fn ($builder) => $builder->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
            }

            return response()->json($query->paginate(20));
        });

        Route::post('/pages', function (Request $request) {
            $data = page_payload($request);
            $builder = BuilderDocument::validate($data['builder_json'] ?? BuilderDocument::empty($data['title']));
            $id = DB::table('pages')->insertGetId(array_merge(page_columns($data, $builder), [
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            create_revision($id, $request->user()->id);
            ActivityLogger::log('page.created', (object) ['id' => $id], ['title' => $data['title']], $request);
            apply_template_site_defaults($builder);

            return response()->json(DB::table('pages')->where('id', $id)->first(), 201);
        });

        Route::get('/pages/{page}', fn (int $page) => response()->json(DB::table('pages')->where('id', $page)->firstOrFail()));
        Route::put('/pages/{page}', function (int $page, Request $request) {
            $data = page_payload($request, false);
            $builder = BuilderDocument::validate($data['builder_json'] ?? json_decode((string) DB::table('pages')->where('id', $page)->value('builder_json'), true) ?: BuilderDocument::empty($data['title'] ?? 'Untitled page'));
            DB::table('pages')->where('id', $page)->update(array_merge(page_columns($data, $builder, false), [
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]));
            create_revision($page, $request->user()->id);
            ActivityLogger::log('page.updated', (object) ['id' => $page], [], $request);

            return response()->json(DB::table('pages')->where('id', $page)->first());
        });

        Route::post('/pages/{page}/duplicate', function (int $page, Request $request) {
            $source = (array) DB::table('pages')->where('id', $page)->firstOrFail();
            unset($source['id']);
            $source['title'] = $source['title'].' copy';
            $source['slug'] = Str::slug($source['slug'].' copy').'-'.Str::lower(Str::random(5));
            $source['status'] = 'draft';
            $source['created_by'] = $request->user()->id;
            $source['updated_by'] = $request->user()->id;
            $source['created_at'] = now();
            $source['updated_at'] = now();
            $source['deleted_at'] = null;
            $id = DB::table('pages')->insertGetId($source);
            create_revision($id, $request->user()->id);

            return response()->json(DB::table('pages')->where('id', $id)->first(), 201);
        });

        Route::post('/pages/{page}/rollback/{revision}', function (int $page, int $revision, Request $request) {
            $revisionRow = DB::table('page_revisions')->where('page_id', $page)->where('revision', $revision)->firstOrFail();
            $snapshot = json_decode((string) $revisionRow->snapshot, true);
            DB::table('pages')->where('id', $page)->update(array_merge(page_columns($snapshot, $snapshot['builder_json'] ?? BuilderDocument::empty($snapshot['title'] ?? 'Page'), false), [
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]));
            create_revision($page, $request->user()->id);
            ActivityLogger::log('page.rollback', (object) ['id' => $page], ['revision' => $revision], $request);

            return response()->json(DB::table('pages')->where('id', $page)->first());
        });

        Route::post('/pages/{page}/preview-token', function (int $page, Request $request) {
            $token = Str::random(48);
            DB::table('preview_tokens')->insert([
                'page_id' => $page,
                'token' => $token,
                'expires_at' => now()->addHours(24),
                'created_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['url' => route('preview', $token), 'token' => $token]);
        });

        Route::delete('/pages/{page}', function (int $page, Request $request) {
            DB::table('pages')->where('id', $page)->update(['status' => 'archived', 'deleted_at' => now(), 'updated_by' => $request->user()->id, 'updated_at' => now()]);
            ActivityLogger::log('page.archived', (object) ['id' => $page], [], $request);

            return response()->noContent();
        });

        Route::post('/pages/{page}/restore', function (int $page, Request $request) {
            DB::table('pages')->where('id', $page)->whereNotNull('deleted_at')->update([
                'deleted_at' => null,
                'status' => 'draft',
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);
            ActivityLogger::log('page.restored', (object) ['id' => $page], [], $request);

            return response()->json(DB::table('pages')->where('id', $page)->first());
        });

        Route::delete('/pages/{page}/force', function (int $page, Request $request) {
            DB::table('preview_tokens')->where('page_id', $page)->delete();
            DB::table('page_revisions')->where('page_id', $page)->delete();
            DB::table('pages')->where('id', $page)->delete();
            ActivityLogger::log('page.purged', (object) ['id' => $page], [], $request);

            return response()->noContent();
        });

        Route::get('/builder/registry', fn () => response()->json(['schema' => BuilderDocument::CURRENT_SCHEMA, 'blocks' => BuilderDocument::registry()]));
        Route::post('/builder/validate', function (Request $request) {
            return response()->json(['document' => BuilderDocument::validate($request->validate(['document' => ['required', 'array']])['document'])]);
        });

        Route::get('/menus', fn () => response()->json(MenuManager::allWithItems()));
        Route::post('/menus', function (Request $request) {
            $data = $request->validate(['name' => ['required', 'string'], 'location' => ['required', 'string'], 'items' => ['array']]);
            DB::table('menus')->updateOrInsert(
                ['location' => $data['location']],
                ['name' => $data['name'], 'updated_at' => now(), 'created_at' => now()],
            );
            $id = (int) DB::table('menus')->where('location', $data['location'])->value('id');
            DB::table('menu_items')->where('menu_id', $id)->delete();
            save_menu_items($id, $data['items'] ?? []);

            $menu = DB::table('menus')->where('id', $id)->first();
            $menu->items = MenuManager::treeForMenu($id);

            return response()->json($menu);
        });

        Route::get('/design', fn () => response()->json(DesignManager::tokens()));
        Route::put('/design', function (Request $request) {
            DesignManager::saveTokens($request->validate(['tokens' => ['required', 'array']])['tokens'], $request->user()->id);
            ActivityLogger::log('design.tokens_updated', null, [], $request);

            return response()->json(DesignManager::tokens());
        });
        Route::put('/custom-code', function (Request $request) {
            $data = $request->validate(['css' => ['nullable', 'string'], 'js_head' => ['nullable', 'string'], 'js_body' => ['nullable', 'string']]);
            foreach (['custom_css' => $data['css'] ?? '', 'custom_js_head' => $data['js_head'] ?? '', 'custom_js_body' => $data['js_body'] ?? ''] as $key => $value) {
                DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'group' => 'advanced', 'is_public' => true, 'updated_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('design_revisions')->insert(['type' => 'custom_code', 'payload' => json_encode($data), 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

            return response()->json(['ok' => true]);
        });

        Route::get('/templates', fn () => response()->json(DB::table('builder_templates')->orderBy('category')->orderBy('name')->get()));
        Route::post('/templates/seed', function () {
            seed_starter_templates();

            return response()->json(['count' => DB::table('builder_templates')->count()]);
        });

        Route::get('/media', function (Request $request) {
            $query = DB::table('media_items')->orderByDesc('created_at');
            if ($request->boolean('trashed')) {
                $query->whereNotNull('deleted_at');
            } else {
                $query->whereNull('deleted_at');
            }
            $paginator = $query->paginate(30);
            $paginator->getCollection()->transform(function (object $item): object {
                $item->url = '/storage/'.ltrim((string) $item->path, '/');

                return $item;
            });

            return response()->json($paginator);
        });
        Route::post('/media', function (Request $request, MediaManager $media) {
            $request->validate(['file' => ['required', 'file', 'max:51200'], 'alt_text' => ['nullable', 'string']]);
            $id = $media->store($request->file('file'), $request->user()->id, $request->only(['alt_text', 'caption', 'credit', 'folder_id']));

            return response()->json($media->payload($id), 201);
        });
        Route::post('/media/chunks', function (Request $request) {
            $data = $request->validate(['upload_id' => ['required', 'string'], 'chunk_index' => ['required', 'integer'], 'total_chunks' => ['required', 'integer'], 'chunk' => ['required', 'file']]);
            $path = $request->file('chunk')->store('chunks/'.$data['upload_id']);
            DB::table('media_upload_chunks')->updateOrInsert(['upload_id' => $data['upload_id'], 'chunk_index' => $data['chunk_index']], ['total_chunks' => $data['total_chunks'], 'path' => $path, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

            return response()->json(['received' => true]);
        });
        Route::post('/media/{media}/replace', function (int $media, Request $request, MediaManager $manager) {
            $request->validate(['file' => ['required', 'file', 'max:51200']]);
            $manager->replace($media, $request->file('file'), $request->user()->id);

            return response()->json($manager->payload($media));
        });
        Route::post('/media/{media}/restore', function (int $media, MediaManager $manager) {
            $manager->restore($media);

            return response()->json($manager->payload($media));
        });
        Route::delete('/media/{media}/force', function (int $media, Request $request, MediaManager $manager) {
            $manager->assertSafeToDelete($media, true);
            $manager->forceDelete($media);
            ActivityLogger::log('media.purged', (object) ['id' => $media], [], $request);

            return response()->noContent();
        });
        Route::delete('/media/{media}', function (int $media, Request $request, MediaManager $manager) {
            $manager->assertSafeToDelete($media, $request->boolean('force'));
            DB::table('media_items')->where('id', $media)->update(['deleted_at' => now(), 'updated_at' => now()]);

            return response()->noContent();
        });

        Route::get('/trash', function () {
            $pages = DB::table('pages')->whereNotNull('deleted_at')->orderByDesc('deleted_at')->limit(100)->get(['id', 'title', 'slug', 'status', 'deleted_at', 'updated_at']);
            $media = DB::table('media_items')->whereNotNull('deleted_at')->orderByDesc('deleted_at')->limit(100)->get();
            $media->transform(function (object $item): object {
                $item->url = '/storage/'.ltrim((string) $item->path, '/');

                return $item;
            });
            $projects = DB::table('projects')->whereNotNull('deleted_at')->orderByDesc('deleted_at')->limit(100)->get(['id', 'title', 'slug', 'status', 'deleted_at', 'updated_at']);

            return response()->json([
                'pages' => $pages,
                'media' => $media,
                'projects' => $projects,
            ]);
        });

        Route::get('/admins', fn () => response()->json(User::query()->where('is_admin', true)->orderBy('name')->get(['id', 'name', 'email', 'is_disabled', 'last_login_at', 'two_factor_confirmed_at'])));
        Route::post('/admins', function (Request $request) {
            $data = $request->validate(['name' => ['required', 'string'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:12']]);
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'is_admin' => true]);
            ActivityLogger::log('admin.created', $user, [], $request);

            return response()->json($user, 201);
        });
        Route::put('/admins/{user}', function (User $user, Request $request) {
            abort_if($user->id === $request->user()->id && $request->boolean('is_disabled'), 422, 'You cannot disable your own account.');
            $data = $request->validate(['name' => ['sometimes', 'string'], 'email' => ['sometimes', 'email'], 'is_disabled' => ['boolean']]);
            $user->fill($data)->save();
            ActivityLogger::log('admin.updated', $user, [], $request);

            return response()->json($user);
        });

        Route::get('/account', function (Request $request) {
            $user = $request->user();

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'two_factor_enabled' => filled($user->two_factor_confirmed_at),
                'two_factor_pending' => filled($user->two_factor_secret) && blank($user->two_factor_confirmed_at),
            ]);
        });
        Route::put('/account', function (Request $request) {
            $data = $request->validate([
                'name' => ['sometimes', 'string', 'max:120'],
                'email' => ['sometimes', 'email', 'max:190'],
                'current_password' => ['required_with:password', 'current_password'],
                'password' => ['nullable', 'confirmed', 'min:12'],
            ]);
            $user = $request->user();
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            if (isset($data['email'])) {
                $user->email = $data['email'];
            }
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();

            return response()->json(['ok' => true]);
        });

        Route::post('/two-factor/enable', function (Request $request) {
            $google2fa = app('pragmarx.google2fa');
            $secret = $google2fa->generateSecretKey();
            $recoveryCodes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all();
            $request->user()->forceFill([
                'two_factor_secret' => encrypt($secret),
                'two_factor_recovery_codes' => $recoveryCodes,
                'two_factor_confirmed_at' => null,
            ])->save();

            $otpauth = $google2fa->getQRCodeUrl(
                (string) (diamondcms_setting('site_name', config('app.name', 'DiamondCMS')) ?: 'DiamondCMS'),
                (string) $request->user()->email,
                $secret,
            );
            $qrSvg = (new Writer(new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd)))->writeString($otpauth);

            return response()->json([
                'secret' => $secret,
                'otpauth_url' => $otpauth,
                'qr_svg' => $qrSvg,
                'recovery_codes' => $recoveryCodes,
            ]);
        });
        Route::post('/two-factor/confirm', function (Request $request) {
            $request->validate(['code' => ['required', 'string']]);
            abort_unless(app('pragmarx.google2fa')->verifyKey(decrypt($request->user()->two_factor_secret), $request->string('code')->toString()), 422);
            $request->user()->forceFill(['two_factor_confirmed_at' => now()])->save();

            return response()->json([
                'ok' => true,
                'recovery_codes' => $request->user()->fresh()->two_factor_recovery_codes ?? [],
            ]);
        });
        Route::post('/two-factor/disable', function (Request $request) {
            $request->validate(['current_password' => ['required', 'current_password']]);
            $request->user()->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            return response()->json(['ok' => true]);
        });

        Route::get('/resumes', fn () => response()->json(DB::table('resume_profiles')->orderByDesc('updated_at')->get()));
        Route::post('/resumes', function (Request $request, ResumeManager $resumes) {
            $data = $request->validate([
                'name' => ['required', 'string'],
                'headline' => ['nullable', 'string'],
                'summary' => ['nullable', 'string'],
                'email' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'location' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'string', 'max:500'],
                'links' => ['nullable', 'array'],
                'links.*.label' => ['nullable', 'string', 'max:120'],
                'links.*.url' => ['nullable', 'string', 'max:500'],
            ]);
            $id = $resumes->createProfile($data, $request->user()->id);

            return response()->json(DB::table('resume_profiles')->where('id', $id)->first(), 201);
        });
        Route::put('/resumes/{profile}', function (int $profile, Request $request, ResumeManager $resumes) {
            abort_unless(DB::table('resume_profiles')->where('id', $profile)->exists(), 404);
            $data = $request->validate([
                'name' => ['sometimes', 'string'],
                'headline' => ['nullable', 'string'],
                'summary' => ['nullable', 'string'],
                'email' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'location' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'string', 'max:500'],
                'links' => ['nullable', 'array'],
                'links.*.label' => ['nullable', 'string', 'max:120'],
                'links.*.url' => ['nullable', 'string', 'max:500'],
            ]);

            return response()->json($resumes->updateProfile($profile, $data));
        });
        Route::delete('/resumes/{profile}', function (int $profile, ResumeManager $resumes) {
            abort_unless(DB::table('resume_profiles')->where('id', $profile)->exists(), 404);
            $resumes->deleteProfile($profile);

            return response()->noContent();
        });
        Route::get('/resumes/{profile}/sections', fn (int $profile) => response()->json(
            DB::table('resume_sections')->where('resume_profile_id', $profile)->orderBy('sort_order')->get()
        ));
        Route::put('/resumes/{profile}/sections', function (int $profile, Request $request) {
            $data = $request->validate(['sections' => ['required', 'array']]);
            DB::table('resume_sections')->where('resume_profile_id', $profile)->delete();
            foreach ($data['sections'] as $index => $section) {
                $meta = is_array($section['metadata'] ?? null) ? $section['metadata'] : [];
                if (array_key_exists('date', $section)) {
                    $meta['date'] = $section['date'];
                }
                DB::table('resume_sections')->insert([
                    'resume_profile_id' => $profile,
                    'type' => $section['type'] ?? 'experience',
                    'title' => $section['title'] ?? null,
                    'organization' => $section['organization'] ?? null,
                    'location' => $section['location'] ?? null,
                    'starts_on' => $section['starts_on'] ?? null,
                    'ends_on' => $section['ends_on'] ?? null,
                    'is_current' => (bool) ($section['is_current'] ?? false),
                    'bullets' => json_encode($section['bullets'] ?? [], JSON_THROW_ON_ERROR),
                    'metadata' => json_encode($meta, JSON_THROW_ON_ERROR),
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(DB::table('resume_sections')->where('resume_profile_id', $profile)->orderBy('sort_order')->get());
        });
        Route::get('/resumes/{profile}/variants', fn (int $profile) => response()->json(
            DB::table('resume_variants')->where('resume_profile_id', $profile)->orderByDesc('updated_at')->get()
        ));
        Route::post('/resumes/import', function (Request $request, ResumeManager $resumes, MediaManager $media) {
            $request->validate([
                'file' => ['required', 'file', 'max:51200'],
                'resume_profile_id' => ['nullable', 'integer'],
            ]);
            $extension = strtolower($request->file('file')->getClientOriginalExtension());
            abort_unless(in_array($extension, ['pdf', 'docx', 'txt'], true), 422, 'Upload a PDF, DOCX, or TXT résumé.');
            $mediaId = null;
            try {
                $mediaId = $media->store($request->file('file'), $request->user()->id);
            } catch (Throwable) {
                $mediaId = null;
            }
            $id = $resumes->createImport($request->file('file'), $request->integer('resume_profile_id') ?: null, $mediaId, $request->user()->id);

            return response()->json(DB::table('resume_imports')->where('id', $id)->first(), 201);
        });
        Route::get('/resumes/import/{import}', function (int $import) {
            $row = DB::table('resume_imports')->where('id', $import)->firstOrFail();
            $row->parsed_payload = json_decode((string) $row->parsed_payload, true) ?: [];

            return response()->json($row);
        });
        Route::put('/resumes/import/{import}', function (int $import, Request $request, ResumeManager $resumes) {
            $data = $request->validate([
                'name' => ['nullable', 'string'],
                'headline' => ['nullable', 'string'],
                'summary' => ['nullable', 'string'],
                'email' => ['nullable', 'string'],
                'phone' => ['nullable', 'string'],
                'location' => ['nullable', 'string'],
                'sections' => ['nullable', 'array'],
            ]);
            $row = $resumes->updateImportPayload($import, $data);
            $row->parsed_payload = json_decode((string) $row->parsed_payload, true) ?: [];

            return response()->json($row);
        });
        Route::post('/resumes/import/{import}/approve', fn (int $import, ResumeManager $resumes) => response()->json(['resume_profile_id' => $resumes->approveImport($import)]));
        Route::post('/resumes/{profile}/variants', function (int $profile, Request $request, ResumeManager $resumes) {
            $data = $request->validate([
                'name' => ['required', 'string'],
                'visibility' => ['nullable', 'in:public,private'],
                'summary_override' => ['nullable', 'string'],
                'download_pdf' => ['nullable', 'string'],
                'download_docx' => ['nullable', 'string'],
            ]);
            $id = $resumes->createVariant($profile, $data);

            return response()->json(DB::table('resume_variants')->where('id', $id)->first(), 201);
        });
        Route::put('/resume-variants/{variant}', function (int $variant, Request $request) {
            abort_unless(DB::table('resume_variants')->where('id', $variant)->exists(), 404);
            $data = $request->validate([
                'name' => ['sometimes', 'string', 'max:190'],
                'slug' => ['sometimes', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'visibility' => ['sometimes', 'in:public,private'],
                'summary_override' => ['nullable', 'string'],
                'download_pdf' => ['nullable', 'string'],
                'download_docx' => ['nullable', 'string'],
            ]);
            if (array_key_exists('slug', $data)) {
                $slug = Str::slug($data['slug']);
                abort_if($slug === '', 422, 'Slug must contain letters or numbers.');
                $taken = DB::table('resume_variants')
                    ->where('slug', $slug)
                    ->where('id', '!=', $variant)
                    ->exists();
                abort_if($taken, 422, 'That slug is already in use.');
                $data['slug'] = $slug;
            }
            $data['updated_at'] = now();
            DB::table('resume_variants')->where('id', $variant)->update($data);

            return response()->json(DB::table('resume_variants')->where('id', $variant)->first());
        });
        Route::delete('/resume-variants/{variant}', function (int $variant, ResumeManager $resumes) {
            abort_unless(DB::table('resume_variants')->where('id', $variant)->exists(), 404);
            $resumes->deleteVariant($variant);

            return response()->noContent();
        });
        Route::post('/resume-variants/{variant}/share', function (int $variant, Request $request) {
            $token = Str::random(48);
            DB::table('resume_share_links')->insert(['resume_variant_id' => $variant, 'token' => $token, 'expires_at' => $request->date('expires_at'), 'created_at' => now(), 'updated_at' => now()]);

            return response()->json(['url' => route('resume.share', $token)]);
        });
        Route::get('/resume-variants/{variant}/print', fn (int $variant, ResumeManager $resumes) => $resumes->pdfResponse($variant));

        Route::get('/portfolio/categories', fn () => response()->json(DB::table('portfolio_categories')->orderBy('sort_order')->get()));
        Route::post('/portfolio/categories', function (Request $request, PortfolioManager $portfolio) {
            $data = $request->validate(['name' => ['required', 'string'], 'slug' => ['nullable', 'string'], 'type' => ['nullable', 'string']]);
            $id = $portfolio->createCategory($data);

            return response()->json(DB::table('portfolio_categories')->where('id', $id)->first(), 201);
        });
        Route::get('/portfolio/projects', fn (PortfolioManager $portfolio) => response()->json($portfolio->adminProjects()));
        Route::post('/portfolio/projects', function (Request $request, PortfolioManager $portfolio) {
            $data = $request->validate([
                'category_id' => ['nullable', 'integer'],
                'title' => ['required', 'string', 'max:190'],
                'slug' => ['nullable', 'string'],
                'status' => ['nullable', 'in:draft,published,archived'],
                'visibility' => ['nullable', 'in:public,private'],
                'is_featured' => ['nullable', 'boolean'],
                'summary' => ['nullable', 'string'],
                'case_study' => ['nullable', 'string'],
                'url' => ['nullable', 'url'],
                'cover_image' => ['nullable', 'string', 'max:500'],
                'skills' => ['nullable', 'array'],
                'gallery' => ['nullable', 'array'],
                'gallery.*.src' => ['required_with:gallery', 'string', 'max:500'],
                'gallery.*.alt' => ['nullable', 'string', 'max:190'],
                'logos' => ['nullable', 'array'],
                'logos.*.label' => ['nullable', 'string', 'max:120'],
                'logos.*.icon' => ['nullable', 'string', 'max:80'],
                'logos.*.image' => ['nullable', 'string', 'max:500'],
                'logos.*.url' => ['nullable', 'string', 'max:500'],
            ]);
            $id = $portfolio->createProject($data, $request->user()->id);

            return response()->json(DB::table('projects')->where('id', $id)->first(), 201);
        });
        Route::put('/portfolio/projects/{project}', function (int $project, Request $request, PortfolioManager $portfolio) {
            $data = $request->validate([
                'category_id' => ['nullable', 'integer'],
                'title' => ['sometimes', 'string'],
                'summary' => ['nullable', 'string'],
                'case_study' => ['nullable', 'string'],
                'status' => ['sometimes', 'in:draft,published,archived'],
                'visibility' => ['sometimes', 'in:public,private'],
                'is_featured' => ['sometimes', 'boolean'],
                'url' => ['nullable', 'url'],
                'cover_image' => ['nullable', 'string', 'max:500'],
                'skills' => ['nullable', 'array'],
                'gallery' => ['nullable', 'array'],
                'gallery.*.src' => ['required_with:gallery', 'string', 'max:500'],
                'gallery.*.alt' => ['nullable', 'string', 'max:190'],
                'logos' => ['nullable', 'array'],
                'logos.*.label' => ['nullable', 'string', 'max:120'],
                'logos.*.icon' => ['nullable', 'string', 'max:80'],
                'logos.*.image' => ['nullable', 'string', 'max:500'],
                'logos.*.url' => ['nullable', 'string', 'max:500'],
            ]);

            $updated = $portfolio->updateProject($project, $data, $request->user()->id);

            return response()->json($updated);
        });
        Route::delete('/portfolio/projects/{project}', function (int $project, PortfolioManager $portfolio) {
            $portfolio->softDelete($project);

            return response()->noContent();
        });
        Route::post('/portfolio/projects/{project}/restore', function (int $project, PortfolioManager $portfolio) {
            $portfolio->restore($project);

            return response()->json(DB::table('projects')->where('id', $project)->first());
        });
        Route::delete('/portfolio/projects/{project}/force', function (int $project, PortfolioManager $portfolio) {
            $portfolio->forceDelete($project);

            return response()->noContent();
        });

        Route::get('/redirects', fn () => response()->json(DB::table('redirects')->orderByDesc('id')->get()));
        Route::post('/redirects', function (Request $request) {
            $data = $request->validate([
                'source' => ['required', 'string', 'starts_with:/'],
                'target' => ['required', 'string'],
                'status_code' => ['nullable', 'integer', 'in:301,302,307,308'],
                'is_active' => ['nullable', 'boolean'],
            ]);
            $id = DB::table('redirects')->insertGetId([
                'source' => $data['source'],
                'target' => $data['target'],
                'status_code' => $data['status_code'] ?? 301,
                'is_active' => $data['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            ActivityLogger::log('redirect.created', null, ['id' => $id], $request);

            return response()->json(DB::table('redirects')->where('id', $id)->first(), 201);
        });
        Route::delete('/redirects/{redirect}', function (int $redirect, Request $request) {
            DB::table('redirects')->where('id', $redirect)->delete();
            ActivityLogger::log('redirect.deleted', null, ['id' => $redirect], $request);

            return response()->noContent();
        });

        Route::post('/seo/audit-page/{page}', function (int $page, SeoManager $seo) {
            $row = DB::table('pages')->where('id', $page)->firstOrFail();
            $html = '<html><head><title>'.e((string) ($row->meta_title ?: $row->title)).'</title>';
            if ($row->meta_description) {
                $html .= '<meta name="description" content="'.e((string) $row->meta_description).'">';
            }
            $html .= '</head><body>'.(string) ($row->html_cache ?? '').'</body></html>';
            $id = $seo->auditHtml($html, 'page', $page);
            $audit = DB::table('seo_audits')->where('id', $id)->first();

            return response()->json([
                'id' => $id,
                'score' => $audit->score,
                'findings' => json_decode((string) $audit->findings, true) ?: [],
            ]);
        });

        Route::get('/activity', fn () => response()->json(DB::table('activity_logs')->latest()->limit(50)->get()));
        Route::get('/pages/{page}/revisions', fn (int $page) => response()->json(
            DB::table('page_revisions')->where('page_id', $page)->orderByDesc('revision')->limit(30)->get(['id', 'revision', 'created_by', 'created_at'])
        ));

        Route::get('/ai/providers', fn () => response()->json(
            DB::table('ai_providers')->orderBy('provider')->get(['id', 'provider', 'name', 'default_model', 'is_enabled', 'models', 'updated_at'])
        ));
        Route::post('/ai/providers', function (Request $request, AiManager $ai) {
            $data = $request->validate([
                'provider' => ['required', 'in:openai,anthropic,gemini'],
                'name' => ['nullable', 'string', 'max:120'],
                'api_key' => ['nullable', 'string'],
                'base_url' => ['nullable', 'url'],
                'default_model' => ['nullable', 'string'],
                'is_enabled' => ['nullable', 'boolean'],
            ]);

            return response()->json(['id' => $ai->saveProvider($data, $request->user()->id)], 201);
        });
        Route::get('/ai/generations', fn () => response()->json(
            DB::table('ai_generations')->orderByDesc('id')->limit(30)->get(['id', 'task', 'status', 'input_summary', 'page_id', 'created_at', 'approved_at'])
        ));
        Route::post('/ai/generate-draft-page', function (Request $request, AiManager $ai) {
            $data = $request->validate(['title' => ['required', 'string', 'max:190'], 'summary' => ['nullable', 'string']]);

            return response()->json(['generation_id' => $ai->generateDraftPage($data, $request->user()->id)], 201);
        });
        Route::post('/ai/generations/{generation}/approve', function (int $generation, Request $request, AiManager $ai) {
            return response()->json(['page_id' => $ai->approveGeneration($generation, $request->user()->id)]);
        });

        Route::get('/backups', fn () => response()->json(
            DB::table('backups')->orderByDesc('id')->limit(40)->get(['id', 'type', 'path', 'size', 'status', 'created_at'])
        ));
        Route::post('/backups', function (Request $request, BackupManager $backups) {
            return response()->json([
                'id' => $backups->backup($request->string('type', 'full')->toString(), $request->user()->id),
            ], 201);
        });
        Route::post('/exports', function (Request $request, BackupManager $backups) {
            $export = $backups->exportSite($request->user()->id);

            return response()->json([
                ...$export,
                'download_url' => url('/admin/api/exports/download/'.rawurlencode($export['filename'])),
                'note' => sprintf(
                    'Complete site package ready: %d files (%d media library). Secrets excluded.',
                    (int) ($export['media_files'] ?? 0),
                    (int) ($export['media_library_files'] ?? 0),
                ),
            ]);
        });
        Route::get('/exports/download/{filename}', function (string $filename, BackupManager $backups) {
            $filename = basename(urldecode($filename));
            abort_unless(preg_match('/^diamondcms-site-[\w\-]+\.zip$/', $filename) === 1, 404);
            $absolute = $backups->exportAbsolutePath('exports/'.$filename);

            return response()->download($absolute, $filename, [
                'Content-Type' => 'application/zip',
            ]);
        })->where('filename', '.*');
        Route::post('/imports/upload', function (Request $request, BackupManager $backups) {
            $data = $request->validate([
                'package' => ['required', 'file', 'max:512000'],
                'mode' => ['nullable', 'in:merge,replace'],
            ]);
            $file = $request->file('package');
            abort_unless(
                $file && strtolower($file->getClientOriginalExtension()) === 'zip',
                422,
                'Upload a .zip site package.',
            );

            return response()->json(
                $backups->importUploadedPackage(
                    $file,
                    $data['mode'] ?? 'replace',
                    $request->user()->id,
                )
            );
        });
        Route::post('/backups/{backup}/restore', fn (int $backup, BackupManager $backups) => response()->json(
            $backups->restore($backup),
        ));
        Route::get('/updates', fn () => response()->json(
            DB::table('update_logs')->orderByDesc('id')->limit(20)->get(['id', 'version', 'status', 'checksum', 'created_at'])
        ));
        Route::post('/updates/stage', function (Request $request, UpdateManager $updates) {
            $data = $request->validate([
                'source_path' => ['required', 'string'],
                'checksum' => ['required', 'string'],
                'version' => ['required', 'string'],
            ]);

            return response()->json(['id' => $updates->stage($data['source_path'], $data['checksum'], $data['version'])], 201);
        });
    });
});

if (! function_exists('page_payload')) {
    function page_payload(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:190'],
            'slug' => [$creating ? 'required' : 'sometimes', 'alpha_dash:ascii', 'max:190', 'unique:pages,slug'.($creating ? '' : ','.$request->route('page'))],
            'status' => ['sometimes', 'in:draft,scheduled,published,archived'],
            'locale' => ['sometimes', 'string', 'max:12'],
            'builder_json' => ['sometimes', 'array'],
            'excerpt' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
        ]);
    }
}

if (! function_exists('page_columns')) {
    function page_columns(array $data, array $builder, bool $creating = true): array
    {
        /** @var array<string, mixed> $columns */
        $columns = [];
        foreach (['title', 'slug', 'status', 'locale', 'excerpt', 'meta_title', 'meta_description', 'scheduled_for'] as $key) {
            if (array_key_exists($key, $data)) {
                $columns[$key] = $data[$key];
            }
        }
        $columns['builder_json'] = json_encode($builder, JSON_THROW_ON_ERROR);
        $columns['html_cache'] = (string) BuilderDocument::render($builder);
        if (array_key_exists('password', $data)) {
            $columns['password_hash'] = $data['password'] ? Hash::make($data['password']) : null;
        }
        if (($columns['status'] ?? null) === 'published' && empty($columns['published_at'] ?? null)) {
            $columns['published_at'] = now();
        }

        return $columns;
    }
}

if (! function_exists('create_revision')) {
    function create_revision(int $pageId, ?int $userId): void
    {
        $page = (array) DB::table('pages')->where('id', $pageId)->first();
        $next = ((int) DB::table('page_revisions')->where('page_id', $pageId)->max('revision')) + 1;
        DB::table('page_revisions')->insert([
            'page_id' => $pageId,
            'revision' => $next,
            'snapshot' => json_encode($page, JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

if (! function_exists('save_menu_items')) {
    function save_menu_items(int $menuId, array $items, ?int $parentId = null): void
    {
        foreach ($items as $index => $item) {
            $id = DB::table('menu_items')->insertGetId([
                'menu_id' => $menuId,
                'parent_id' => $parentId,
                'page_id' => $item['page_id'] ?? null,
                'label' => $item['label'] ?? 'Menu item',
                'url' => $item['url'] ?? null,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            save_menu_items($menuId, $item['children'] ?? [], $id);
        }
    }
}

if (! function_exists('apply_template_site_defaults')) {
    /**
     * @param  array<string, mixed>  $builder
     */
    function apply_template_site_defaults(array $builder): void
    {
        $shell = $builder['meta']['shell'] ?? 'default';
        $previewTheme = $builder['meta']['preview_theme'] ?? '';

        if ($shell === 'sidebar-dark') {
            DesignManager::saveTokens([
                'mode' => 'dark',
                'dark' => [
                    'background' => '#0f0f0f',
                    'foreground' => '#f4f4f4',
                    'muted' => '#a8b0ac',
                    'primary' => '#2dd4bf',
                    'primaryContrast' => '#042f2e',
                    'surface' => '#1a1a1a',
                    'accent' => '#34d399',
                ],
                'atmosphere' => ['preset' => 'soft-teal', 'custom' => ''],
                'motion' => ['enabled' => true],
            ]);
        } elseif ($previewTheme === 'dark-navy') {
            DesignManager::saveTokens([
                'mode' => 'dark',
                'dark' => [
                    'background' => '#050a15',
                    'foreground' => '#eef5ff',
                    'muted' => '#9eb0c8',
                    'primary' => '#00a3ff',
                    'primaryContrast' => '#041018',
                    'surface' => '#0b1524',
                    'accent' => '#4cc9f0',
                ],
                'atmosphere' => ['preset' => 'navy', 'custom' => ''],
                'motion' => ['enabled' => true],
            ]);
        } elseif ($previewTheme === 'dark-neon') {
            DesignManager::saveTokens([
                'mode' => 'dark',
                'dark' => [
                    'background' => '#0a0a0a',
                    'foreground' => '#f4f4f4',
                    'muted' => '#a3a3a3',
                    'primary' => '#b8ff3c',
                    'primaryContrast' => '#041004',
                    'surface' => '#141414',
                    'accent' => '#84cc16',
                ],
                'atmosphere' => ['preset' => 'midnight', 'custom' => ''],
                'motion' => ['enabled' => true],
            ]);
        } elseif ($previewTheme === 'split-teal') {
            DesignManager::saveTokens([
                'mode' => 'dark',
                'dark' => [
                    'background' => '#062828',
                    'foreground' => '#f4fff8',
                    'muted' => '#a7c4bc',
                    'primary' => '#2dd4bf',
                    'primaryContrast' => '#042f2e',
                    'surface' => '#0d3333',
                    'accent' => '#a3e635',
                ],
                'atmosphere' => ['preset' => 'split-teal', 'custom' => ''],
                'motion' => ['enabled' => true],
            ]);
        }

        if ($shell !== 'sidebar-dark') {
            return;
        }

        $menuId = DB::table('menus')->where('location', 'header')->value('id');
        if (! $menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'name' => 'Header',
                'location' => 'header',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('menu_items')->where('menu_id', $menuId)->exists()) {
            return;
        }

        foreach ([
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About', 'url' => '/#about'],
            ['label' => 'Resume', 'url' => '/resume'],
            ['label' => 'Portfolio', 'url' => '/projects'],
            ['label' => 'Contact', 'url' => '/contact'],
        ] as $index => $item) {
            DB::table('menu_items')->insert([
                'menu_id' => $menuId,
                'parent_id' => null,
                'page_id' => null,
                'label' => $item['label'],
                'url' => $item['url'],
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

if (! function_exists('seed_starter_templates')) {
    function seed_starter_templates(): void
    {
        foreach (StarterTemplates::definitions() as $definition) {
            $document = $definition['document'];
            $document['meta'] = array_merge($document['meta'] ?? [], [
                'preview_theme' => $definition['preview_theme'] ?? ($document['meta']['preview_theme'] ?? 'light'),
                'blurb' => $definition['blurb'] ?? '',
            ]);

            DB::table('builder_templates')->updateOrInsert(['slug' => Str::slug($definition['name'])], [
                'name' => $definition['name'],
                'category' => $definition['category'],
                'builder_json' => json_encode($document, JSON_THROW_ON_ERROR),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
