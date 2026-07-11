<?php

use App\Domains\AI\Support\AiManager;
use App\Domains\Activity\Support\ActivityLogger;
use App\Domains\Backups\Support\BackupManager;
use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Design\Support\DesignManager;
use App\Domains\Forms\Support\FormManager;
use App\Domains\Mail\Support\MailSettingsManager;
use App\Domains\Media\Support\MediaManager;
use App\Domains\Portfolio\Support\PortfolioManager;
use App\Domains\Resume\Support\ResumeManager;
use App\Domains\SEO\Support\SeoManager;
use App\Domains\Updates\Support\UpdateManager;
use App\Models\User;
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
            'builder_json' => ['nullable', 'array'],
        ]);

        return response()->json(['id' => $portfolio->createProject($data, $request->user()->id)]);
    })->name('portfolio.projects.store');

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

        return response()->json(['id' => $forms->createForm($data, $request->user()->id)]);
    })->name('forms.store');

    Route::get('/forms/{form}/submissions.csv', function (int $form, FormManager $forms) {
        return response($forms->csv($form), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="form-'.$form.'-submissions.csv"',
        ]);
    })->name('forms.submissions.csv');

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

    Route::post('/exports', fn (Request $request, BackupManager $backups) => response()->json([
        'path' => $backups->exportSite($request->user()->id),
    ]))->name('exports.store');

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
                    'installed' => \App\Domains\Installer\Support\InstallState::isInstalled(),
                    'homepage' => DB::table('pages')->where('slug', 'home')->exists(),
                    'branding' => DB::table('settings')->where('key', 'design_tokens')->exists(),
                ],
            ],
        ]);
    })->name('dashboard');

    Route::view('/builder/{page?}', 'admin.shell')->name('builder');
    Route::view('/media', 'admin.shell')->name('media');
    Route::view('/resumes', 'admin.shell')->name('resumes');
    Route::view('/settings', 'admin.shell')->name('settings');

    Route::prefix('api')->name('api.')->group(function (): void {
        Route::get('/dashboard', fn () => response()->json([
            'pages' => DB::table('pages')->count(),
            'published' => DB::table('pages')->where('status', 'published')->count(),
            'drafts' => DB::table('pages')->where('status', 'draft')->count(),
            'media' => DB::table('media_items')->count(),
            'recent_activity' => DB::table('activity_logs')->latest()->limit(10)->get(),
        ]));

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

        Route::get('/builder/registry', fn () => response()->json(['schema' => BuilderDocument::CURRENT_SCHEMA, 'blocks' => BuilderDocument::registry()]));
        Route::post('/builder/validate', function (Request $request) {
            return response()->json(['document' => BuilderDocument::validate($request->validate(['document' => ['required', 'array']])['document'])]);
        });

        Route::get('/menus', fn () => response()->json(DB::table('menus')
            ->leftJoin('menu_items', 'menus.id', '=', 'menu_items.menu_id')
            ->select('menus.*', DB::raw('count(menu_items.id) as items_count'))
            ->groupBy('menus.id', 'menus.name', 'menus.location', 'menus.created_at', 'menus.updated_at')
            ->get()));
        Route::post('/menus', function (Request $request) {
            $data = $request->validate(['name' => ['required', 'string'], 'location' => ['required', 'string'], 'items' => ['array']]);
            $menuId = DB::table('menus')->updateOrInsert(['location' => $data['location']], ['name' => $data['name'], 'created_at' => now(), 'updated_at' => now()]);
            $id = (int) DB::table('menus')->where('location', $data['location'])->value('id');
            DB::table('menu_items')->where('menu_id', $id)->delete();
            save_menu_items($id, $data['items'] ?? []);

            return response()->json(DB::table('menus')->where('id', $id)->first());
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

        Route::get('/media', fn (Request $request) => response()->json(DB::table('media_items')->whereNull('deleted_at')->orderByDesc('created_at')->paginate(30)));
        Route::post('/media', function (Request $request, MediaManager $media) {
            $request->validate(['file' => ['required', 'file', 'max:51200'], 'alt_text' => ['nullable', 'string']]);
            $id = $media->store($request->file('file'), $request->user()->id, $request->only(['alt_text', 'caption', 'credit', 'folder_id']));

            return response()->json(DB::table('media_items')->where('id', $id)->first(), 201);
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

            return response()->json(DB::table('media_items')->where('id', $media)->first());
        });
        Route::delete('/media/{media}', function (int $media, Request $request, MediaManager $manager) {
            $manager->assertSafeToDelete($media, $request->boolean('force'));
            DB::table('media_items')->where('id', $media)->update(['deleted_at' => now(), 'updated_at' => now()]);

            return response()->noContent();
        });

        Route::get('/admins', fn () => response()->json(User::query()->where('is_admin', true)->orderBy('name')->get(['id', 'name', 'email', 'is_disabled', 'last_login_at'])));
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

        Route::post('/two-factor/enable', function (Request $request) {
            $secret = app('pragmarx.google2fa')->generateSecretKey();
            $request->user()->forceFill([
                'two_factor_secret' => encrypt($secret),
                'two_factor_recovery_codes' => collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all(),
            ])->save();

            return response()->json(['secret' => $secret]);
        });
        Route::post('/two-factor/confirm', function (Request $request) {
            $request->validate(['code' => ['required', 'string']]);
            abort_unless(app('pragmarx.google2fa')->verifyKey(decrypt($request->user()->two_factor_secret), $request->string('code')->toString()), 422);
            $request->user()->forceFill(['two_factor_confirmed_at' => now()])->save();

            return response()->json(['ok' => true]);
        });

        Route::get('/resumes', fn () => response()->json(DB::table('resume_profiles')->orderByDesc('updated_at')->get()));
        Route::post('/resumes', function (Request $request, ResumeManager $resumes) {
            $data = $request->validate(['name' => ['required', 'string'], 'headline' => ['nullable', 'string'], 'summary' => ['nullable', 'string']]);
            $id = $resumes->createProfile($data, $request->user()->id);

            return response()->json(DB::table('resume_profiles')->where('id', $id)->first(), 201);
        });
        Route::post('/resumes/import', function (Request $request, ResumeManager $resumes, MediaManager $media) {
            $request->validate(['file' => ['required', 'file', 'max:51200'], 'resume_profile_id' => ['nullable', 'integer']]);
            $mediaId = $media->store($request->file('file'), $request->user()->id);
            $id = $resumes->createImport($request->file('file'), $request->integer('resume_profile_id') ?: null, $mediaId, $request->user()->id);

            return response()->json(DB::table('resume_imports')->where('id', $id)->first(), 201);
        });
        Route::post('/resumes/import/{import}/approve', fn (int $import, ResumeManager $resumes) => response()->json(['resume_profile_id' => $resumes->approveImport($import)]));
        Route::post('/resumes/{profile}/variants', function (int $profile, Request $request, ResumeManager $resumes) {
            $data = $request->validate(['name' => ['required', 'string'], 'visibility' => ['nullable', 'in:public,private'], 'summary_override' => ['nullable', 'string']]);
            $id = $resumes->createVariant($profile, $data);

            return response()->json(DB::table('resume_variants')->where('id', $id)->first(), 201);
        });
        Route::post('/resume-variants/{variant}/share', function (int $variant, Request $request) {
            $token = Str::random(48);
            DB::table('resume_share_links')->insert(['resume_variant_id' => $variant, 'token' => $token, 'expires_at' => $request->date('expires_at'), 'created_at' => now(), 'updated_at' => now()]);

            return response()->json(['url' => route('resume.share', $token)]);
        });
        Route::get('/resume-variants/{variant}/print', fn (int $variant, ResumeManager $resumes) => $resumes->pdfResponse($variant));
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
        if (($columns['status'] ?? null) === 'published' && empty($columns['published_at'])) {
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

if (! function_exists('seed_starter_templates')) {
    function seed_starter_templates(): void
    {
        $names = [
            'Dark technical resume',
            'Minimal professional resume',
            'Creative portfolio',
            'Property-management professional',
            'Developer technical portfolio',
            'Photography portfolio',
            'Personal biography',
            'Split-screen resume',
            'Editorial case-study portfolio',
            'Modern one-page personal site',
        ];

        foreach ($names as $name) {
            DB::table('builder_templates')->updateOrInsert(['slug' => Str::slug($name)], [
                'name' => $name,
                'category' => str_contains(Str::lower($name), 'resume') ? 'resume' : 'page',
                'builder_json' => json_encode(BuilderDocument::empty($name), JSON_THROW_ON_ERROR),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
