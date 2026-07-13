<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Design\Support\DesignManager;
use App\Domains\Design\Support\SocialLinksManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_social_links_library(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/admin/api/social-links')
            ->assertOk()
            ->assertJson([]);

        $saved = $this->actingAs($admin)
            ->putJson('/admin/api/social-links', [
                'links' => [
                    [
                        'id' => 'link-linkedin',
                        'label' => 'LinkedIn',
                        'url' => 'https://linkedin.com/in/me',
                        'icon' => 'linkedin',
                    ],
                    [
                        'id' => 'link-instagram',
                        'label' => 'Instagram',
                        'url' => 'https://instagram.com/me',
                        'icon' => 'instagram',
                    ],
                ],
            ])
            ->assertOk()
            ->json();

        $this->assertCount(2, $saved);
        $this->assertSame('LinkedIn', $saved[0]['label']);

        $this->actingAs($admin)
            ->getJson('/admin/api/social-links')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_footer_resolves_selected_library_links(): void
    {
        SocialLinksManager::save([
            ['id' => 'a', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com', 'icon' => 'linkedin'],
            ['id' => 'b', 'label' => 'Printables', 'url' => 'https://print.example', 'icon' => 'link'],
            ['id' => 'c', 'label' => 'Facebook', 'url' => 'https://facebook.com', 'icon' => 'facebook'],
        ]);

        $tokens = DesignManager::tokens();
        $tokens['chrome']['footerSocialLinkIds'] = ['a', 'c'];
        DesignManager::saveTokens($tokens);

        $items = DesignManager::footerSocialItems();

        $this->assertCount(2, $items);
        $this->assertSame('LinkedIn', $items[0]['label']);
        $this->assertSame('Facebook', $items[1]['label']);
        $this->assertSame(['a', 'c'], DesignManager::tokens()['chrome']['footerSocialLinkIds']);
        $this->assertSame('LinkedIn', DesignManager::tokens()['chrome']['footerSocials'][0]['label']);
    }

    public function test_theme_api_persists_footer_social_link_ids_and_public_footer_renders_them(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->putJson('/admin/api/social-links', [
            'links' => [
                ['id' => 'li', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/me', 'icon' => 'linkedin'],
                ['id' => 'ig', 'label' => 'Instagram', 'url' => 'https://instagram.com/me', 'icon' => 'instagram'],
            ],
        ])->assertOk();

        $tokens = DesignManager::tokens();
        $tokens['chrome']['footerSocialLinkIds'] = ['li', 'ig'];
        $tokens['chrome']['footerSocialStyle'] = 'icons';

        $this->actingAs($admin)->putJson('/admin/api/design', [
            'tokens' => $tokens,
        ])->assertOk();

        $this->assertSame(['li', 'ig'], DesignManager::tokens()['chrome']['footerSocialLinkIds']);

        $pageId = \Illuminate\Support\Facades\DB::table('pages')->insertGetId([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'published',
            'builder_json' => json_encode(\App\Domains\Builder\Support\BuilderDocument::empty('Home')),
            'html_cache' => '<p>Hello</p>',
            'published_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['key' => 'homepage_slug'],
            ['value' => json_encode('home'), 'group' => 'site', 'is_public' => true, 'updated_at' => now(), 'created_at' => now()],
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('dc-footer-socials', false)
            ->assertSee('linkedin.com/in/me', false)
            ->assertSee('instagram.com/me', false);

        unset($pageId);
    }

    public function test_guest_cannot_manage_social_links(): void
    {
        $this->getJson('/admin/api/social-links')->assertUnauthorized();
    }
}
