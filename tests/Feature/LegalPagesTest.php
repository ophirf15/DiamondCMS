<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Legal\Support\LegalSettingsManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_render_with_operator_details(): void
    {
        LegalSettingsManager::save([
            'operator_name' => 'Ophir Yahalom',
            'contact_email' => 'hello@example.com',
            'contact_address' => '123 Main St',
            'website_url' => 'https://example.com',
            'jurisdiction' => 'State of California, United States',
            'effective_date' => '2026-07-13',
            'show_in_footer' => false,
            'pages' => ['privacy' => true, 'cookies' => true, 'terms' => true],
        ]);

        $this->get('/privacy')->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Ophir Yahalom')
            ->assertSee('hello@example.com')
            ->assertSee('State of California, United States');

        $this->get('/cookies')->assertOk()->assertSee('Cookie Policy');
        $this->get('/terms')->assertOk()->assertSee('Terms of Use')->assertSee('123 Main St');
    }

    public function test_disabled_legal_page_returns_404(): void
    {
        LegalSettingsManager::save([
            'pages' => ['privacy' => false, 'cookies' => true, 'terms' => true],
            'show_in_footer' => false,
        ]);

        $this->get('/privacy')->assertNotFound();
        $this->get('/cookies')->assertOk();
    }

    public function test_admin_can_save_legal_settings_and_sync_footer(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->putJson('/admin/api/legal', [
            'operator_name' => 'Test Operator',
            'contact_email' => 'legal@example.com',
            'jurisdiction' => 'Texas, United States',
            'effective_date' => '2026-07-13',
            'show_in_footer' => true,
            'pages' => ['privacy' => true, 'cookies' => true, 'terms' => true],
        ])->assertOk()
            ->assertJsonPath('operator_name', 'Test Operator');

        $footerId = DB::table('menus')->where('location', 'footer')->value('id');
        $this->assertNotNull($footerId);
        $labels = DB::table('menu_items')->where('menu_id', $footerId)->pluck('label')->all();
        $this->assertContains('Privacy', $labels);
        $this->assertContains('Cookies', $labels);
        $this->assertContains('Terms', $labels);
    }
}
