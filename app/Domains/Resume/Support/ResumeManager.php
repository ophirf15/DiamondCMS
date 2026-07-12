<?php

declare(strict_types=1);

namespace App\Domains\Resume\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

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

    /** @param array<string, mixed> $payload */
    public function updateImportPayload(int $importId, array $payload): object
    {
        $import = DB::table('resume_imports')->where('id', $importId)->first();
        if (! $import || $import->status !== 'needs_review') {
            throw new RuntimeException('Resume import is not ready for review.');
        }

        $payload['review_required'] = true;
        DB::table('resume_imports')->where('id', $importId)->update([
            'parsed_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        return DB::table('resume_imports')->where('id', $importId)->firstOrFail();
    }

    public function approveImport(int $importId): int
    {
        $import = DB::table('resume_imports')->where('id', $importId)->first();
        if (! $import || $import->status !== 'needs_review') {
            throw new RuntimeException('Resume import is not ready for review.');
        }

        $payload = json_decode((string) $import->parsed_payload, true) ?: [];
        $profileId = $import->resume_profile_id ? (int) $import->resume_profile_id : null;

        if ($profileId) {
            DB::table('resume_profiles')->where('id', $profileId)->update([
                'name' => $payload['name'] ?: 'Imported resume',
                'headline' => $payload['headline'] ?? null,
                'summary' => $payload['summary'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'location' => $payload['location'] ?? null,
                'updated_at' => now(),
            ]);
            DB::table('resume_sections')->where('resume_profile_id', $profileId)->delete();
        } else {
            $profileId = $this->createProfile([
                'name' => $payload['name'] ?: 'Imported resume',
                'headline' => $payload['headline'] ?? null,
                'summary' => $payload['summary'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'location' => $payload['location'] ?? null,
            ], $import->created_by);
        }

        foreach (($payload['sections'] ?? []) as $index => $section) {
            DB::table('resume_sections')->insert([
                'resume_profile_id' => $profileId,
                'type' => $section['type'] ?? 'experience',
                'title' => $section['title'] ?? null,
                'organization' => $section['organization'] ?? null,
                'bullets' => json_encode($section['bullets'] ?? [], JSON_THROW_ON_ERROR),
                'metadata' => json_encode([
                    'date' => $section['date'] ?? null,
                    'raw' => $section,
                ], JSON_THROW_ON_ERROR),
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

    public function primaryPublicProfile(): ?object
    {
        return DB::table('resume_profiles')->orderBy('id')->first();
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    public function primaryExperienceItems()
    {
        $profile = $this->primaryPublicProfile();
        if (! $profile) {
            return collect();
        }

        return DB::table('resume_sections')
            ->where('resume_profile_id', $profile->id)
            ->whereIn('type', ['experience', 'work', 'employment'])
            ->orderBy('sort_order')
            ->get()
            ->map(function (object $section): object {
                $bullets = json_decode((string) $section->bullets, true) ?: [];
                $section->body = is_array($bullets) ? implode(' ', $bullets) : (string) $section->title;

                return $section;
            });
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
        $path = $file->getRealPath();
        if ($path === false) {
            return '';
        }

        if ($extension === 'txt') {
            $raw = file_get_contents($path);

            return trim($raw === false ? '' : $raw);
        }

        if ($extension === 'docx') {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml') ?: '';
                $zip->close();

                return trim(html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $xml))));
            }
        }

        if ($extension === 'pdf') {
            try {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($path);

                return trim($pdf->getText());
            } catch (Throwable) {
                $raw = file_get_contents($path);

                return trim(preg_replace('/[^\PC\s]/u', '', $raw === false ? '' : $raw) ?: '');
            }
        }

        return '';
    }

    /** @return array<string, mixed> */
    public function bestEffortParse(string $text): array
    {
        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(fn (string $line) => trim(preg_replace('/\s+/', ' ', $line) ?? ''))
            ->filter()
            ->values();

        $email = null;
        $phone = null;
        foreach ($lines as $line) {
            if (! $email && preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $line, $match)) {
                $email = $match[0];
            }
            if (! $phone && preg_match('/(?:\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $line, $match)) {
                $phone = $match[0];
            }
        }

        $name = $lines->first(fn (string $line) => ! str_contains($line, '@') && ! preg_match('/^\d/', $line)) ?: 'Imported resume';
        $headline = $lines->slice(1, 3)->first(fn (string $line) => ! str_contains(strtolower($line), 'experience') && strlen($line) < 120);

        $sections = $this->parseSections($lines->all());

        $summaryLines = [];
        foreach ($lines->slice(1, 8) as $line) {
            if ($this->isSectionHeading($line)) {
                break;
            }
            if ($line === $headline || str_contains($line, '@')) {
                continue;
            }
            $summaryLines[] = $line;
            if (count($summaryLines) >= 3) {
                break;
            }
        }

        return [
            'name' => $name,
            'headline' => $headline,
            'summary' => implode(' ', $summaryLines),
            'email' => $email,
            'phone' => $phone,
            'sections' => $sections,
            'review_required' => true,
        ];
    }

    /**
     * @param array<int, string> $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseSections(array $lines): array
    {
        $sections = [];
        $currentType = null;
        $buffer = [];

        $flush = function () use (&$sections, &$currentType, &$buffer): void {
            if ($currentType === null || $buffer === []) {
                $buffer = [];

                return;
            }

            if ($currentType === 'skills') {
                $sections[] = [
                    'type' => 'skills',
                    'title' => 'Skills',
                    'organization' => null,
                    'date' => null,
                    'bullets' => array_values(array_filter($buffer)),
                ];
            } elseif ($currentType === 'education') {
                foreach ($this->chunkEntries($buffer) as $entry) {
                    $sections[] = $entry + ['type' => 'education'];
                }
            } else {
                foreach ($this->chunkEntries($buffer) as $entry) {
                    $sections[] = $entry + ['type' => $currentType];
                }
            }

            $buffer = [];
        };

        foreach ($lines as $line) {
            $heading = $this->mapSectionHeading($line);
            if ($heading !== null) {
                $flush();
                $currentType = $heading;
                continue;
            }

            if ($currentType === null) {
                continue;
            }

            $buffer[] = $line;
        }

        $flush();

        if ($sections === []) {
            $sections[] = [
                'type' => 'experience',
                'title' => 'Imported experience',
                'organization' => null,
                'date' => null,
                'bullets' => array_slice($lines, 5, 8),
            ];
        }

        return $sections;
    }

    private function isSectionHeading(string $line): bool
    {
        return $this->mapSectionHeading($line) !== null;
    }

    private function mapSectionHeading(string $line): ?string
    {
        $normalized = strtolower(trim($line, " \t:-"));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return match (true) {
            in_array($normalized, ['experience', 'work experience', 'employment', 'professional experience'], true) => 'experience',
            in_array($normalized, ['education', 'academic', 'schooling'], true) => 'education',
            in_array($normalized, ['skills', 'skill', 'technical skills', 'core skills', 'abilities'], true) => 'skills',
            in_array($normalized, ['projects', 'project', 'selected work', 'portfolio'], true) => 'project',
            in_array($normalized, ['awards', 'award', 'honors'], true) => 'award',
            default => null,
        };
    }

    /**
     * @param array<int, string> $lines
     * @return array<int, array<string, mixed>>
     */
    private function chunkEntries(array $lines): array
    {
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $isDate = (bool) preg_match('/\b(19|20)\d{2}\b/', $line) && (
                preg_match('/(-|–|—|to|current|present)/i', $line)
                || preg_match('/^(january|february|march|april|may|june|july|august|september|october|november|december)\b/i', $line)
            );
            $isBullet = str_starts_with($line, '•') || str_starts_with($line, '-') || str_starts_with($line, '*');

            if ($isDate) {
                if ($current) {
                    $entries[] = $current;
                }
                $current = [
                    'title' => null,
                    'organization' => null,
                    'date' => $line,
                    'bullets' => [],
                ];
                continue;
            }

            if ($current === null) {
                $current = [
                    'title' => $line,
                    'organization' => null,
                    'date' => null,
                    'bullets' => [],
                ];
                continue;
            }

            if ($isBullet) {
                $current['bullets'][] = ltrim($line, "•-* \t");
                continue;
            }

            if ($current['title'] === null) {
                $current['title'] = $line;
            } elseif ($current['organization'] === null && strlen($line) < 120) {
                $current['organization'] = $line;
            } else {
                $current['bullets'][] = $line;
            }
        }

        if ($current) {
            $entries[] = $current;
        }

        return array_map(function (array $entry): array {
            if (! $entry['title'] && $entry['organization']) {
                $entry['title'] = $entry['organization'];
                $entry['organization'] = null;
            }

            return $entry;
        }, $entries);
    }
}
