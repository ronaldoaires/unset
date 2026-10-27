<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\Api\V1\FavoriteFamilyResource;
use App\Models\Family;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(PaginationRequest $request, FavoriteService $favorites): AnonymousResourceCollection
    {
        return FavoriteFamilyResource::collection(
            $favorites->paginate($request->user(), $request->perPage()),
        );
    }

    public function store(Request $request, Family $family, FavoriteService $favorites): JsonResponse
    {
        $favorites->add($request->user(), $family);

        return response()->json(['data' => ['slug' => $family->slug, 'is_favorite' => true]]);
    }

    public function destroy(Request $request, Family $family, FavoriteService $favorites): JsonResponse
    {
        $favorites->remove($request->user(), $family);

        return response()->json(['data' => ['slug' => $family->slug, 'is_favorite' => false]]);
    }
}
