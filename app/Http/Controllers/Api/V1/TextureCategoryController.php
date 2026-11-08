<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TextureCategoryResource;
use App\Models\TextureCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TextureCategoryController extends Controller
{
    /** Active categories with at least one published texture, in display order. */
    public function index(): AnonymousResourceCollection
    {
        $categories = TextureCategory::active()
            ->whereHas('publishedTextures')
            ->withCount('publishedTextures')
            ->get();

        return TextureCategoryResource::collection($categories);
    }
}
