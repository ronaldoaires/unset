<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

/**
 * Family list item plus the moment it was favorited (FavoriteService::paginate).
 */
class FavoriteFamilyResource extends FamilyResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'favorited_at' => $this->favorited_at?->toAtomString(),
        ]);
    }
}
