<?php

declare(strict_types=1);

namespace App\Domains\Mail\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class MailSettingsManager
{
    public function save(array $data, ?int $userId = null): int
    {
        $existing = DB::table('mail_settings')->where('is_active', true)->first();

        if (! empty($data['password'])) {
            $data['encrypted_password'] = Crypt::encryptString((string) $data['password']);
        }

        $payload = [
            'mailer' => $data['mailer'] ?? 'smtp',
            'host' => $data['host'],
            'port' => $data['port'] ?? 587,
            'username' => $data['username'] ?? null,
            'encrypted_password' => $data['encrypted_password'] ?? $existing?->encrypted_password,
            'encryption' => $data['encryption'] ?? 'tls',
            'from_address' => $data['from_address'],
            'from_name' => $data['from_name'] ?? config('app.name'),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'updated_by' => $userId,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('mail_settings')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        $payload['created_at'] = now();

        return (int) DB::table('mail_settings')->insertGetId($payload);
    }

    public function publicConfig(): array
    {
        $settings = DB::table('mail_settings')->where('is_active', true)->first();

        return $settings ? [
            'mailer' => $settings->mailer,
            'host' => $settings->host,
            'port' => $settings->port,
            'username' => $settings->username,
            'encryption' => $settings->encryption,
            'from_address' => $settings->from_address,
            'from_name' => $settings->from_name,
        ] : [];
    }

    public function testSend(string $recipient): int
    {
        $this->applyActiveSettings();

        Mail::raw('DiamondCMS test email sent at '.now()->toIso8601String(), function ($message) use ($recipient): void {
            $message->to($recipient)->subject('DiamondCMS SMTP test');
        });

        return (int) DB::table('email_delivery_logs')->insertGetId([
            'template_key' => 'smtp_test',
            'recipient_hash' => hash('sha256', Str::lower($recipient)),
            'status' => 'sent',
            'message' => 'SMTP test accepted by configured mailer.',
            'context' => json_encode(['sent_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function applyActiveSettings(): void
    {
        $settings = DB::table('mail_settings')->where('is_active', true)->first();
        if (! $settings) {
            return;
        }

        config([
            'mail.default' => $settings->mailer,
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $settings->encrypted_password ? Crypt::decryptString($settings->encrypted_password) : null,
            'mail.mailers.smtp.encryption' => $settings->encryption,
            'mail.from.address' => $settings->from_address,
            'mail.from.name' => $settings->from_name,
        ]);
    }
}
