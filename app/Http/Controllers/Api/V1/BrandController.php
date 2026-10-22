<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    /**
     * Active brands that have at least one published family, ordered by name.
     */
    public function index(): AnonymousResourceCollection
    {
        $brands = Brand::active()
            ->whereHas('publishedFamilies')
            ->get();

        return BrandResource::collection($brands);
    }
}