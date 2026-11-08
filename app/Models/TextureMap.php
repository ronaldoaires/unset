<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $formatted_size
 */
class TextureMap extends Model
{
    // ─── Map types ──────────────────────────────────────────────────
    const TYPE_DIFFUSE      = 'diffuse';
    const TYPE_NORMAL       = 'normal';
    const TYPE_ROUGHNESS    = 'roughness';
    const TYPE_AO           = 'ao';
    const TYPE_DISPLACEMENT = 'displacement';
    const TYPE_ALPHA        = 'alpha';
    const TYPE_METALLIC     = 'metallic';

    /** Map type => label shown in the admin. */
    const TYPES = [
        self::TYPE_DIFFUSE      => 'Cor (diffuse)',
        self::TYPE_NORMAL       => 'Normal',
        self::TYPE_ROUGHNESS    => 'Rugosidade (roughness)',
        self::TYPE_AO           => 'Oclusão (AO)',
        self::TYPE_DISPLACEMENT => 'Deslocamento (displacement)',
        self::TYPE_ALPHA        => 'Recorte (alpha)',
        self::TYPE_METALLIC     => 'Metálico (metallic)',
    ];

    /**
     * Words found in common PBR file names, per map type. Checked in this
     * order, so "roughness" wins over a stray "color" in the same name.
     */
    const NAME_HINTS = [
        self::TYPE_NORMAL       => ['normal', 'nrm', 'nor_gl', 'nor'],
        self::TYPE_ROUGHNESS    => ['roughness', 'rough', 'rgh'],
        self::TYPE_AO           => ['ambientocclusion', 'ambient_occlusion', 'occlusion', 'ao'],
        self::TYPE_DISPLACEMENT => ['displacement', 'height', 'disp', 'bump'],
        self::TYPE_ALPHA        => ['opacity', 'alpha', 'mask', 'cutout'],
        self::TYPE_METALLIC     => ['metallic', 'metalness', 'metal'],
        self::TYPE_DIFFUSE      => ['diffuse', 'basecolor', 'base_color', 'albedo', 'color', 'col', 'diff'],
    ];

    // ─── Resolutions ────────────────────────────────────────────────
    const RESOLUTIONS = ['1k', '2k', '4k', '8k'];

    protected $fillable = [
        'texture_id',
        'type',
        'resolution',
        'disk',
        'path',
        'original_name',
        'width_px',
        'height_px',
        'size_bytes',
        'mime_type',
    ];

    protected $casts = [
        'width_px'   => 'integer',
        'height_px'  => 'integer',
        'size_bytes' => 'integer',
    ];

    // ─── Hooks ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (TextureMap $map) {
            $disk = Storage::disk($map->disk ?? 'local');
            $disk->delete($map->path);

            // Remove the resolution folder and then the texture folder once
            // they are empty (textures/{slug}/{resolution}/{type}.ext).
            $folder = dirname($map->path);

            foreach ([$folder, dirname($folder)] as $dir) {
                if (str_starts_with($dir, 'textures/') && $disk->exists($dir)
                    && empty($disk->files($dir)) && empty($disk->directories($dir))) {
                    $disk->deleteDirectory($dir);
                }
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function texture(): BelongsTo
    {
        return $this->belongsTo(Texture::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────

    protected function typeLabel(): Attribute
    {
        return Attribute::make(get: fn() => self::TYPES[$this->type] ?? $this->type);
    }

    protected function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->width_px && $this->height_px
                ? $this->width_px . ' × ' . $this->height_px . ' px'
                : '—'
        );
    }

    protected function formattedSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->size_bytes) {
                    return '—';
                }

                $units = ['B', 'KB', 'MB', 'GB'];
                $bytes = $this->size_bytes;
                $i     = 0;

                while ($bytes >= 1024 && $i < count($units) - 1) {
                    $bytes /= 1024;
                    $i++;
                }

                return round($bytes, 1) . ' ' . $units[$i];
            }
        );
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /** Guesses the map type from a file name, or null when nothing matches. */
    public static function guessType(string $fileName): ?string
    {
        $name = strtolower(pathinfo($fileName, PATHINFO_FILENAME));

        // Split on separators so short hints ("ao", "col") only match whole words.
        $words = preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach (self::NAME_HINTS as $type => $hints) {
            foreach ($hints as $hint) {
                if (in_array($hint, $words, true) || (strlen($hint) > 4 && str_contains($name, $hint))) {
                    return $type;
                }
            }
        }

        return null;
    }

    /** Resolution label for a pixel size: 1024 → 1k, 2048 → 2k... */
    public static function resolutionFor(int $longestSide): string
    {
        return match (true) {
            $longestSide >= 7000 => '8k',
            $longestSide >= 3500 => '4k',
            $longestSide >= 1800 => '2k',
            default              => '1k',
        };
    }
}
