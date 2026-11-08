<?php

namespace App\Http\Resources\Api\V1;

use App\Services\TextureDownloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Full texture. Needs "category" and "maps" loaded, and the authenticated
 * user's "activeSubscription.plan" (read by the policy).
 */
class TextureDetailResource extends TextureResource
{
    public function toArray(Request $request): array
    {
        $access   = Gate::forUser($request->user())->inspect('download', $this->resource);
        $packages = app(TextureDownloadService::class);

        $resolutions = $packages->resolutionSummary($this->resource);

        return array_merge(parent::toArray($request), [
            'description'    => $this->description,
            'preview_url'    => $this->preview_url,
            'base_color'     => $this->base_color,
            'download_count' => $this->download_count,
            'updated_at'     => $this->updated_at?->toAtomString(),
            // Only image (pbr) textures ship files; the others are built from
            // a Revit library asset and base_color.
            'needs_download' => $this->needsMaps(),
            'resolutions'    => $resolutions,
            'default_resolution' => $resolutions
                ? $packages->pickResolution($this->resource, null)
                : null,
            'download'       => [
                'url'     => route('api.v1.textures.download', ['texture' => $this->slug]),
                'allowed' => $access->allowed(),
                'message' => $access->message(),
                // null or "subscription_required".
                'code'    => $access->code(),
            ],
        ]);
    }
}
