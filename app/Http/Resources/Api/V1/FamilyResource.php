<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * List item. Needs category, subcategory, brand, thumbnail and primaryImage loaded.
 */
class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'slug'            => $this->slug,
            'name'            => $this->name,
            'category'        => $this->reference($this->category),
            'subcategory'     => $this->reference($this->subcategory),
            'brand'           => $this->reference($this->brand),
            'revit_version'   => $this->revit_version,
            'is_parametric'   => $this->is_parametric,
            'is_free'         => $this->is_free,
            'price'           => $this->price !== null ? (float) $this->price : null,
            'formatted_price' => $this->formatted_price,
            'thumbnail_url'   => $this->thumbnail_url,
            // One query per request: favoriteFamilyIds() is memoized on the user.
            'is_favorite'     => in_array($this->id, $request->user()?->favoriteFamilyIds() ?? [], true),
        ];
    }

    /**
     * Compact {id, name, slug} of a related record, or null when there is none.
     */
    protected function reference(mixed $model): ?array
    {
        return $model
            ? ['id' => $model->id, 'name' => $model->name, 'slug' => $model->slug]
            : null;
    }
}