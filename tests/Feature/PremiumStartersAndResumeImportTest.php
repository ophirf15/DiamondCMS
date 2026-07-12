<?php

namespace Tests\Feature;

use App\Domains\Builder\Support\BuilderDocument;
use App\Domains\Builder\Support\StarterTemplates;
use App\Domains\Design\Support\DesignManager;
use App\Domains\Resume\Support\ResumeManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PremiumStartersAndResumeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_templates_are_visually_unique_with_preview_themes(): void
    {
        $defs = StarterTemplates::definitions();
        $this->assertCount(13, $defs);

        $hashes = collect($defs)->map(fn (array $def) => md5(json_encode($def['document'])))->unique();
        $this->assertCount(13, $hashes);

        $themes = collect($defs)->pluck('preview_theme')->unique();
        $this->assertGreaterThanOrEqual(4, $themes->count());

        $ophir = collect($defs)->firstWhere('name', 'Ophir professional');
        $this->assertNotNull($ophir);
        $this->assertSame('sidebar-dark', $ophir['document']['meta']['shell']);
        $this->assertSame('dark-teal', $ophir['preview_theme']);
    }

    public function test_text_blocks_preserve_line_breaks(): void
    {
        $html = (string) BuilderDocument::render([
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'title' => 'Breaks',
            'blocks' => [
                [
                    'id' => 't1',
                    'type' => 'text',
                    'props' => ['text' => "Line one\nLine two"],
                ],
            ],
        ]);

        $this->assertStringContainsString("Line one\nLine two", $html);
        $this->assertStringContainsString('dc-text', $html);
    }

    public function test_new_builder_blocks_render_on_public_pages(): void
    {
        $html = (string) BuilderDocument::render([
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'title' => 'Blocks',
            'blocks' => [
                [
                    'id' => '1',
                    'type' => 'section',
                    'props' => ['padding' => '2rem'],
                    'children' => [
                        [
                            'id' => '2',
                            'type' => 'stats-row',
                            'props' => [
                                'items' => [
                                    ['value' => '98%', 'label' => 'Occupancy'],
                                ],
                            ],
                        ],
                        [
                            'id' => '3',
                            'type' => 'timeline',
                            'props' => [
                                'items' => [
                                    [
                                        'date' => '2019 — Current',
                                        'title' => 'Manager',
                                        'organization' => 'Woodmont',
                                        'bullets' => ['Led operations.'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => '4',
                            'type' => 'gallery-grid',
                            'props' => [
                                'images' => [
                                    ['src' => '/brand/favicon.svg', 'alt' => 'Sample'],
                                ],
                            ],
                        ],
                        [
                            'id' => '5',
                            'type' => 'social-links',
                            'props' => [
                                'variant' => 'icons-labels',
                                'items' => [
                                    ['label' => 'Email', 'url' => 'mailto:a@b.com', 'icon' => 'email'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('dc-stats-row', $html);
        $this->assertStringContainsString('98%', $html);
        $this->assertStringContainsString('dc-timeline', $html);
        $this->assertStringContainsString('Woodmont', $html);
        $this->assertStringContainsString('dc-gallery-grid', $html);
        $this->assertStringContainsString('dc-social-links', $html);
        $this->assertStringContainsString('dc-social-icon', $html);
        $this->assertStringContainsString('data-dc-animate', $html);
    }

    public function test_sidebar_dark_shell_renders_on_public_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $document = [
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'title' => 'Home',
            'meta' => ['shell' => 'sidebar-dark', 'preview_theme' => 'dark-teal'],
            'blocks' => [
                [
                    'id' => '1',
                    'type' => 'section',
                    'props' => ['padding' => '2rem'],
                    'children' => [
                        ['id' => '2', 'type' => 'heading', 'props' => ['level' => 1, 'text' => 'Ophir Yahalom']],
                    ],
                ],
            ],
        ];

        DB::table('pages')->insert([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'published',
            'builder_json' => json_encode($document),
            'html_cache' => (string) BuilderDocument::render($document),
            'published_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
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

        $this->get('/')
            ->assertOk()
            ->assertSee('dc-shell-sidebar-dark', false)
            ->assertSee('Ophir Yahalom');
    }

    public function test_resume_txt_import_parses_sections_and_approves(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $fixture = <<<'TXT'
Ophir Yahalom
Property Manager
contact@ophiryahalom.com
1-415-860-6090

EXPERIENCE

June 2019 - Current
Woodmont Real Estate Services
Multi-Site Property Manager
- Oversaw capital improvements and budgeting.
- Restored occupancy to 98%.

March 2019 - June 2019
Greystar, Larkspur California
Assistant Community Manager
- Managed leasing staff of two.

EDUCATION

June 2016
Jewish Community High School of the Bay (JCHS), San Francisco, CA

SKILLS
Outstanding customer service
Proficient in Yardi CRM
TXT;

        $file = UploadedFile::fake()->createWithContent('ophir-resume.txt', $fixture);

        $response = $this->post('/admin/api/resumes/import', [
            'file' => $file,
        ]);
        $response->assertCreated();
        $importId = (int) $response->json('id');

        $payload = json_decode((string) DB::table('resume_imports')->where('id', $importId)->value('parsed_payload'), true);
        $this->assertSame('Ophir Yahalom', $payload['name']);
        $this->assertNotEmpty($payload['sections']);
        $types = collect($payload['sections'])->pluck('type')->unique()->all();
        $this->assertContains('experience', $types);
        $this->assertContains('education', $types);

        $this->putJson("/admin/api/resumes/import/{$importId}", [
            'name' => 'Ophir Yahalom',
            'headline' => 'Property Manager',
            'summary' => 'Operator focused on resident care.',
            'email' => 'contact@ophiryahalom.com',
            'sections' => $payload['sections'],
        ])->assertOk();

        $this->postJson("/admin/api/resumes/import/{$importId}/approve")
            ->assertOk()
            ->assertJsonStructure(['resume_profile_id']);

        $profileId = (int) DB::table('resume_imports')->where('id', $importId)->value('resume_profile_id');
        $this->assertSame('Ophir Yahalom', DB::table('resume_profiles')->where('id', $profileId)->value('name'));
        $this->assertGreaterThanOrEqual(2, DB::table('resume_sections')->where('resume_profile_id', $profileId)->count());
    }

    public function test_resume_manager_parses_experience_dates(): void
    {
        $manager = app(ResumeManager::class);
        $payload = $manager->bestEffortParse(<<<'TXT'
Alex Rivera
Staff Engineer

EXPERIENCE
2021 — Current
Meridian Labs
Staff Engineer
- Led platform migration.

EDUCATION
2014
State University
B.S. Computer Science
TXT);

        $this->assertSame('Alex Rivera', $payload['name']);
        $experience = collect($payload['sections'])->firstWhere('type', 'experience');
        $this->assertNotNull($experience);
        $this->assertStringContainsString('2021', (string) ($experience['date'] ?? ''));
    }

    public function test_motion_token_flag_is_readable(): void
    {
        DesignManager::saveTokens(['motion' => ['enabled' => false]]);
        $this->assertFalse(DesignManager::motionEnabled());

        DesignManager::saveTokens(['motion' => ['enabled' => true]]);
        $this->assertTrue(DesignManager::motionEnabled());
    }

    public function test_navy_atmosphere_is_injected_into_design_css(): void
    {
        DesignManager::saveTokens([
            'mode' => 'dark',
            'atmosphere' => ['preset' => 'navy', 'custom' => ''],
        ]);

        $css = DesignManager::cssVariables()->toHtml();
        $this->assertStringContainsString('--dc-site-bg:', $css);
        $this->assertStringContainsString('var(--dc-bg)', $css);
        $this->assertStringContainsString('var(--dc-primary)', $css);
    }
}
