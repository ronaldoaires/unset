<?php

namespace App\Services;

use App\Models\Family;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Search, filter, sort and pagination of the published catalog for the API.
 * Filters are already validated by FamilyIndexRequest.
 */
class FamilyCatalogService
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $category    = $filters['category'] ?? null;
        $subcategory = $filters['subcategory'] ?? null;
        $brand       = $filters['brand'] ?? null;
        $term        = $filters['q'] ?? null;

        $query = Family::published()
            // Everything the list resource reads (lazy loading is blocked outside production).
            ->with(['category', 'subcategory', 'brand', 'thumbnail', 'primaryImage'])
            ->when($term, fn (Builder $query) => $query->search($term))
            ->when($category, fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category),
            ))
            ->when($subcategory, fn (Builder $query) => $query->whereHas(
                'subcategory',
                fn (Builder $subcategoryQuery) => $subcategoryQuery->where('slug', $subcategory),
            ))
            ->when($brand, fn (Builder $query) => $query->whereHas(
                'brand',
                fn (Builder $brandQuery) => $brandQuery->where('slug', $brand),
            ))
            // free=1 keeps only free families, free=0 only paid ones; absent = both.
            ->when(
                isset($filters['free']),
                fn (Builder $query) => filter_var($filters['free'], FILTER_VALIDATE_BOOLEAN)
                    ? $query->free()
                    : $query->premium(),
            )
            // Only families the given Revit version can open.
            ->when(
                isset($filters['revit_version']),
                fn (Builder $query) => $query->where('revit_version', '<=', (int) $filters['revit_version']),
            );

        match ($filters['sort'] ?? 'recent') {
            'downloads' => $query->orderByDesc('download_count'),
            'views'     => $query->orderByDesc('view_count'),
            'name'      => $query->orderBy('name'),
            default     => $query->orderByDesc('created_at'),
        };

        // Tie-breaker keeps pagination stable when values repeat.
        $query->orderByDesc('id');

        return $query
            ->paginate((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE))
            ->withQueryString();
    }
}