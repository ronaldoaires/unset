<?php

namespace App\Services;

use App\Models\Texture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Search, filter, sort and pagination of the published textures for the API.
 * Filters are already validated by TextureIndexRequest.
 */
class TextureCatalogService
{
    private const DEFAULT_PER_PAGE = 24;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $term     = $filters['q'] ?? null;
        $category = $filters['category'] ?? null;

        $query = Texture::published()
            ->with('category')
            ->when($term, fn(Builder $query) => $query->search($term))
            ->when($category, fn(Builder $query) => $query->whereHas(
                'category',
                fn(Builder $categoryQuery) => $categoryQuery->where('slug', $category),
            ))
            ->when(
                isset($filters['material_type']),
                fn(Builder $query) => $query->where('material_type', $filters['material_type']),
            )
            // free=1 only free, free=0 only subscriber textures, absent = both.
            ->when(
                isset($filters['free']),
                fn(Builder $query) => $query->where('is_free', filter_var($filters['free'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->when(
                filter_var($filters['featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn(Builder $query) => $query->where('is_featured', true),
            );

        match ($filters['sort'] ?? 'recent') {
            'downloads' => $query->orderByDesc('download_count'),
            'name'      => $query->orderBy('name'),
            default     => $query->orderByDesc('created_at'),
        };

        $query->orderByDesc('id');

        return $query
            ->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE))
            ->withQueryString();
    }
}
