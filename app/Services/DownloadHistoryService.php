<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Download history of a user, shared by the account page and the API.
 */
class DownloadHistoryService
{
    /**
     * One row per family (not per download), most recently downloaded first,
     * each with "last_downloaded_at" and "user_downloads_count". Unpublished
     * families drop out, since the download route answers 404 for them anyway.
     */
    public function paginate(User $user, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        // Restricts every downloads subquery below to this user's own rows.
        $own = fn (Builder $query) => $query->where('user_id', $user->id);

        return Family::published()
            ->whereHas('downloads', $own)
            ->withMax(['downloads as last_downloaded_at' => $own], 'downloaded_at')
            ->withCount(['downloads as user_downloads_count' => $own])
            ->withCasts(['last_downloaded_at' => 'datetime'])
            // Everything the site card and the API resource read (lazy loading is blocked outside production).
            ->with(['category', 'subcategory', 'brand', 'thumbnail', 'primaryImage'])
            ->orderByDesc('last_downloaded_at')
            ->orderByDesc('id')
            ->paginate($perPage, pageName: $pageName)
            ->withQueryString();
    }
}
