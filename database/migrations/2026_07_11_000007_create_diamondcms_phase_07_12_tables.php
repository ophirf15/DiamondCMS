<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('project')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('portfolio_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('project')->index();
            $table->string('status')->default('draft')->index();
            $table->string('visibility')->default('private')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->date('started_on')->nullable()->index();
            $table->date('completed_on')->nullable()->index();
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->string('client')->nullable();
            $table->string('role')->nullable();
            $table->string('url')->nullable();
            $table->string('repository_url')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('summary')->nullable();
            $table->longText('case_study')->nullable();
            $table->json('skills')->nullable();
            $table->json('tags')->nullable();
            $table->json('metrics')->nullable();
            $table->json('before_after_media')->nullable();
            $table->json('gallery')->nullable();
            $table->json('builder_json')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['visibility', 'status', 'is_featured']);
        });

        Schema::create('project_relations', function (Blueprint $table): void {
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('related_project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('relation_type')->default('related');
            $table->primary(['project_id', 'related_project_id']);
        });

        Schema::create('personal_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->json('items')->nullable();
            $table->json('builder_json')->nullable();
            $table->string('visibility')->default('private')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('testimonials', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('company')->nullable();
            $table->string('avatar')->nullable();
            $table->text('quote');
            $table->boolean('is_featured')->default(false)->index();
            $table->string('visibility')->default('private')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('galleries', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('image')->index();
            $table->string('visibility')->default('private')->index();
            $table->json('items')->nullable();
            $table->timestamps();
        });

        Schema::create('timeline_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('type')->default('milestone')->index();
            $table->date('occurred_on')->nullable()->index();
            $table->text('body')->nullable();
            $table->string('visibility')->default('private')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('mail_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('mailer')->default('smtp');
            $table->string('host');
            $table->unsignedInteger('port')->default(587);
            $table->string('username')->nullable();
            $table->text('encrypted_password')->nullable();
            $table->string('encryption')->nullable();
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('subject');
            $table->longText('body');
            $table->json('variables')->nullable();
            $table->timestamps();
        });

        Schema::create('email_delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('template_key')->nullable()->index();
            $table->string('recipient_hash')->index();
            $table->string('status')->default('queued')->index();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('forms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft')->index();
            $table->json('schema');
            $table->json('notifications')->nullable();
            $table->json('spam_config')->nullable();
            $table->text('success_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->unsignedInteger('retention_days')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->json('payload');
            $table->json('files')->nullable();
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->boolean('is_spam')->default(false)->index();
            $table->string('ip_hash')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->index();
            $table->string('name');
            $table->text('encrypted_api_key')->nullable();
            $table->string('base_url')->nullable();
            $table->json('models')->nullable();
            $table->string('default_model')->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->unsignedInteger('monthly_token_limit')->nullable();
            $table->decimal('monthly_cost_limit', 10, 4)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['provider', 'name']);
        });

        Schema::create('ai_prompt_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->unsignedInteger('version')->default(1);
            $table->string('purpose')->index();
            $table->longText('prompt');
            $table->json('defaults')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['key', 'version']);
        });

        Schema::create('ai_generations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('prompt_template_id')->nullable()->constrained('ai_prompt_templates')->nullOnDelete();
            $table->string('task')->index();
            $table->string('status')->default('draft')->index();
            $table->json('input_summary')->nullable();
            $table->json('output_payload')->nullable();
            $table->json('diff')->nullable();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('source')->unique();
            $table->string('target');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_audits', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('findings');
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('accessibility_audits', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('findings');
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('backups', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('full')->index();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('checksum', 64)->index();
            $table->unsignedBigInteger('size')->default(0);
            $table->json('manifest')->nullable();
            $table->string('status')->default('completed')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('import_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('mode')->default('dry-run')->index();
            $table->string('status')->default('pending')->index();
            $table->string('source_path')->nullable();
            $table->foreignId('pre_import_backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->json('report')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('update_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('version')->index();
            $table->string('status')->default('staged')->index();
            $table->string('source_url')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('stage_path')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        collect([
            'update_logs',
            'import_jobs',
            'backups',
            'accessibility_audits',
            'seo_audits',
            'redirects',
            'ai_usage_logs',
            'ai_generations',
            'ai_prompt_templates',
            'ai_providers',
            'form_submissions',
            'forms',
            'email_delivery_logs',
            'email_templates',
            'mail_settings',
            'timeline_entries',
            'galleries',
            'testimonials',
            'personal_contents',
            'project_relations',
            'projects',
            'portfolio_categories',
        ])->each(fn (string $table) => Schema::dropIfExists($table));
    }
};
