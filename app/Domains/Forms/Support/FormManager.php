<?php

declare(strict_types=1);

namespace App\Domains\Forms\Support;

use App\Domains\Activity\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FormManager
{
    public function createForm(array $data, ?int $userId = null): int
    {
        $schema = $this->normalizeSchema($data['schema'] ?? []);

        return (int) DB::table('forms')->insertGetId([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?? $data['name']),
            'status' => $data['status'] ?? 'draft',
            'schema' => json_encode($schema, JSON_THROW_ON_ERROR),
            'notifications' => json_encode($data['notifications'] ?? [], JSON_THROW_ON_ERROR),
            'spam_config' => json_encode($data['spam_config'] ?? ['honeypot' => 'website', 'rate_limit' => 5], JSON_THROW_ON_ERROR),
            'success_message' => $data['success_message'] ?? 'Thank you. Your submission has been received.',
            'redirect_url' => $data['redirect_url'] ?? null,
            'retention_days' => $data['retention_days'] ?? 365,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function publicForm(string $slug): object
    {
        $form = DB::table('forms')->where('slug', $slug)->where('status', 'published')->whereNull('deleted_at')->first();
        abort_unless($form, 404);

        return $this->decodeForm($form);
    }

    public function submit(string $slug, Request $request): int
    {
        $form = $this->publicForm($slug);
        $spam = $form->spam_config;
        $honeypot = (string) ($spam['honeypot'] ?? 'website');

        if ($request->filled($honeypot)) {
            ActivityLogger::log('forms.spam_honeypot', (object) ['id' => $form->id], ['form' => $form->slug], $request);

            return $this->storeSubmission($form, [], $request, true);
        }

        $limiterKey = 'form:'.$form->id.':'.sha1((string) $request->ip());
        $maxAttempts = (int) ($spam['rate_limit'] ?? 5);
        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            throw ValidationException::withMessages(['form' => 'Please wait before submitting again.']);
        }
        RateLimiter::hit($limiterKey, 60);

        $payload = $request->validate($this->rules($form->schema));

        if (! $this->passesTurnstile($spam, $request)) {
            ActivityLogger::log('forms.spam_turnstile_failed', (object) ['id' => $form->id], ['form' => $form->slug], $request);
            throw ValidationException::withMessages(['cf-turnstile-response' => 'Verification failed.']);
        }

        [$values, $files] = $this->splitSubmissionPayload($form, $payload, $request);

        return $this->storeSubmission($form, $values, $request, false, $files);
    }

    /** @return Collection<int, object> */
    public function submissions(int $formId): Collection
    {
        return DB::table('form_submissions')->where('form_id', $formId)->whereNull('deleted_at')->latest()->get();
    }

    public function csv(int $formId): string
    {
        $rows = $this->submissions($formId);
        $fields = $rows->flatMap(fn (object $row) => array_keys(json_decode((string) $row->payload, true) ?: []))->unique()->values()->all();
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_merge(['id', 'status', 'created_at'], $fields));

        foreach ($rows as $row) {
            $payload = json_decode((string) $row->payload, true) ?: [];
            fputcsv($handle, array_merge([$row->id, $row->status, $row->created_at], array_map(fn (string $field) => $payload[$field] ?? '', $fields)));
        }

        rewind($handle);

        return stream_get_contents($handle) ?: '';
    }

    private function storeSubmission(object $form, array $payload, Request $request, bool $spam, array $files = []): int
    {
        return (int) DB::table('form_submissions')->insertGetId([
            'form_id' => $form->id,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'files' => json_encode($files, JSON_THROW_ON_ERROR),
            'status' => $spam ? 'spam' : 'new',
            'is_spam' => $spam,
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function decodeForm(object $form): object
    {
        $form->schema = json_decode((string) $form->schema, true) ?: [];
        $form->notifications = json_decode((string) $form->notifications, true) ?: [];
        $form->spam_config = json_decode((string) $form->spam_config, true) ?: [];

        return $form;
    }

    private function normalizeSchema(array $schema): array
    {
        $fields = collect($schema['fields'] ?? [])
            ->map(fn (array $field) => [
                'name' => Str::snake((string) ($field['name'] ?? $field['label'] ?? Str::random(6))),
                'label' => (string) ($field['label'] ?? $field['name'] ?? 'Field'),
                'type' => (string) ($field['type'] ?? 'text'),
                'required' => (bool) ($field['required'] ?? false),
                'rules' => array_values($field['rules'] ?? []),
                'options' => array_values($field['options'] ?? []),
                'accept' => array_values($field['accept'] ?? []),
            ])
            ->values()
            ->all();

        return ['version' => 1, 'fields' => $fields];
    }

    /** @return array<string, array<int, string>> */
    private function rules(array $schema): array
    {
        return collect($schema['fields'] ?? [])->mapWithKeys(function (array $field): array {
            $rules = $field['required'] ? ['required'] : ['nullable'];
            $typeRules = match ($field['type']) {
                'email' => ['email'],
                'url' => ['url'],
                'number' => ['numeric'],
                'file' => ['file', 'max:10240'],
                'checkbox' => ['accepted'],
                'select' => ['string'],
                'textarea' => ['string'],
                default => ['string'],
            };

            return [$field['name'] => array_merge($rules, $typeRules, $field['rules'] ?? [])];
        })->all();
    }

    private function passesTurnstile(array $spam, Request $request): bool
    {
        $turnstile = $spam['turnstile'] ?? [];
        if (! ($turnstile['enabled'] ?? false)) {
            return true;
        }

        $token = $request->string('cf-turnstile-response')->toString();
        if ($token === '') {
            return false;
        }

        $secret = (string) ($turnstile['secret'] ?? config('services.turnstile.secret'));
        if ($secret === '') {
            return false;
        }

        return (bool) Http::asForm()
            ->timeout(5)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ])
            ->json('success', false);
    }

    private function splitSubmissionPayload(object $form, array $payload, Request $request): array
    {
        $files = [];
        foreach ($form->schema['fields'] ?? [] as $field) {
            if (($field['type'] ?? null) !== 'file') {
                continue;
            }

            $name = (string) $field['name'];
            unset($payload[$name]);

            if (! $request->hasFile($name)) {
                continue;
            }

            $uploaded = $request->file($name);
            $path = $uploaded->store('form-uploads/'.$form->id, 'local');
            $absolutePath = Storage::disk('local')->path($path);
            $files[$name] = [
                'disk' => 'local',
                'path' => $path,
                'original_name' => $uploaded->getClientOriginalName(),
                'mime_type' => $uploaded->getClientMimeType(),
                'size' => $uploaded->getSize(),
                'sha256' => is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null,
            ];
        }

        return [$payload, $files];
    }
}
