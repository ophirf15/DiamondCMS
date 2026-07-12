<?php

declare(strict_types=1);

namespace App\Domains\Media\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

final class MediaManager
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    public function store(UploadedFile $file, ?int $userId = null, array $attributes = []): int
    {
        $mime = (string) $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('This file type is not allowed. SVG uploads are disabled until sanitization is configured.');
        }

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        $hash = hash('sha256', $contents);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $path = 'media/'.date('Y/m').'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $contents);

        $variants = [];
        if (str_starts_with($mime, 'image/')) {
            try {
                $variants = $this->createImageVariants($path);
            } catch (\Throwable) {
                $variants = [];
            }
        }

        return (int) DB::table('media_items')->insertGetId([
            'folder_id' => $attributes['folder_id'] ?? null,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'extension' => $extension,
            'size' => $file->getSize() ?: strlen($contents),
            'sha256' => $hash,
            'alt_text' => $attributes['alt_text'] ?? null,
            'caption' => $attributes['caption'] ?? null,
            'credit' => $attributes['credit'] ?? null,
            'metadata' => json_encode(['duplicate_count' => $this->duplicateCount($hash)], JSON_THROW_ON_ERROR),
            'variants' => json_encode($variants, JSON_THROW_ON_ERROR),
            'is_svg' => false,
            'uploaded_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function replace(int $mediaId, UploadedFile $file, ?int $userId = null): void
    {
        $existing = DB::table('media_items')->where('id', $mediaId)->first();
        if (! $existing) {
            throw new RuntimeException('Media item not found.');
        }

        $newId = $this->store($file, $userId);
        $replacement = DB::table('media_items')->where('id', $newId)->first();

        DB::table('media_items')->where('id', $mediaId)->update([
            'path' => $replacement->path,
            'original_name' => $replacement->original_name,
            'mime_type' => $replacement->mime_type,
            'extension' => $replacement->extension,
            'size' => $replacement->size,
            'sha256' => $replacement->sha256,
            'metadata' => $replacement->metadata,
            'variants' => $replacement->variants,
            'updated_at' => now(),
        ]);
        DB::table('media_items')->where('id', $newId)->delete();
    }

    public function assertSafeToDelete(int $mediaId, bool $force = false): void
    {
        $usageCount = DB::table('media_usages')->where('media_item_id', $mediaId)->count();
        if ($usageCount > 0 && ! $force) {
            throw new RuntimeException('Media item is in use and requires explicit force deletion.');
        }
    }

    public function restore(int $mediaId): void
    {
        DB::table('media_items')->where('id', $mediaId)->whereNotNull('deleted_at')->update([
            'deleted_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function forceDelete(int $mediaId): void
    {
        $item = DB::table('media_items')->where('id', $mediaId)->first();
        if (! $item) {
            return;
        }

        $disk = Storage::disk($item->disk ?: 'public');
        $paths = [$item->path];
        $variants = is_string($item->variants) ? json_decode($item->variants, true) : ($item->variants ?? []);
        if (is_array($variants)) {
            foreach ($variants as $variantPath) {
                if (is_string($variantPath) && $variantPath !== '') {
                    $paths[] = $variantPath;
                }
            }
        }

        foreach (array_unique($paths) as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        DB::table('media_usages')->where('media_item_id', $mediaId)->delete();
        DB::table('media_items')->where('id', $mediaId)->delete();
    }

    /** @return array<string, mixed> */
    public function payload(int $mediaId): array
    {
        $item = DB::table('media_items')->where('id', $mediaId)->firstOrFail();
        $row = (array) $item;
        $row['url'] = '/storage/'.ltrim((string) $item->path, '/');

        return $row;
    }

    /** @return array<string, string> */
    private function createImageVariants(string $path): array
    {
        $source = Storage::disk('public')->path($path);
        $manager = new ImageManager(new Driver);
        $variants = [];

        foreach ([320, 768, 1280] as $width) {
            $image = $manager->decodePath($source);
            if ($image->width() <= $width) {
                continue;
            }

            $variantPath = preg_replace('/\.[^.]+$/', "-{$width}.webp", $path) ?: $path.'.webp';
            $image->scale(width: $width)->save(Storage::disk('public')->path($variantPath), quality: 82);
            $variants[(string) $width] = $variantPath;
        }

        return $variants;
    }

    private function duplicateCount(string $hash): int
    {
        return DB::table('media_items')->where('sha256', $hash)->count();
    }
}
