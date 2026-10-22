<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Needs "activeSubcategories" loaded and "published_families_count" (withCount).
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'icon'           => $this->icon,
            'families_count' => $this->published_families_count,
            'subcategories'  => $this->activeSubcategories->map(fn ($subcategory) => [
                'id'   => $subcategory->id,
                'name' => $subcategory->name,
                'slug' => $subcategory->slug,
            ])->values(),
        ];
    }
}