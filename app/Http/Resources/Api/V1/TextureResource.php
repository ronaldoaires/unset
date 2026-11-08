<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * List item. Needs "category" loaded.
 */
class TextureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'slug'                => $this->slug,
            'name'                => $this->name,
            'category'            => $this->category
                ? ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug]
                : null,
            'material_type'       => $this->material_type,
            'material_type_label' => $this->material_type_label,
            'tile_width_mm'       => $this->tile_width_mm,
            'tile_height_mm'      => $this->tile_height_mm,
            'is_seamless'         => $this->is_seamless,
            'is_free'             => $this->is_free,
            'is_featured'         => $this->is_featured,
            'shading_color'       => $this->shading_color,
            'thumbnail_url'       => $this->thumbnail_url,
            'version'             => $this->version,
        ];
    }
}
