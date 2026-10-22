<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Full family. Besides the list relations it needs tags and rfaFiles loaded,
 * and the authenticated user's "activeSubscription.plan" (read by the policy).
 */
class FamilyDetailResource extends FamilyResource
{
    public function toArray(Request $request): array
    {
        // Same rule as the site: free, purchased or within the plan limit.
        $access = Gate::forUser($request->user())->inspect('download', $this->resource);

        $rfa = $this->rfaFiles->first();

        return array_merge(parent::toArray($request), [
            'description'    => $this->description,
            'tags'           => $this->tag_list,
            'image_url'      => $this->image_url,
            'download_count' => $this->download_count,
            'updated_at'     => $this->updated_at?->toAtomString(),
            'file'           => $rfa ? [
                'size_bytes'     => $rfa->size_bytes,
                'formatted_size' => $rfa->formatted_size,
            ] : null,
            'page_url'       => route('revit.families.show', ['family' => $this->slug]),
            'download'       => [
                'allowed'      => $access->allowed(),
                'message'      => $access->message(),
                // Purchases happen on the site, never inside the plugin.
                'purchase_url' => ! $access->allowed() && ($this->price ?? 0) > 0
                    ? route('revit.families.purchase.show', ['family' => $this->slug])
                    : null,
            ],
        ]);
    }
}