<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FamilyIndexRequest;
use App\Http\Resources\Api\V1\FamilyDetailResource;
use App\Http\Resources\Api\V1\FamilyResource;
use App\Models\Family;
use App\Services\FamilyCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FamilyController extends Controller
{
    public function index(FamilyIndexRequest $request, FamilyCatalogService $catalog): AnonymousResourceCollection
    {
        return FamilyResource::collection($catalog->paginate($request->validated()));
    }

    public function show(Request $request, Family $family): FamilyDetailResource
    {
        abort_unless($family->isPublished(), 404);

        $family->loadMissing(['category', 'subcategory', 'brand', 'thumbnail', 'primaryImage', 'tags', 'rfaFiles']);

        // The download rule reads the user's subscription: load it once up front.
        $request->user()->loadMissing('activeSubscription.plan');

        return new FamilyDetailResource($family);
    }
}