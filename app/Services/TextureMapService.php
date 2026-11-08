<?php

namespace App\Services;

use App\Models\Texture;
use App\Models\TextureMap;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

/**
 * Stores texture maps and keeps the derived data (version, shading color,
 * auto preview) in step with them. Used by the Filament pages and the maps
 * relation manager, so every upload path applies the same rules.
 *
 * Layout on the private disk: textures/{slug}/{resolution}/{type}.{ext}
 */
class TextureMapService
{
    /** Directory of covers uploaded by an admin (never overwritten automatically). */
    public const COVER_DIR = 'textures/covers';

    /** Directory of covers generated from the diffuse map. */
    public const AUTO_PREVIEW_DIR = 'textures/previews';

    /** Accepted map formats: what Revit's UnifiedBitmap reads reliably. */
    public const MIME_TYPES = ['image/png', 'image/jpeg'];

    /** Longest side used to build the preview and the shading color. */
    private const PREVIEW_SIZE = 1024;

    public function __construct(private ImageService $images) {}

    /**
     * Stores several files at once, guessing each map type from its file name
     * and the resolution from its pixel size. A file replaces the existing map
     * of the same type and resolution.
     *
     * @param  array<UploadedFile>  $files
     * @return array{stored: array<string>, skipped: array<string>}
     */
    public function storeMany(Texture $texture, array $files): array
    {
        $stored  = [];
        $skipped = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $type = TextureMap::guessType($file->getClientOriginalName());

            if ($type === null) {
                $skipped[] = $file->getClientOriginalName();
                continue;
            }

            $map      = $this->store($texture, $file, $type, refresh: false);
            $stored[] = $map->type_label . ' ' . strtoupper($map->resolution);
        }

        if ($stored) {
            $this->afterMapsChanged($texture);
        }

        return ['stored' => $stored, 'skipped' => $skipped];
    }

    /** Stores one file as the given map type. */
    public function store(Texture $texture, UploadedFile $file, string $type, bool $refresh = true): TextureMap
    {
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        $resolution = TextureMap::resolutionFor(max((int) $width, (int) $height));
        $extension  = strtolower($file->getClientOriginalExtension()) === 'png' ? 'png' : 'jpg';
        $path       = "textures/{$texture->slug}/{$resolution}/{$type}.{$extension}";

        $map = DB::transaction(function () use ($texture, $file, $type, $resolution, $extension, $path, $width, $height) {
            // Deleting the old record also removes its file (TextureMap hook),
            // which frees the path when the extension is the same.
            $texture->maps()
                ->where('type', $type)
                ->where('resolution', $resolution)
                ->get()
                ->each->delete();

            Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

            return $texture->maps()->create([
                'type'          => $type,
                'resolution'    => $resolution,
                'disk'          => 'local',
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'width_px'      => $width,
                'height_px'     => $height,
                'size_bytes'    => Storage::disk('local')->size($path),
                'mime_type'     => $extension === 'png' ? 'image/png' : 'image/jpeg',
            ]);
        });

        if ($refresh) {
            $this->afterMapsChanged($texture);
        }

        return $map;
    }

    public function delete(TextureMap $map): void
    {
        $texture = $map->texture;
        $map->delete();

        $this->afterMapsChanged($texture);
    }

    /**
     * Called after any change to the maps: clients must re-download, and the
     * shading color and auto preview follow the current diffuse map.
     */
    public function afterMapsChanged(Texture $texture): void
    {
        // Cached ZIPs belong to the old version.
        TextureDownloadService::forget($texture);

        // A texture still being created has no client copies to invalidate.
        if (! $texture->wasRecentlyCreated) {
            $texture->bumpVersion();
        }

        $this->refreshFromDiffuse($texture);
    }

    /**
     * Rebuilds the shading color and, unless an admin uploaded a cover, the
     * preview from the smallest diffuse map (the cheapest one to decode).
     * Failures are logged and never block the upload itself.
     */
    public function refreshFromDiffuse(Texture $texture): void
    {
        $diffuse = $texture->maps()
            ->where('type', TextureMap::TYPE_DIFFUSE)
            ->get()
            ->sortBy(fn(TextureMap $map) => array_search($map->resolution, TextureMap::RESOLUTIONS, true))
            ->first();

        if (! $diffuse) {
            $texture->update(['shading_color' => null]);
            $this->deleteAutoPreview($texture);

            return;
        }

        try {
            $preview = Image::decodePath(Storage::disk($diffuse->disk)->path($diffuse->path))
                ->scaleDown(width: self::PREVIEW_SIZE, height: self::PREVIEW_SIZE)
                ->encode(new WebpEncoder(quality: 82))
                ->toString();

            $texture->update(['shading_color' => $this->averageColor($preview)]);

            if (! $this->hasCustomCover($texture)) {
                $this->storeAutoPreview($texture, $preview);
            }
        } catch (Throwable $e) {
            Log::warning('Texture preview/shading color failed', [
                'texture' => $texture->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Stores a cover uploaded by an admin, replacing any previous cover.
     * Passing null removes the custom cover and goes back to the auto preview.
     */
    public function storeCover(Texture $texture, ?UploadedFile $file): void
    {
        $old = $texture->preview_path;

        if ($file === null) {
            if ($this->hasCustomCover($texture)) {
                $this->images->deleteImageAndThumb($old, 'public');
                $texture->update(['preview_path' => null]);
                $this->refreshFromDiffuse($texture);
            }

            return;
        }

        $path = $this->images->storeWithSlug(
            slug: $texture->slug,
            file: $file,
            directory: self::COVER_DIR,
            disk: 'public',
            oldPath: $old,
        );

        $texture->update(['preview_path' => $path]);
    }

    public function hasCustomCover(Texture $texture): bool
    {
        return filled($texture->preview_path)
            && str_starts_with($texture->preview_path, self::COVER_DIR . '/');
    }

    private function storeAutoPreview(Texture $texture, string $webp): void
    {
        $path = self::AUTO_PREVIEW_DIR . '/' . $texture->slug . '.webp';

        Storage::disk('public')->put($path, $webp);
        $this->images->generateThumb($path, 'public');

        if ($texture->preview_path !== $path) {
            $texture->update(['preview_path' => $path]);
        }
    }

    private function deleteAutoPreview(Texture $texture): void
    {
        if ($texture->preview_path && ! $this->hasCustomCover($texture)) {
            $this->images->deleteImageAndThumb($texture->preview_path, 'public');
            $texture->update(['preview_path' => null]);
        }
    }

    /** Average color of an image as "#rrggbb", by resampling it to one pixel. */
    private function averageColor(string $binary): string
    {
        $color = Image::decodeBinary($binary)
            ->resize(1, 1)
            ->colorAt(0, 0)
            ->toColorspace(RgbColorspace::class);

        // Built by hand: toHex() appends the alpha channel for translucent
        // pixels, and the column holds "#rrggbb" only.
        return sprintf(
            '#%02x%02x%02x',
            $color->red()->value(),
            $color->green()->value(),
            $color->blue()->value(),
        );
    }
}
