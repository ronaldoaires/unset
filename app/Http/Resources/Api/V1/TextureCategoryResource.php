<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TextureCategoryResource extends JsonResource
{
    /** Needs "published_textures_count" (withCount). */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'textures_count' => $this->published_textures_count,
        ];
    }
}
