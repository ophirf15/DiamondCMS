<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->index();
            $table->boolean('is_disabled')->default(false)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->string('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->boolean('is_public')->default(false)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('locale', 12)->default('en')->index();
            $table->string('status')->default('draft')->index();
            $table->json('builder_json')->nullable();
            $table->longText('html_cache')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('password_hash')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('page_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['page_id', 'revision']);
        });

        Schema::create('preview_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('location')->unique();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('builder_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('page')->index();
            $table->json('builder_json');
            $table->boolean('is_system')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('design_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->json('payload');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('media_folders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('media_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension', 16)->nullable();
            $table->unsignedBigInteger('size');
            $table->string('sha256')->index();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->string('credit')->nullable();
            $table->json('metadata')->nullable();
            $table->json('variants')->nullable();
            $table->boolean('is_svg')->default(false)->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('media_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('media_item_tag', function (Blueprint $table): void {
            $table->foreignId('media_item_id')->constrained('media_items')->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->primary(['media_item_id', 'media_tag_id']);
        });

        Schema::create('media_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_item_id')->constrained('media_items')->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('field')->nullable();
            $table->timestamps();
            $table->unique(['media_item_id', 'subject_type', 'subject_id', 'field'], 'media_usage_unique');
        });

        Schema::create('media_upload_chunks', function (Blueprint $table): void {
            $table->id();
            $table->string('upload_id')->index();
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('total_chunks');
            $table->string('path');
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['upload_id', 'chunk_index']);
        });

        Schema::create('resume_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('headline')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->text('summary')->nullable();
            $table->json('links')->nullable();
            $table->timestamps();
        });

        Schema::create('resume_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resume_profile_id')->constrained('resume_profiles')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title')->nullable();
            $table->string('organization')->nullable();
            $table->string('location')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->json('bullets')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('resume_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resume_profile_id')->constrained('resume_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('visibility')->default('private')->index();
            $table->text('summary_override')->nullable();
            $table->json('section_order')->nullable();
            $table->json('hidden_sections')->nullable();
            $table->json('skill_overrides')->nullable();
            $table->json('builder_json')->nullable();
            $table->timestamps();
        });

        Schema::create('resume_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resume_profile_id')->nullable()->constrained('resume_profiles')->nullOnDelete();
            $table->foreignId('media_item_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->longText('extracted_text')->nullable();
            $table->json('parsed_payload')->nullable();
            $table->string('status')->default('needs_review')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('resume_share_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resume_variant_id')->constrained('resume_variants')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        collect([
            'resume_share_links',
            'resume_imports',
            'resume_variants',
            'resume_sections',
            'resume_profiles',
            'media_upload_chunks',
            'media_usages',
            'media_item_tag',
            'media_tags',
            'media_items',
            'media_folders',
            'design_revisions',
            'builder_templates',
            'menu_items',
            'menus',
            'preview_tokens',
            'page_revisions',
            'pages',
            'activity_logs',
            'settings',
        ])->each(fn (string $table) => Schema::dropIfExists($table));

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'is_admin',
                'is_disabled',
                'last_login_at',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
