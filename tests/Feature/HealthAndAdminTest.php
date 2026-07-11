<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HealthAndAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_health_exposes_safe_status(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonStructure(['status', 'app', 'version', 'timestamp'])
            ->assertJsonMissingPath('checks.database');
    }

    public function test_detailed_health_requires_admin(): void
    {
        $this->getJson('/admin/health')->assertUnauthorized();

        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->getJson('/admin/health')->assertForbidden();
    }

    public function test_admin_can_open_dashboard_shell(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('admin-app');
    }
}
