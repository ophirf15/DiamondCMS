<?php

declare(strict_types=1);

namespace App\Domains\Resume\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
            'email' => self::nullableString($data['email'] ?? null),
            'phone' => self::nullableString($data['phone'] ?? null),
            'location' => self::nullableString($data['location'] ?? null),
            'website' => self::normalizeWebsite($data['website'] ?? null),
            'summary' => $data['summary'] ?? null,
            'links' => json_encode(self::normalizeLinks($data['links'] ?? []), JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(int $profileId, array $data): object
    {
        $payload = ['updated_at' => now()];
        foreach (['name', 'headline', 'summary'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }
        foreach (['email', 'phone', 'location'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = self::nullableString($data[$key]);
            }
        }
        if (array_key_exists('website', $data)) {
            $payload['website'] = self::normalizeWebsite($data['website']);
        }
        if (array_key_exists('links', $data)) {
            $payload['links'] = json_encode(self::normalizeLinks($data['links']), JSON_THROW_ON_ERROR);
        }

        DB::table('resume_profiles')->where('id', $profileId)->update($payload);

        return DB::table('resume_profiles')->where('id', $profileId)->firstOrFail();
    }

    public static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    public static function normalizeWebsite(mixed $value): ?string
    {
        $text = self::nullableString($value);
        if ($text === null) {
            return null;
        }
        if (! preg_match('#^https?://#i', $text)) {
            $text = 'https://'.$text;
        }

        return $text;
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public static function normalizeLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        $normalized = [];
        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }
            $label = self::nullableString($link['label'] ?? null) ?? '';
            $url = self::normalizeWebsite($link['url'] ?? null);
            if ($url === null) {
                continue;
            }
            if ($label === '') {
                $host = parse_url($url, PHP_URL_HOST) ?: $url;
                $label = is_string($host) ? preg_replace('#^www\.#i', '', $host) ?? $host : $url;
            }
            $normalized[] = ['label' => $label, 'url' => $url];
        }

        return $normalized;
    }

    /** @return list<array{label: string, url: string}> */
    public static function profileLinks(object $profile): array
    {
        $raw = $profile->links ?? null;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        return self::normalizeLinks($raw);
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
            'download_pdf' => $data['download_pdf'] ?? null,
            'download_docx' => $data['download_docx'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function deleteProfile(int $profileId): void
    {
        DB::table('resume_profiles')->where('id', $profileId)->delete();
    }

    public function deleteVariant(int $variantId): void
    {
        DB::table('resume_variants')->where('id', $variantId)->delete();
    }

    /** @param Collection<int, object>|iterable<int, object> $sections */
    public static function groupSections(iterable $sections): array
    {
        $grouped = [];
        foreach ($sections as $section) {
            $type = (string) ($section->type ?? 'other');
            if ($type === 'work' || $type === 'employment') {
                $type = 'experience';
            }
            $grouped[$type] ??= [];
            $grouped[$type][] = $section;
        }

        return $grouped;
    }

    public static function sectionTypeLabel(string $type): string
    {
        return match ($type) {
            'experience', 'work', 'employment' => 'Experience',
            'education' => 'Education',
            'skills' => 'Skills',
            'project' => 'Projects',
            'award' => 'Awards',
            'certification' => 'Certifications',
            default => 'Additional',
        };
    }

    public function downloadFileResponse(object $variant, string $format)
    {
        $path = match ($format) {
            'pdf' => (string) ($variant->download_pdf ?? ''),
            'docx' => (string) ($variant->download_docx ?? ''),
            default => '',
        };
        abort_if($path === '', 404);

        $absolute = public_path(ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/'));
        if (str_starts_with($path, '/storage/')) {
            $absolute = storage_path('app/public/'.ltrim(substr($path, strlen('/storage/')), '/'));
        } elseif (str_starts_with($path, 'storage/')) {
            $absolute = storage_path('app/public/'.ltrim(substr($path, strlen('storage/')), '/'));
        }

        if (! is_file($absolute)) {
            // Fall back to redirect for absolute/external URLs or public paths Vite/media serve.
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
                return redirect()->away($path);
            }
            abort(404);
        }

        $filename = basename($absolute);
        $mime = $format === 'docx'
            ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            : 'application/pdf';

        return response()->download($absolute, $filename, [
            'Content-Type' => $mime,
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

    /** @return Collection<int, \stdClass> */
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
            ->map(function (\stdClass $section): \stdClass {
                $bullets = json_decode((string) $section->bullets, true) ?: [];
                $section->body = is_array($bullets) ? implode(' ', $bullets) : (string) $section->title;

                return $section;
            });
    }

    public function renderHtml(int $variantId): string
    {
        $variant = DB::table('resume_variants')->where('id', $variantId)->first();
        abort_unless($variant !== null, 404);
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
            $zip = new \ZipArchive;
            if ($zip->open($path) === true) {
                $xml = $zip->getFromName('word/document.xml') ?: '';
                $zip->close();

                return trim(html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $xml))));
            }
        }

        if ($extension === 'pdf') {
            try {
                $parser = new PdfParser;
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
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseSections(array $lines): array
    {
        $sections = [];
        $currentType = null;
        $buffer = [];

        foreach ($lines as $line) {
            $heading = $this->mapSectionHeading($line);
            if ($heading !== null) {
                $this->flushParsedSection($sections, $currentType, $buffer);
                $currentType = $heading;

                continue;
            }

            if ($currentType === null) {
                continue;
            }

            $buffer[] = $line;
        }

        $this->flushParsedSection($sections, $currentType, $buffer);

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

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<int, string>  $buffer
     */
    private function flushParsedSection(array &$sections, ?string &$currentType, array &$buffer): void
    {
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
            in_array($normalized, ['certifications', 'certification', 'certificates', 'licenses', 'licence', 'licences'], true) => 'certification',
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $lines
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
