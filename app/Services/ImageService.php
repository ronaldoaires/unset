<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;

class ImageService
{
    public function storeWithSlug(
        string $slug,
        mixed $file,
        string $directory = 'uploads',
        string $disk = 'public',
        ?string $oldPath = null
    ): string {
        // Delete the old image+thumb first — otherwise, on a replace, the
        // uniqueness check below finds the old file still sitting at
        // "{$slug}.webp" and bumps to "{$slug}-1.webp", "{$slug}-2.webp",
        // etc. on every single edit, piling up orphaned files.
        if ($oldPath) {
            $this->deleteImageAndThumb($oldPath, $disk);
        }

        $slug = Str::slug($slug, '-');
        $baseName = $slug;
        $filename = "{$baseName}.webp";
        $i = 1;

        while (Storage::disk($disk)->exists("{$directory}/{$filename}")) {
            $filename = "{$baseName}-{$i}.webp";
            $i++;
        }

        $path = "{$directory}/{$filename}";
        $encoded = Image::decode($file)->encode(new WebpEncoder(quality: 80));
        Storage::disk($disk)->put($path, $encoded);

        $this->generateThumb($path, $disk);

        return $path;
    }

    /**
     * Crops the image to a centered square, converts it to webp and stores it
     * as "{directory}/{baseName}-{random}.webp". The random suffix gives every
     * upload a new URL, so browsers never show a stale cached copy. The old
     * file is only removed after the new one is safely stored.
     */
    public function storeSquare(
        mixed $file,
        string $baseName,
        int $size = 256,
        string $directory = 'avatars',
        string $disk = 'public',
        ?string $oldPath = null
    ): string {
        $path = $directory . '/' . Str::slug($baseName) . '-' . Str::lower(Str::random(8)) . '.webp';

        $encoded = Image::decode($file)
            ->cover($size, $size)
            ->encode(new WebpEncoder(quality: 80));

        Storage::disk($disk)->put($path, $encoded);

        if ($oldPath) {
            $this->deleteImageAndThumb($oldPath, $disk);
        }

        return $path;
    }

    public function generateThumb(
        string $path,
        string $disk = 'public',
        int $width = 400,
        string $thumbDir = 'thumbs'
    ): void {
        $content = Storage::disk($disk)->get($path);
        $encoded = Image::decode($content)
            ->scale(width: $width)
            ->encode(new WebpEncoder(quality: 80));

        $thumbPath = dirname($path) . "/{$thumbDir}/" . basename($path);

        Storage::disk($disk)->makeDirectory(dirname($path) . "/{$thumbDir}");
        Storage::disk($disk)->put($thumbPath, $encoded);
    }

    public function deleteImageAndThumb(
        string $path,
        string $disk = 'public',
        string $thumbDir = 'thumbs'
    ): void {
        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            $storage->delete($path);
        }

        $thumbPath = dirname($path) . "/{$thumbDir}/" . basename($path);

        if ($storage->exists($thumbPath)) {
            $storage->delete($thumbPath);
        }
    }
}
