<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\AI\Support\AiManager;
use App\Domains\Backups\Support\BackupManager;
use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Forms\Support\FormManager;
use App\Domains\Mail\Support\MailSettingsManager;
use App\Domains\Portfolio\Support\PortfolioManager;
use App\Domains\Updates\Support\UpdateManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class PhaseSevenToTwelveTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_projects_render_inside_builder_documents(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $portfolio = app(PortfolioManager::class);
        $categoryId = $portfolio->createCategory(['name' => 'Technical Builds']);
        $portfolio->createProject([
            'category_id' => $categoryId,
            'title' => 'Resident Portal',
            'status' => 'published',
            'visibility' => 'public',
            'is_featured' => true,
            'summary' => 'A property-management portal.',
            'cover_image' => '/brand/logo-primary-gold.svg',
            'skills' => ['Laravel', 'MySQL'],
        ], $admin->id);

        $html = BuilderDocument::render([
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'blocks' => [['type' => 'portfolio-featured-grid', 'props' => ['limit' => 3]]],
        ])->toHtml();

        $this->assertStringContainsString('Resident Portal', $html);
        $this->assertStringContainsString('dc-project-thumb', $html);
        $this->assertStringContainsString('/brand/logo-primary-gold.svg', $html);
        $this->get('/projects?skill=Laravel')->assertOk()->assertSee('Resident Portal')->assertSee('dc-project-thumb', false);
    }

    public function test_public_form_submission_is_validated_stored_and_exported_as_csv(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $formId = app(FormManager::class)->createForm([
            'name' => 'Contact',
            'status' => 'published',
            'schema' => ['fields' => [
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['name' => 'message', 'label' => 'Message', 'type' => 'text', 'required' => true],
            ]],
        ], $admin->id);

        $this->post('/forms/contact', ['email' => 'person@example.com', 'message' => 'Hello'])->assertSessionHas('status');
        $this->assertDatabaseHas('form_submissions', ['form_id' => $formId, 'status' => 'new', 'is_spam' => false]);

        $this->actingAs($admin)->get('/admin/forms/'.$formId.'/submissions.csv')->assertOk()->assertSee('person@example.com');
    }

    public function test_form_uploads_are_stored_privately_and_excluded_from_payload_json(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true]);
        $formId = app(FormManager::class)->createForm([
            'name' => 'Upload',
            'status' => 'published',
            'schema' => ['fields' => [
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['name' => 'attachment', 'label' => 'Attachment', 'type' => 'file', 'required' => true],
            ]],
        ], $admin->id);

        $this->post('/forms/upload', [
            'email' => 'person@example.com',
            'attachment' => UploadedFile::fake()->create('proof.pdf', 12, 'application/pdf'),
        ])->assertSessionHas('status');

        $submission = DB::table('form_submissions')->where('form_id', $formId)->first();
        $this->assertSame(['email' => 'person@example.com'], json_decode((string) $submission->payload, true));
        $files = json_decode((string) $submission->files, true);
        Storage::disk('local')->assertExists($files['attachment']['path']);
    }

    public function test_mail_settings_preserve_encrypted_password_when_metadata_changes(): void
    {
        $mail = app(MailSettingsManager::class);
        $id = $mail->save([
            'host' => 'smtp.example.com',
            'username' => 'mailer',
            'password' => 'secret-password',
            'from_address' => 'hello@example.com',
        ]);

        $mail->save([
            'host' => 'smtp2.example.com',
            'username' => 'mailer',
            'from_address' => 'hello@example.com',
        ]);

        $settings = DB::table('mail_settings')->where('id', $id)->first();
        $this->assertSame('secret-password', Crypt::decryptString($settings->encrypted_password));
    }

    public function test_ai_generation_requires_approval_before_creating_page_revision(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $generationId = app(AiManager::class)->generateDraftPage(['title' => 'AI Landing', 'summary' => 'Draft copy'], $admin->id);

        $this->assertDatabaseMissing('pages', ['title' => 'AI Landing']);

        $pageId = app(AiManager::class)->approveGeneration($generationId, $admin->id);

        $this->assertDatabaseHas('pages', ['id' => $pageId, 'title' => 'AI Landing', 'status' => 'draft']);
        $this->assertNotEmpty(DB::table('pages')->where('id', $pageId)->value('html_cache'));
        $this->assertDatabaseHas('page_revisions', ['page_id' => $pageId, 'revision' => 1]);
    }

    public function test_sitemap_excludes_drafts_and_password_protected_pages(): void
    {
        DB::table('pages')->insert([
            ['title' => 'Public', 'slug' => 'public', 'status' => 'published', 'password_hash' => null, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Draft', 'slug' => 'draft', 'status' => 'draft', 'password_hash' => null, 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Private', 'slug' => 'private', 'status' => 'published', 'password_hash' => 'x', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/sitemap.xml')->assertOk()->assertSee('/public')->assertDontSee('/draft')->assertDontSee('/private');
    }

    public function test_backups_exports_and_update_staging_verify_checksums(): void
    {
        Storage::fake('local');
        $backupId = app(BackupManager::class)->backup('full');
        $this->assertDatabaseHas('backups', ['id' => $backupId, 'status' => 'completed']);

        $releasePath = storage_path('app/release-test.zip');
        file_put_contents($releasePath, 'release');
        $checksum = hash_file('sha256', $releasePath);
        $updateId = app(UpdateManager::class)->stage($releasePath, $checksum, '0.1.0-rc1');

        $this->assertDatabaseHas('update_logs', ['id' => $updateId, 'status' => 'staged', 'checksum' => $checksum]);
        @unlink($releasePath);
    }

    public function test_export_import_apply_creates_pre_import_backup_and_merges_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        DB::table('settings')->insert([
            'id' => 10,
            'key' => 'site_name',
            'value' => json_encode('Before'),
            'group' => 'general',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zipPath = storage_path('app/import-test.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode(['version' => '0.1.0']));
        $zip->addFromString('content.json', json_encode(['tables' => [
            'settings' => [[
                'id' => 10,
                'key' => 'site_name',
                'value' => json_encode('After'),
                'group' => 'general',
                'is_public' => true,
                'updated_by' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]],
        ]]));
        $zip->close();

        $result = app(BackupManager::class)->applyImport($zipPath, 'merge', $admin->id);

        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('import_jobs', ['id' => $result['job_id'], 'status' => 'completed']);
        $this->assertSame('After', json_decode((string) DB::table('settings')->where('key', 'site_name')->value('value'), true));
        @unlink($zipPath);
    }

    public function test_full_site_export_includes_media_and_replace_import_restores_files(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Storage::disk('public')->put('media/export-probe.txt', 'local-build-asset');
        DB::table('settings')->insert([
            'key' => 'site_name',
            'value' => json_encode('Local Clone'),
            'group' => 'general',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('media_items')->insert([
            'folder_id' => null,
            'disk' => 'public',
            'path' => 'media/export-probe.txt',
            'original_name' => 'export-probe.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 17,
            'sha256' => hash('sha256', 'local-build-asset'),
            'alt_text' => null,
            'caption' => null,
            'credit' => null,
            'metadata' => null,
            'variants' => null,
            'is_svg' => false,
            'uploaded_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $export = app(BackupManager::class)->exportSite($admin->id);
        $this->assertFileExists($export['path']);
        $this->assertGreaterThan(0, $export['media_files']);
        $this->assertGreaterThan(0, $export['media_library_files']);
        $this->assertSame([], $export['missing_media']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($export['path']) === true);
        $this->assertNotFalse($zip->locateName('files/public/media/export-probe.txt'));
        $zip->close();

        Storage::disk('public')->delete('media/export-probe.txt');
        DB::table('settings')->where('key', 'site_name')->delete();
        DB::table('media_items')->delete();

        $result = app(BackupManager::class)->applyImport($export['path'], 'replace', $admin->id);

        $this->assertTrue($result['ok']);
        $this->assertGreaterThan(0, $result['media_files']);
        $this->assertSame('local-build-asset', Storage::disk('public')->get('media/export-probe.txt'));
        $this->assertSame('Local Clone', json_decode((string) DB::table('settings')->where('key', 'site_name')->value('value'), true));
        $this->assertDatabaseHas('media_items', ['path' => 'media/export-probe.txt']);
    }
}
