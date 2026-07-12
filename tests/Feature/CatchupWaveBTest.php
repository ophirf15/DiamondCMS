<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Design\Support\DesignManager;
use App\Domains\Resume\Support\ResumeManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatchupWaveBTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_two_factor_enable_returns_qr_and_recovery_codes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->postJson('/admin/api/two-factor/enable');

        $response->assertOk()
            ->assertJsonStructure(['secret', 'otpauth_url', 'qr_svg', 'recovery_codes']);
        $this->assertCount(8, $response->json('recovery_codes'));
        $this->assertStringContainsString('<svg', $response->json('qr_svg'));
        $this->assertStringStartsWith('otpauth://totp/', $response->json('otpauth_url'));
    }

    public function test_two_factor_challenge_accepts_recovery_code(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $codes = ['RECOVERY01', 'RECOVERY02'];
        $admin->forceFill([
            'two_factor_secret' => encrypt(app('pragmarx.google2fa')->generateSecretKey()),
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->withSession(['2fa:user:id' => $admin->id])
            ->post('/two-factor/challenge', ['code' => 'RECOVERY01'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $admin->refresh();
        $this->assertSame(['RECOVERY02'], $admin->two_factor_recovery_codes);
    }

    public function test_resume_variant_is_publicly_reachable_and_download_block_links(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $resumes = app(ResumeManager::class);
        $profileId = $resumes->createProfile([
            'name' => 'Ophir',
            'headline' => 'Builder',
            'summary' => 'Ships CMS features.',
        ], $admin->id);
        DB::table('resume_sections')->insert([
            'resume_profile_id' => $profileId,
            'type' => 'experience',
            'title' => 'Lead',
            'organization' => 'Diamond',
            'bullets' => json_encode(['Built Wave B']),
            'metadata' => json_encode([]),
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variant = $this->actingAs($admin)->postJson("/admin/api/resumes/{$profileId}/variants", [
            'name' => 'Public',
            'visibility' => 'public',
        ])->assertCreated()->json();

        $this->get('/resume/'.$variant['slug'])->assertOk()->assertSee('Ophir')->assertSee('Built Wave B');
        $this->get('/resume/'.$variant['slug'].'/print')->assertOk()->assertHeader('X-DiamondCMS-PDF-Mode', 'browser-print');

        DB::table('resume_variants')->where('id', $variant['id'])->update([
            'download_pdf' => '/storage/media/sample.pdf',
            'download_docx' => '/storage/media/sample.docx',
        ]);

        $html = BuilderDocument::render([
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'blocks' => [['type' => 'resume-download', 'props' => ['text' => 'Get résumé']]],
        ])->toHtml();
        $this->assertStringContainsString('/resume/'.$variant['slug'].'/download/pdf', $html);
        $this->assertStringContainsString('/resume/'.$variant['slug'].'/download/docx', $html);
        $this->assertStringContainsString('Get résumé', $html);
    }

    public function test_portfolio_crud_and_public_listing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = $this->postJson('/admin/api/portfolio/categories', ['name' => 'Web'])->assertCreated()->json();
        $project = $this->postJson('/admin/api/portfolio/projects', [
            'title' => 'Case Study Alpha',
            'summary' => 'A public build',
            'case_study' => 'Detailed write-up',
            'status' => 'published',
            'visibility' => 'public',
            'is_featured' => true,
            'category_id' => $category['id'],
            'skills' => ['Vue', 'Laravel'],
            'cover_image' => '/brand/logo-primary-gold.svg',
            'gallery' => [
                ['src' => '/brand/logo-gold-on-charcoal.svg', 'alt' => 'Dark mark'],
                ['src' => '/brand/diamond-icon-gold.svg', 'alt' => 'Icon'],
            ],
            'logos' => [
                ['label' => 'Vue', 'icon' => 'vuedotjs', 'image' => '', 'url' => 'https://vuejs.org'],
                ['label' => 'Brand', 'icon' => '', 'image' => '/brand/favicon.svg', 'url' => ''],
            ],
        ])->assertCreated()->json();

        $this->putJson('/admin/api/portfolio/projects/'.$project['id'], [
            'summary' => 'Updated summary',
            'case_study' => 'Updated case',
        ])->assertOk();

        $this->get('/projects')->assertOk()->assertSee('Case Study Alpha');
        $show = $this->get('/projects/'.$project['slug'])->assertOk();
        $show->assertSee('Updated case');
        $show->assertSee('dc-project-gallery', false);
        $show->assertSee('dc-gallery-carousel', false);
        $show->assertSee('dc-media-frame', false);
        $show->assertSee('dc-project-logos', false);
        $show->assertSee('dc-project-cta', false);
        $show->assertSee('dc-project-page--classic', false);
        $show->assertSee('Dark mark');
        $show->assertSee('Vue');
        $show->assertSee('cdn.simpleicons.org/vuedotjs', false);

        DesignManager::saveTokens([
            'portfolio' => [
                'pageLayout' => 'magazine',
                'logoStyle' => 'icons',
                'logoSize' => 'lg',
                'ctaSize' => 'sm',
                'skillsStyle' => 'chips',
            ],
        ]);

        $magazine = $this->get('/projects/'.$project['slug'])->assertOk();
        $magazine->assertSee('dc-project-page--magazine', false);
        $magazine->assertSee('dc-project-logos--icons', false);
        $magazine->assertSee('dc-project-logosize--lg', false);
        $magazine->assertSee('dc-project-cta--sm', false);
        $magazine->assertSee('dc-project-skill-chip', false);

        $this->deleteJson('/admin/api/portfolio/projects/'.$project['id'])->assertNoContent();
        $this->get('/projects/'.$project['slug'])->assertNotFound();
    }

    public function test_seo_redirect_audit_and_revision_restore(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageId = DB::table('pages')->insertGetId([
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'meta_title' => 'About DiamondCMS long enough',
            'meta_description' => 'Meta description for audits.',
            'builder_json' => json_encode(BuilderDocument::empty('About')),
            'html_cache' => '<p>About</p><img src="/x.jpg">',
            'published_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->postJson('/admin/api/redirects', [
            'source' => '/legacy-about',
            'target' => '/about',
            'status_code' => 301,
        ])->assertCreated();

        $this->get('/legacy-about')->assertRedirect('/about');

        $audit = $this->actingAs($admin)->postJson('/admin/api/seo/audit-page/'.$pageId)->assertOk()->json();
        $this->assertArrayHasKey('score', $audit);
        $this->assertLessThan(100, $audit['score']);

        DB::table('page_revisions')->insert([
            'page_id' => $pageId,
            'revision' => 1,
            'snapshot' => json_encode([
                'title' => 'About restored',
                'slug' => 'about',
                'status' => 'published',
                'builder_json' => BuilderDocument::empty('About restored'),
                'html_cache' => '<p>Restored</p>',
            ]),
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->postJson("/admin/api/pages/{$pageId}/rollback/1")->assertOk();
        $this->assertSame('About restored', DB::table('pages')->where('id', $pageId)->value('title'));

        $redirectId = (int) DB::table('redirects')->where('source', '/legacy-about')->value('id');
        $this->actingAs($admin)->deleteJson('/admin/api/redirects/'.$redirectId)->assertNoContent();
    }

    public function test_wave_c_ai_and_backup_api_endpoints(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->postJson('/admin/api/ai/providers', [
            'provider' => 'openai',
            'name' => 'Default',
            'api_key' => 'sk-test-not-real',
        ])->assertCreated();

        $generation = $this->postJson('/admin/api/ai/generate-draft-page', [
            'title' => 'AI Landing',
            'summary' => 'Needs approval',
        ])->assertCreated()->json();

        $this->getJson('/admin/api/ai/generations')->assertOk()->assertJsonFragment(['status' => 'pending_approval']);

        $approved = $this->postJson('/admin/api/ai/generations/'.$generation['generation_id'].'/approve')
            ->assertOk()
            ->json();
        $this->assertDatabaseHas('pages', ['id' => $approved['page_id'], 'status' => 'draft']);

        $backup = $this->postJson('/admin/api/backups', ['type' => 'full'])->assertCreated()->json();
        $this->getJson('/admin/api/backups')->assertOk()->assertJsonFragment(['id' => $backup['id']]);

        Storage::disk('public')->put('media/api-export.txt', 'via-api');
        $export = $this->postJson('/admin/api/exports')->assertOk()->json();
        $this->assertArrayHasKey('download_url', $export);
        $this->assertArrayHasKey('media_files', $export);
        $this->get($export['download_url'])->assertOk();
    }
}
