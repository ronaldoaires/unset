<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TextureIndexRequest;
use App\Http\Resources\Api\V1\TextureDetailResource;
use App\Http\Resources\Api\V1\TextureResource;
use App\Models\Texture;
use App\Services\TextureCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TextureController extends Controller
{
    public function index(TextureIndexRequest $request, TextureCatalogService $catalog): AnonymousResourceCollection
    {
        return TextureResource::collection($catalog->paginate($request->validated()));
    }

    public function show(Request $request, Texture $texture): TextureDetailResource
    {
        abort_unless($texture->isPublished(), 404);

        $texture->loadMissing(['category', 'maps']);
        $request->user()->loadMissing('activeSubscription.plan');

        return new TextureDetailResource($texture);
    }
}
