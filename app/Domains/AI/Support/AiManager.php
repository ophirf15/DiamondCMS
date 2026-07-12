<?php

declare(strict_types=1);

namespace App\Domains\AI\Support;

use App\Domains\Builder\Support\BuilderDocument;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class AiManager
{
    public const ALLOWED_PROVIDERS = ['openai', 'anthropic', 'gemini'];

    public function saveProvider(array $data, ?int $userId = null): int
    {
        $provider = (string) $data['provider'];
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            throw new RuntimeException('Unsupported AI provider.');
        }

        $name = $data['name'] ?? Str::headline($provider);
        $existing = DB::table('ai_providers')->where('provider', $provider)->where('name', $name)->first();

        $payload = [
            'provider' => $provider,
            'name' => $name,
            'encrypted_api_key' => filled($data['api_key'] ?? null) ? Crypt::encryptString((string) $data['api_key']) : (is_object($existing) ? $existing->encrypted_api_key : null),
            'base_url' => $data['base_url'] ?? null,
            'models' => json_encode($data['models'] ?? (json_decode((string) (is_object($existing) ? ($existing->models ?? '') : ''), true) ?: $this->fallbackModels($provider)), JSON_THROW_ON_ERROR),
            'default_model' => $data['default_model'] ?? (is_object($existing) ? $existing->default_model : null) ?? $this->fallbackModels($provider)[0],
            'is_enabled' => (bool) ($data['is_enabled'] ?? true),
            'monthly_token_limit' => $data['monthly_token_limit'] ?? null,
            'monthly_cost_limit' => $data['monthly_cost_limit'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ai_providers')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        $payload['created_at'] = now();

        return (int) DB::table('ai_providers')->insertGetId($payload);
    }

    public function discoverModels(int $providerId): array
    {
        $provider = DB::table('ai_providers')->where('id', $providerId)->first();
        abort_unless($provider !== null, 404);

        if (! $provider->encrypted_api_key) {
            return $this->fallbackModels($provider->provider);
        }

        $key = Crypt::decryptString($provider->encrypted_api_key);
        $models = match ($provider->provider) {
            'openai' => Http::withToken($key)->timeout(10)->get(($provider->base_url ?: 'https://api.openai.com/v1').'/models')->json('data.*.id') ?: [],
            'anthropic' => Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->timeout(10)->get(($provider->base_url ?: 'https://api.anthropic.com').'/v1/models')->json('data.*.id') ?: [],
            'gemini' => collect(Http::withHeaders(['x-goog-api-key' => $key])->timeout(10)->get(($provider->base_url ?: 'https://generativelanguage.googleapis.com').'/v1beta/models')->json('models') ?: [])->pluck('name')->all(),
            default => [],
        };

        $models = $models ?: $this->fallbackModels($provider->provider);
        DB::table('ai_providers')->where('id', $providerId)->update(['models' => json_encode($models, JSON_THROW_ON_ERROR), 'updated_at' => now()]);

        return $models;
    }

    public function createPromptTemplate(array $data): int
    {
        return (int) DB::table('ai_prompt_templates')->insertGetId([
            'key' => $data['key'],
            'version' => $data['version'] ?? 1,
            'purpose' => $data['purpose'],
            'prompt' => $data['prompt'],
            'defaults' => json_encode($data['defaults'] ?? [], JSON_THROW_ON_ERROR),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function generateDraftPage(array $answers, ?int $userId = null): int
    {
        $title = (string) ($answers['title'] ?? 'AI draft page');
        $document = BuilderDocument::validate([
            'schema' => BuilderDocument::CURRENT_SCHEMA,
            'title' => $title,
            'blocks' => [
                ['type' => 'section', 'props' => ['padding' => '4rem 1rem'], 'children' => [
                    ['type' => 'heading', 'props' => ['level' => 1, 'text' => $title]],
                    ['type' => 'text', 'props' => ['text' => e((string) ($answers['summary'] ?? 'Generated draft content awaiting approval.'))]],
                    ['type' => 'portfolio-featured-grid', 'props' => ['limit' => 3]],
                ]],
            ],
        ]);

        return (int) DB::table('ai_generations')->insertGetId([
            'task' => 'draft_page',
            'status' => 'pending_approval',
            'input_summary' => json_encode(['title' => $title, 'context_keys' => array_keys($answers)], JSON_THROW_ON_ERROR),
            'output_payload' => json_encode(['page' => ['title' => $title, 'slug' => Str::slug($title), 'builder_json' => $document]], JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function approveGeneration(int $generationId, int $userId): int
    {
        return DB::transaction(function () use ($generationId, $userId): int {
            $generation = DB::table('ai_generations')->where('id', $generationId)->lockForUpdate()->first();
            abort_unless($generation && $generation->status === 'pending_approval', 404);

            $payload = json_decode((string) $generation->output_payload, true) ?: [];
            $page = $payload['page'] ?? [];
            $builder = BuilderDocument::validate($page['builder_json'] ?? BuilderDocument::empty((string) ($page['title'] ?? 'AI draft')));
            $pageId = (int) DB::table('pages')->insertGetId([
                'title' => $page['title'],
                'slug' => $page['slug'].'-'.Str::lower(Str::random(5)),
                'status' => 'draft',
                'builder_json' => json_encode($builder, JSON_THROW_ON_ERROR),
                'html_cache' => BuilderDocument::render($builder)->toHtml(),
                'excerpt' => 'AI-generated draft awaiting editorial review.',
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('page_revisions')->insert([
                'page_id' => $pageId,
                'revision' => 1,
                'snapshot' => json_encode(['source' => 'ai_generation', 'generation_id' => $generationId, 'page' => $page], JSON_THROW_ON_ERROR),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ai_generations')->where('id', $generationId)->update([
                'status' => 'approved',
                'page_id' => $pageId,
                'approved_by' => $userId,
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

            return $pageId;
        });
    }

    private function fallbackModels(string $provider): array
    {
        return match ($provider) {
            'openai' => ['gpt-4.1-mini', 'gpt-4.1'],
            'anthropic' => ['claude-3-5-sonnet-latest', 'claude-3-5-haiku-latest'],
            'gemini' => ['gemini-1.5-pro', 'gemini-1.5-flash'],
            default => [],
        };
    }
}
