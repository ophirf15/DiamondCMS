<?php

declare(strict_types=1);

namespace App\Domains\Forms\Support;

use App\Domains\Activity\Support\ActivityLogger;
use App\Domains\Mail\Support\MailSettingsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

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

    public function updateForm(int $formId, array $data, ?int $userId = null): object
    {
        $existing = DB::table('forms')->where('id', $formId)->whereNull('deleted_at')->first();
        abort_unless($existing, 404);

        $payload = ['updated_at' => now()];

        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }
        if (array_key_exists('slug', $data)) {
            $payload['slug'] = Str::slug((string) $data['slug']);
        }
        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'];
        }
        if (array_key_exists('schema', $data)) {
            $payload['schema'] = json_encode($this->normalizeSchema($data['schema'] ?? []), JSON_THROW_ON_ERROR);
        }
        if (array_key_exists('notifications', $data)) {
            $payload['notifications'] = json_encode($data['notifications'] ?? [], JSON_THROW_ON_ERROR);
        }
        if (array_key_exists('spam_config', $data)) {
            $payload['spam_config'] = json_encode($data['spam_config'] ?? [], JSON_THROW_ON_ERROR);
        }
        if (array_key_exists('success_message', $data)) {
            $payload['success_message'] = $data['success_message'];
        }
        if (array_key_exists('redirect_url', $data)) {
            $payload['redirect_url'] = $data['redirect_url'];
        }
        if (array_key_exists('retention_days', $data)) {
            $payload['retention_days'] = $data['retention_days'];
        }

        DB::table('forms')->where('id', $formId)->update($payload);
        ActivityLogger::log('forms.updated', (object) ['id' => $formId], ['form_id' => $formId], request());

        return $this->find($formId);
    }

    public function find(int $formId): object
    {
        $form = DB::table('forms')->where('id', $formId)->whereNull('deleted_at')->first();
        abort_unless($form, 404);

        return $this->decodeForm($form);
    }

    /** @return Collection<int, object> */
    public function list(): Collection
    {
        return DB::table('forms')
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (object $form): object {
                $decoded = $this->decodeForm($form);
                $decoded->submissions_count = DB::table('form_submissions')
                    ->where('form_id', $form->id)
                    ->whereNull('deleted_at')
                    ->count();

                return $decoded;
            });
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
        $submissionId = $this->storeSubmission($form, $values, $request, false, $files);
        $this->notifyRecipients($form, $values);

        return $submissionId;
    }

    /** @return Collection<int, object> */
    public function submissions(int $formId): Collection
    {
        return DB::table('form_submissions')->where('form_id', $formId)->whereNull('deleted_at')->latest()->get()->map(function (object $row): object {
            $row->payload = json_decode((string) $row->payload, true) ?: [];
            $row->files = json_decode((string) $row->files, true) ?: [];

            return $row;
        });
    }

    public function csv(int $formId): string
    {
        $rows = DB::table('form_submissions')->where('form_id', $formId)->whereNull('deleted_at')->latest()->get();
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

    public function renderEmbed(string $slug): string
    {
        if ($slug === '') {
            return '<p class="dc-text">Choose a published form for this block.</p>';
        }

        try {
            $form = $this->publicForm($slug);
        } catch (Throwable) {
            return '<p class="dc-text">Form “'.e($slug).'” is not published yet.</p>';
        }

        return view('public.forms.embed', ['form' => $form])->render();
    }

    public function ensureContactForm(?int $userId = null): object
    {
        $existing = DB::table('forms')->where('slug', 'contact')->whereNull('deleted_at')->first();
        if ($existing) {
            return $this->decodeForm($existing);
        }

        $id = $this->createForm([
            'name' => 'Contact',
            'slug' => 'contact',
            'status' => 'published',
            'schema' => [
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
                ],
            ],
            'notifications' => [
                'recipients' => array_values(array_filter([
                    optional(DB::table('users')->where('is_admin', true)->orderBy('id')->first())->email,
                ])),
            ],
            'success_message' => 'Thanks — your message is on its way.',
        ], $userId);

        return $this->find($id);
    }

    private function notifyRecipients(object $form, array $values): void
    {
        $recipients = collect($form->notifications['recipients'] ?? [])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            app(MailSettingsManager::class)->applyActiveSettings();
            $lines = collect($values)->map(fn ($value, $key) => $key.': '.(is_scalar($value) ? (string) $value : json_encode($value)))->implode("\n");
            $body = "New submission for {$form->name}\n\n".$lines;

            foreach ($recipients as $recipient) {
                Mail::raw($body, function ($message) use ($recipient, $form): void {
                    $message->to($recipient)->subject('['.diamondcms_site_name().'] '.$form->name.' submission');
                });

                DB::table('email_delivery_logs')->insert([
                    'template_key' => 'form_notification',
                    'recipient_hash' => hash('sha256', Str::lower((string) $recipient)),
                    'status' => 'sent',
                    'message' => 'Form notification accepted by configured mailer.',
                    'context' => json_encode(['form_id' => $form->id, 'form_slug' => $form->slug], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            ActivityLogger::log('forms.notification_failed', (object) ['id' => $form->id], [
                'error' => $e->getMessage(),
            ], request());
        }
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
