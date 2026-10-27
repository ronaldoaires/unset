<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

/**
 * Family list item plus the user's own download data (DownloadHistoryService::paginate).
 */
class DownloadedFamilyResource extends FamilyResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'last_downloaded_at' => $this->last_downloaded_at?->toAtomString(),
            'downloads_count'    => (int) $this->user_downloads_count,
        ]);
    }
}
