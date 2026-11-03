<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Keeps the public disk free of orphaned images uploaded through a
 * Filament RichEditor. The editor stores each image as
 * <img data-id="{path on disk}" ...>, so the paths can be read back from
 * the saved HTML: images removed from the text are deleted on save, and
 * every remaining one is deleted together with the record.
 */
trait CleansRichContentImages
{
    public static function bootCleansRichContentImages(): void
    {
        static::updated(function (self $model) {
            foreach ($model->richContentImageAttributes() as $attribute) {
                if (! $model->wasChanged($attribute)) {
                    continue;
                }

                $before = static::richContentImagePaths($model->getOriginal($attribute));
                $after  = static::richContentImagePaths($model->getAttribute($attribute));

                static::deleteRichContentImages(array_diff($before, $after));
            }
        });

        static::deleted(function (self $model) {
            foreach ($model->richContentImageAttributes() as $attribute) {
                static::deleteRichContentImages(
                    static::richContentImagePaths($model->getAttribute($attribute))
                );
            }
        });
    }

    /**
     * Attributes that hold rich text. Override in the model when needed.
     *
     * @return array<int, string>
     */
    public function richContentImageAttributes(): array
    {
        return ['body'];
    }

    /**
     * @return array<int, string>
     */
    protected static function richContentImagePaths(?string $html): array
    {
        if (blank($html)) {
            return [];
        }

        preg_match_all('/<img[^>]+data-id="([^"]+)"/i', $html, $matches);

        return array_values(array_unique(array_map('html_entity_decode', $matches[1])));
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected static function deleteRichContentImages(array $paths): void
    {
        $disk = Storage::disk('public');

        foreach ($paths as $path) {
            // Never follow a path out of the content folders.
            if (str_contains($path, '..') || ! str_contains($path, '/content/')) {
                continue;
            }

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
