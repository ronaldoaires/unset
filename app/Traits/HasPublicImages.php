<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * URL helpers for images stored on the public disk by ImageService,
 * where the thumbnail sits in a "thumbs" folder next to the original.
 */
trait HasPublicImages
{
    protected function publicImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');

        // The last-modified timestamp makes browsers fetch the new file
        // when an image is replaced under the same filename.
        return $disk->exists($path)
            ? $disk->url($path) . '?v=' . $disk->lastModified($path)
            : $disk->url($path);
    }

    protected function publicThumbUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $thumbPath = dirname($path) . '/thumbs/' . basename($path);

        return Storage::disk('public')->exists($thumbPath)
            ? $this->publicImageUrl($thumbPath)
            : $this->publicImageUrl($path);
    }
}
