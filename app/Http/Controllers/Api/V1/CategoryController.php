<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Active categories (with their active subcategories) that have at least
     * one published family, in display order. Small list, so no pagination.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::active()
            ->whereHas('publishedFamilies')
            ->withCount('publishedFamilies')
            ->with(['activeSubcategories' => fn ($query) => $query->whereHas('publishedFamilies')])
            ->get();

        return CategoryResource::collection($categories);
    }
}