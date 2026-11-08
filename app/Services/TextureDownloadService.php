<?php

namespace App\Services;

use App\Models\Texture;
use App\Models\TextureDownload;
use App\Models\TextureMap;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Builds and serves texture packages: one ZIP per texture, version and
 * resolution, with the maps at the root ({type}.{ext}) plus texture.json.
 *
 * Packages are cached on the private disk and rebuilt only when missing, so
 * the shared host zips each package once. Any change to the maps bumps the
 * version and drops the cached packages (see forget()).
 */
class TextureDownloadService
{
    /** Cached packages: texture-packages/{slug}/v{version}-{resolution}.zip */
    public const PACKAGE_DIR = 'texture-packages';

    /** Resolution served when the client does not ask for one. */
    public const DEFAULT_RESOLUTION = '2k';

    /**
     * Checks the rules, records the download and streams the package.
     * Aborts with 404 (unpublished, no maps) or 403 (policy denial).
     */
    public function download(User $user, Texture $texture, ?string $requested, ?string $ip): StreamedResponse
    {
        abort_unless($texture->isPublished(), 404);

        $user->loadMissing('activeSubscription.plan');
        Gate::forUser($user)->authorize('download', $texture);

        $resolution = $this->pickResolution($texture, $requested);
        abort_if($resolution === null, 404);

        $path = $this->package($texture, $resolution);

        DB::transaction(function () use ($user, $texture, $resolution, $ip) {
            // The public counter shows how many people use the texture, so a
            // user's later versions/resolutions don't count again.
            $firstTime = ! $texture->downloads()->where('user_id', $user->id)->exists();

            TextureDownload::create([
                'user_id'       => $user->id,
                'texture_id'    => $texture->id,
                'resolution'    => $resolution,
                'version'       => $texture->version,
                'ip_address'    => $ip,
                'downloaded_at' => now(),
            ]);

            if ($firstTime) {
                $texture->incrementDownloads();
            }
        });

        return Storage::disk('local')->download(
            $path,
            "{$texture->slug}-{$resolution}.zip",
            [
                'Content-Type'         => 'application/zip',
                'X-Texture-Version'    => (string) $texture->version,
                'X-Texture-Resolution' => $resolution,
            ],
        );
    }

    /**
     * The resolution actually served: the requested one when it exists,
     * otherwise the highest available below it, otherwise the lowest
     * available. Null when the texture has no diffuse map at all.
     */
    public function pickResolution(Texture $texture, ?string $requested): ?string
    {
        $available = $texture->availableResolutions();

        if (! $available) {
            return null;
        }

        $order  = TextureMap::RESOLUTIONS;
        $target = array_search($requested ?? self::DEFAULT_RESOLUTION, $order, true);

        $atOrBelow = array_filter($available, fn(string $r) => array_search($r, $order, true) <= $target);

        return $atOrBelow ? end($atOrBelow) : $available[0];
    }

    /**
     * Maps that go into a package of the given resolution, keyed by type.
     * A map missing at that resolution comes from the next lower one, or
     * failing that from the lowest higher one (low-detail maps such as
     * roughness are often shipped smaller than the diffuse).
     *
     * @return Collection<string, TextureMap>
     */
    public function mapsFor(Texture $texture, string $resolution): Collection
    {
        $order  = TextureMap::RESOLUTIONS;
        $target = array_search($resolution, $order, true);

        return $texture->maps
            ->groupBy('type')
            ->map(function (Collection $maps) use ($order, $target) {
                $rank = fn(TextureMap $m) => array_search($m->resolution, $order, true);

                return $maps->filter(fn($m) => $rank($m) <= $target)->sortByDesc($rank)->first()
                    ?? $maps->sortBy($rank)->first();
            })
            ->filter();
    }

    /** Package size per available resolution, for the detail endpoint. */
    public function resolutionSummary(Texture $texture): array
    {
        return collect($texture->availableResolutions())
            ->map(function (string $resolution) use ($texture) {
                $maps    = $this->mapsFor($texture, $resolution);
                $diffuse = $maps->get(TextureMap::TYPE_DIFFUSE);

                return [
                    'resolution' => $resolution,
                    'width_px'   => $diffuse?->width_px,
                    'height_px'  => $diffuse?->height_px,
                    'maps'       => $maps->keys()->values()->all(),
                    'size_bytes' => (int) $maps->sum('size_bytes'),
                ];
            })
            ->values()
            ->all();
    }

    /** Relative path of the cached ZIP, building it when missing. */
    public function package(Texture $texture, string $resolution): string
    {
        $disk = Storage::disk('local');
        $path = self::PACKAGE_DIR . "/{$texture->slug}/v{$texture->version}-{$resolution}.zip";

        if ($disk->exists($path)) {
            return $path;
        }

        $maps = $this->mapsFor($texture, $resolution);

        foreach ($maps as $map) {
            abort_unless(Storage::disk($map->disk)->exists($map->path), 404);
        }

        $disk->makeDirectory(dirname($path));

        // Built under a temporary name and renamed at the end, so a parallel
        // request never streams a half-written ZIP.
        $final = $disk->path($path);
        $tmp   = $final . '.' . bin2hex(random_bytes(4)) . '.tmp';

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create texture package {$path}.");
        }

        $files = [];
        foreach ($maps as $type => $map) {
            $name = $type . '.' . pathinfo($map->path, PATHINFO_EXTENSION);
            $zip->addFile(Storage::disk($map->disk)->path($map->path), $name);
            // Images are already compressed: storing them saves CPU on the host.
            $zip->setCompressionName($name, ZipArchive::CM_STORE);
            $files[$type] = $name;
        }

        $zip->addFromString('texture.json', json_encode(
            $this->manifest($texture, $resolution, $files),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        $zip->close();
        rename($tmp, $final);

        return $path;
    }

    /** Drops every cached package of the texture (maps changed or texture deleted). */
    public static function forget(Texture $texture): void
    {
        Storage::disk('local')->deleteDirectory(self::PACKAGE_DIR . '/' . $texture->slug);
    }

    /** texture.json: what the plugin needs to build the material offline. */
    private function manifest(Texture $texture, string $resolution, array $files): array
    {
        return [
            'slug'           => $texture->slug,
            'name'           => $texture->name,
            'version'        => $texture->version,
            'resolution'     => $resolution,
            'material_type'  => $texture->material_type,
            'tile_width_mm'  => $texture->tile_width_mm,
            'tile_height_mm' => $texture->tile_height_mm,
            'is_seamless'    => $texture->is_seamless,
            'shading_color'  => $texture->shading_color,
            'base_color'     => $texture->base_color,
            'maps'           => $files,
        ];
    }
}
