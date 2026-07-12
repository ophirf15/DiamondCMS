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
    }

    public function test_guest_cannot_manage_social_links(): void
    {
        $this->getJson('/admin/api/social-links')->assertUnauthorized();
    }
}
