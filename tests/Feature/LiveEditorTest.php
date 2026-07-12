<?php

namespace Tests\Feature;

use App\Domains\Builder\Support\BuilderDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LiveEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_live_editor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageId = DB::table('pages')->insertGetId([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'draft',
            'builder_json' => json_encode(BuilderDocument::empty('Home')),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.live', $pageId))
            ->assertOk()
            ->assertSee('live-editor-app', false)
            ->assertSee('Home');
    }

    public function test_guest_cannot_open_live_editor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageId = DB::table('pages')->insertGetId([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'draft',
            'builder_json' => json_encode(BuilderDocument::empty('Home')),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('admin.live', $pageId))->assertRedirect();
    }

    public function test_published_page_shows_edit_live_fab_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $document = BuilderDocument::empty('About');
        DB::table('pages')->insert([
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'builder_json' => json_encode($document),
            'html_cache' => (string) BuilderDocument::render($document),
            'published_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/about')
            ->assertOk()
            ->assertSee('Edit live')
            ->assertSee('dc-live-edit-fab', false);
    }
}
