<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Favorites of a user. Shared by the site and the API, so both apply the
 * same rules. Every verified user may favorite: it is not a plan permission.
 */
class FavoriteService
{
    /**
     * Adds a published family. Idempotent: adding it twice keeps one row.
     */
    public function add(User $user, Family $family): void
    {
        abort_unless($family->isPublished(), 404);

        try {
            $user->favorites()->firstOrCreate(['family_id' => $family->id]);
        } catch (UniqueConstraintViolationException) {
            // A parallel request (double click) saved it first: same end result.
        }
    }

    /**
     * Removes a family. No published check on purpose: users can always clean
     * up their list. Idempotent: removing a missing favorite is not an error.
     */
    public function remove(User $user, Family $family): void
    {
        $user->favorites()->where('family_id', $family->id)->delete();
    }

    /**
     * Favorited families, most recently favorited first, each with a
     * "favorited_at" attribute. Unpublished families drop out but keep their
     * row, so they come back on their own if they are published again.
     */
    public function paginate(User $user, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        $own = fn (Builder $query) => $query->where('user_id', $user->id);

        return Family::published()
            ->whereHas('favorites', $own)
            ->withMax(['favorites as favorited_at' => $own], 'created_at')
            ->withCasts(['favorited_at' => 'datetime'])
            // Everything the list resource reads (lazy loading is blocked outside production).
            ->with(['category', 'subcategory', 'brand', 'thumbnail', 'primaryImage'])
            ->orderByDesc('favorited_at')
            ->orderByDesc('id')
            ->paginate($perPage, pageName: $pageName)
            ->withQueryString();
    }
}
