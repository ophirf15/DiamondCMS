<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Builder\Support\StarterTemplates;
use App\Domains\Design\Support\DesignManager;
use App\Domains\Design\Support\MenuManager;
use App\Domains\Forms\Support\FormManager;
use App\Domains\Mail\Support\MailSettingsManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CatchupWaveATest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_site_name_appears_on_public_layout(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'site_name'], [
            'value' => json_encode('Wave A Studio'),
            'group' => 'general',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/')->assertOk()->assertSee('Wave A Studio');
    }

    public function test_atmosphere_css_uses_theme_tokens(): void
    {
        DesignManager::saveTokens(['atmosphere' => ['preset' => 'navy', 'custom' => '']]);
        $css = DesignManager::atmosphereCss();
        $this->assertStringContainsString('var(--dc-bg)', $css);
        $this->assertStringContainsString('var(--dc-primary)', $css);
        $this->assertStringNotContainsString('#050a15', $css);
    }

    public function test_design_tokens_respect_forced_dark_mode(): void
    {
        DesignManager::saveTokens(['mode' => 'dark', 'dark' => ['background' => '#111111']]);
        $css = DesignManager::cssVariables()->toHtml();

        $this->assertStringContainsString('--dc-bg:#111111', $css);
        $this->assertStringNotContainsString('prefers-color-scheme', $css);
    }

    public function test_resume_and_uikit_attrs_are_emitted_on_public_layout(): void
    {
        DesignManager::saveTokens([
            'uiKit' => ['density' => 'compact', 'controlStyle' => 'bordered'],
            'resume' => [
                'density' => 'compact',
                'sectionRhythm' => 'tight',
                'experienceStyle' => 'timeline',
            ],
        ]);

        $html = $this->get('/projects')->assertOk()->getContent();
        $this->assertStringContainsString('data-dc-density="compact"', $html);
        $this->assertStringContainsString('data-dc-control="bordered"', $html);
        $this->assertStringContainsString('data-dc-resume-density="compact"', $html);
        $this->assertStringContainsString('data-dc-resume-rhythm="tight"', $html);
        $this->assertStringContainsString('data-dc-resume-experience="timeline"', $html);
    }

    public function test_starter_templates_are_differentiated(): void
    {
        $defs = StarterTemplates::definitions();
        $this->assertCount(13, $defs);

        $hashes = collect($defs)->map(fn (array $def) => md5(json_encode($def['document'])))->unique();
        $this->assertCount(13, $hashes);
    }

    public function test_menus_render_on_public_header_without_hardcoded_admin_for_guests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageId = DB::table('pages')->insertGetId([
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'builder_json' => json_encode(BuilderDocument::empty('About')),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('menus')->insert([
            'name' => 'Header',
            'location' => 'header',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $menuId = (int) DB::table('menus')->where('location', 'header')->value('id');
        DB::table('menu_items')->insert([
            'menu_id' => $menuId,
            'page_id' => $pageId,
            'label' => 'About us',
            'url' => null,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotEmpty(MenuManager::publicItems('header'));
        $this->get('/')->assertOk()->assertSee('About us')->assertDontSee('>Admin<', false);
    }

    public function test_form_embed_ssr_and_notification_mail(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'owner@example.com']);

        app(MailSettingsManager::class)->save([
            'host' => 'smtp.example.com',
            'port' => 587,
            'from_address' => 'site@example.com',
            'from_name' => 'Site',
            'encryption' => 'tls',
            'is_active' => true,
        ], $admin->id);

        $forms = app(FormManager::class);
        $formId = $forms->createForm([
            'name' => 'Contact',
            'slug' => 'contact',
            'status' => 'published',
            'schema' => ['fields' => [
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
            ]],
            'notifications' => ['recipients' => ['owner@example.com']],
        ], $admin->id);

        $html = BuilderDocument::render([
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'blocks' => [['type' => 'form', 'props' => ['slug' => 'contact']]],
        ])->toHtml();

        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('forms/contact', $html);

        $this->post('/forms/contact', [
            'email' => 'visitor@example.com',
            'message' => 'Hello from Wave A',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('form_submissions', ['form_id' => $formId, 'status' => 'new']);
        $this->assertDatabaseHas('email_delivery_logs', ['template_key' => 'form_notification', 'status' => 'sent']);
    }

    public function test_admin_api_exposes_wave_a_endpoints(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/admin/api/mail')
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/admin/api/forms')
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($admin)
            ->postJson('/admin/api/templates/seed')
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/admin/api/templates')
            ->assertOk()
            ->assertJsonCount(13);
    }
}
