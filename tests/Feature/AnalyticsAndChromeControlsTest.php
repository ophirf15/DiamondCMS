<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Analytics\Support\AnalyticsManager;
use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Design\Support\DesignManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AnalyticsAndChromeControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_views_and_resume_downloads_appear_on_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageId = DB::table('pages')->insertGetId([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'published',
            'locale' => 'en',
            'builder_json' => json_encode(BuilderDocument::empty('Home'), JSON_THROW_ON_ERROR),
            'html_cache' => '',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/home')->assertOk();
        $this->get('/home')->assertOk();

        $variantId = DB::table('resume_variants')->insertGetId([
            'resume_profile_id' => DB::table('resume_profiles')->insertGetId([
                'name' => 'Default',
                'headline' => 'Engineer',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'slug' => 'main',
            'name' => 'Main',
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AnalyticsManager::class)->trackResumeDownload($variantId);

        $response = $this->actingAs($admin)->getJson('/admin/api/dashboard')->assertOk();
        $response->assertJsonPath('analytics.page_views_7d', 2);
        $response->assertJsonPath('analytics.resume_downloads', 1);
        $this->assertSame('Home', $response->json('analytics.top_pages.0.title'));
        $this->assertSame($pageId, $response->json('analytics.top_pages.0.page_id'));
    }

    public function test_admin_can_unpublish_and_delete_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageId = DB::table('pages')->insertGetId([
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'locale' => 'en',
            'builder_json' => json_encode(BuilderDocument::empty('About'), JSON_THROW_ON_ERROR),
            'html_cache' => '',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/about')->assertOk();

        $this->actingAs($admin)->putJson("/admin/api/pages/{$pageId}", ['status' => 'draft'])->assertOk()
            ->assertJsonPath('status', 'draft');
        $this->get('/about')->assertNotFound();

        $this->actingAs($admin)->deleteJson("/admin/api/pages/{$pageId}")->assertNoContent();
        $this->assertNotNull(DB::table('pages')->where('id', $pageId)->value('deleted_at'));
        $this->actingAs($admin)->getJson('/admin/api/pages')->assertOk()
            ->assertJsonMissing(['id' => $pageId]);
    }

    public function test_theme_chrome_buttons_and_lock_render_on_public_layout(): void
    {
        DesignManager::saveTokens([
            'mode' => 'dark',
            'chrome' => [
                'headerStyle' => 'pill',
                'footerStyle' => 'split',
                'footerShowLogo' => true,
                'footerShowSiteName' => true,
                'footerTagline' => 'Built with care',
            ],
            'buttons' => ['style' => 'outline'],
            'themeControl' => [
                'allowVisitorToggle' => false,
                'lockMode' => true,
            ],
        ]);

        DB::table('pages')->insert([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'published',
            'locale' => 'en',
            'builder_json' => json_encode(BuilderDocument::empty('Home'), JSON_THROW_ON_ERROR),
            'html_cache' => '<p>Hi</p>',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->updateOrInsert(['key' => 'homepage_slug'], [
            'value' => json_encode('home'),
            'group' => 'general',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('dc-header--pill', $html);
        $this->assertStringContainsString('dc-footer--split', $html);
        $this->assertStringContainsString('dc-btn-outline', $html);
        $this->assertStringContainsString('data-dc-theme-lock="1"', $html);
        $this->assertStringContainsString('data-dc-theme-toggle="0"', $html);
        $this->assertStringContainsString('Built with care', $html);
        $this->assertStringNotContainsString('data-dc-theme-toggle-btn', $html);
    }

    public function test_footer_socials_and_heroui_uikit_tokens_render(): void
    {
        DesignManager::saveTokens([
            'chrome' => [
                'footerStyle' => 'branded',
                'footerSocialStyle' => 'icons-labels',
                'footerSocials' => [
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/example', 'icon' => 'instagram'],
                    ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/example', 'icon' => 'linkedin'],
                ],
            ],
            'uiKit' => [
                'radiusPreset' => 'lg',
                'surface' => 'elevated',
                'density' => 'comfortable',
                'controlStyle' => 'soft',
                'socialStyle' => 'icons-labels',
            ],
        ]);

        DB::table('pages')->insert([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'published',
            'locale' => 'en',
            'builder_json' => json_encode([
                'schema' => BuilderDocument::CURRENT_SCHEMA,
                'title' => 'Home',
                'blocks' => [
                    [
                        'id' => '1',
                        'type' => 'section',
                        'props' => ['padding' => '2rem'],
                        'children' => [
                            [
                                'id' => '2',
                                'type' => 'social-links',
                                'props' => [
                                    'variant' => 'pills',
                                    'items' => [
                                        ['label' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'github'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'html_cache' => '',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->updateOrInsert(['key' => 'homepage_slug'], [
            'value' => json_encode('home'),
            'group' => 'general',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('dc-footer-socials', $html);
        $this->assertStringContainsString('dc-social-icon', $html);
        $this->assertStringContainsString('Instagram', $html);
        $this->assertStringContainsString('cdn.simpleicons.org/instagram', $html);
        $this->assertStringContainsString('dc-social-links--pills', $html);
        $this->assertStringContainsString('data-dc-surface="elevated"', $html);
        $this->assertStringContainsString('--dc-radius:0.85rem', $html);
    }
}
