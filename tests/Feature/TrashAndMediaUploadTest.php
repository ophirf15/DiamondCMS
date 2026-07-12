<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Media\Support\MediaManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TrashAndMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_pages_and_media_can_be_restored_or_purged(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);

        $pageId = DB::table('pages')->insertGetId([
            'title' => 'Gone',
            'slug' => 'gone',
            'status' => 'published',
            'locale' => 'en',
            'builder_json' => json_encode(BuilderDocument::empty('Gone'), JSON_THROW_ON_ERROR),
            'html_cache' => '',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->deleteJson("/admin/api/pages/{$pageId}")->assertNoContent();
        $this->assertNotNull(DB::table('pages')->where('id', $pageId)->value('deleted_at'));

        $trash = $this->actingAs($admin)->getJson('/admin/api/trash')->assertOk()->json();
        $this->assertCount(1, $trash['pages']);

        $this->actingAs($admin)->postJson("/admin/api/pages/{$pageId}/restore")->assertOk()
            ->assertJsonPath('status', 'draft');
        $this->assertNull(DB::table('pages')->where('id', $pageId)->value('deleted_at'));

        $this->actingAs($admin)->deleteJson("/admin/api/pages/{$pageId}")->assertNoContent();
        $this->actingAs($admin)->deleteJson("/admin/api/pages/{$pageId}/force")->assertNoContent();
        $this->assertNull(DB::table('pages')->where('id', $pageId)->first());

        $file = UploadedFile::fake()->image('hero.jpg', 640, 480);
        $upload = $this->actingAs($admin)->post('/admin/api/media', ['file' => $file], [
            'Accept' => 'application/json',
        ])->assertCreated();
        $mediaId = (int) $upload->json('id');
        $this->assertNotEmpty($upload->json('url'));

        $this->actingAs($admin)->deleteJson("/admin/api/media/{$mediaId}")->assertNoContent();
        $this->actingAs($admin)->postJson("/admin/api/media/{$mediaId}/restore")->assertOk();
        $this->assertNull(DB::table('media_items')->where('id', $mediaId)->value('deleted_at'));

        $this->actingAs($admin)->deleteJson("/admin/api/media/{$mediaId}")->assertNoContent();
        $this->actingAs($admin)->deleteJson("/admin/api/media/{$mediaId}/force")->assertNoContent();
        $this->assertNull(DB::table('media_items')->where('id', $mediaId)->first());
    }

    public function test_media_manager_payload_includes_url(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $id = app(MediaManager::class)->store(
            UploadedFile::fake()->image('a.png', 100, 100),
            $admin->id,
        );
        $payload = app(MediaManager::class)->payload($id);
        $this->assertArrayHasKey('url', $payload);
        $this->assertStringContainsString('/storage/', $payload['url']);
    }
}
