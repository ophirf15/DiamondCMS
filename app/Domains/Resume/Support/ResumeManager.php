<?php

declare(strict_types=1);

namespace App\Domains\Resume\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class ResumeManager
{
    public function createProfile(array $data, ?int $userId = null): int
    {
        return (int) DB::table('resume_profiles')->insertGetId([
            'user_id' => $userId,
            'name' => $data['name'],
            'headline' => $data['headline'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'location' => $data['location'] ?? null,
            'website' => $data['website'] ?? null,
            'summary' => $data['summary'] ?? null,
            'links' => json_encode($data['links'] ?? [], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createVariant(int $profileId, array $data): int
    {
        return (int) DB::table('resume_variants')->insertGetId([
            'resume_profile_id' => $profileId,
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'] ?? $data['name']).'-'.Str::lower(Str::random(6)),
            'visibility' => $data['visibility'] ?? 'private',
            'summary_override' => $data['summary_override'] ?? null,
            'section_order' => json_encode($data['section_order'] ?? [], JSON_THROW_ON_ERROR),
            'hidden_sections' => json_encode($data['hidden_sections'] ?? [], JSON_THROW_ON_ERROR),
            'skill_overrides' => json_encode($data['skill_overrides'] ?? [], JSON_THROW_ON_ERROR),
            'builder_json' => json_encode($data['builder_json'] ?? null, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createImport(UploadedFile $file, ?int $profileId, ?int $mediaId, ?int $userId): int
    {
        $text = $this->extractText($file);
        $payload = $this->bestEffortParse($text);

        return (int) DB::table('resume_imports')->insertGetId([
            'resume_profile_id' => $profileId,
            'media_item_id' => $mediaId,
            'extracted_text' => $text,
            'parsed_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'needs_review',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function approveImport(int $importId): int
    {
        $import = DB::table('resume_imports')->where('id', $importId)->first();
        if (! $import || $import->status !== 'needs_review') {
            throw new RuntimeException('Resume import is not ready for review.');
        }

        $payload = json_decode((string) $import->parsed_payload, true) ?: [];
        $profileId = $import->resume_profile_id ?: $this->createProfile([
            'name' => $payload['name'] ?: 'Imported resume',
            'headline' => $payload['headline'] ?? null,
            'summary' => $payload['summary'] ?? null,
        ], $import->created_by);

        foreach (($payload['sections'] ?? []) as $index => $section) {
            DB::table('resume_sections')->insert([
                'resume_profile_id' => $profileId,
                'type' => $section['type'] ?? 'experience',
                'title' => $section['title'] ?? null,
                'organization' => $section['organization'] ?? null,
                'bullets' => json_encode($section['bullets'] ?? [], JSON_THROW_ON_ERROR),
                'metadata' => json_encode($section, JSON_THROW_ON_ERROR),
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('resume_imports')->where('id', $importId)->update([
            'resume_profile_id' => $profileId,
            'status' => 'approved',
            'updated_at' => now(),
        ]);

        return $profileId;
    }

    public function renderHtml(int $variantId): string
    {
        $variant = DB::table('resume_variants')->where('id', $variantId)->first();
        abort_unless($variant, 404);
        $profile = DB::table('resume_profiles')->where('id', $variant->resume_profile_id)->first();
        $sections = DB::table('resume_sections')->where('resume_profile_id', $profile->id)->orderBy('sort_order')->get();

        return view('public.resume-print', compact('profile', 'variant', 'sections'))->render();
    }

    public function pdfResponse(int $variantId)
    {
        $html = $this->renderHtml($variantId);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="resume-'.$variantId.'.html"',
            'X-DiamondCMS-PDF-Mode' => 'browser-print',
        ]);
    }

    private function extractText(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false) {
            return '';
        }

        if ($extension === 'txt') {
            return trim($raw);
        }

        if ($extension === 'docx') {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) === true) {
                $xml = $zip->getFromName('word/document.xml') ?: '';
                $zip->close();

                return trim(html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $xml))));
            }
        }

        if ($extension === 'pdf') {
            return trim(preg_replace('/[^\PC\s]/u', '', $raw) ?: '');
        }

        return '';
    }

    /** @return array<string, mixed> */
    private function bestEffortParse(string $text): array
    {
        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values();

        return [
            'name' => $lines->first() ?: 'Imported resume',
            'headline' => $lines->get(1),
            'summary' => $lines->slice(2, 3)->implode(' '),
            'sections' => [
                [
                    'type' => 'experience',
                    'title' => 'Imported experience',
                    'bullets' => $lines->slice(5, 6)->values()->all(),
                ],
            ],
            'review_required' => true,
        ];
    }
}
