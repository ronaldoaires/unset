<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Family;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shared search, filter, sort and pagination for every page that lists
 * families (main listing, category, subcategory and brand pages).
 */
class FamilyListingService
{
    private const PER_PAGE = 20;

    private const DEFAULT_SORT = 'recentes';

    /** Allowed values of the "ordenar" query parameter. */
    private const SORTS = ['recentes', 'baixados', 'visualizados', 'nome'];

    /**
     * @param  Builder|null  $scope   Base query (defaults to all published families).
     * @param  array<string>  $ignore  Filters fixed by the page itself ('categoria', 'marca').
     * @return array{families: LengthAwarePaginator, filters: array, hasFilters: bool}
     */
    public function build(Request $request, ?Builder $scope = null, array $ignore = []): array
    {
        // Filters are read leniently: invalid values are ignored instead of
        // raising an error, so a shared or mistyped URL always opens.
        $term     = Str::limit($this->stringParam($request, 'q'), 100, '');
        $category = in_array('categoria', $ignore, true) ? '' : $this->stringParam($request, 'categoria');
        $brand    = in_array('marca', $ignore, true) ? '' : $this->stringParam($request, 'marca');
        $sort     = $this->stringParam($request, 'ordenar');
        $sort     = in_array($sort, self::SORTS, true) ? $sort : self::DEFAULT_SORT;
        $freeOnly = $request->boolean('gratuita');

        $query = ($scope ?? Family::published())
            // Relations used by the card, loaded up front (lazy loading is blocked outside production).
            ->with(['category', 'brand', 'thumbnail', 'primaryImage'])
            ->when($term !== '', fn(Builder $query) => $query->search($term))
            ->when($category !== '', fn(Builder $query) => $query->whereHas(
                'category',
                fn(Builder $categoryQuery) => $categoryQuery->where('slug', $category),
            ))
            ->when($brand !== '', fn(Builder $query) => $query->whereHas(
                'brand',
                fn(Builder $brandQuery) => $brandQuery->where('slug', $brand),
            ))
            ->when($freeOnly, fn(Builder $query) => $query->free());

        $this->applySort($query, $sort);

        return [
            'families'   => $query->paginate(self::PER_PAGE, pageName: 'pagina')->withQueryString(),
            'filters'    => [
                'q'         => $term,
                'categoria' => $category,
                'marca'     => $brand,
                'gratuita'  => $freeOnly,
                'ordenar'   => $sort,
            ],
            'hasFilters' => $term !== '' || $category !== '' || $brand !== '' || $freeOnly || $sort !== self::DEFAULT_SORT,
        ];
    }

    /**
     * Categories offered in the filter: only those with published families
     * (optionally narrowed by $constraint, applied to the families query).
     */
    public function categoryOptions(?Closure $constraint = null): Collection
    {
        return Category::active()
            ->whereHas('publishedFamilies', $constraint)
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Brands offered in the filter: only those with published families
     * (optionally narrowed by $constraint, applied to the families query).
     */
    public function brandOptions(?Closure $constraint = null): Collection
    {
        return Brand::active()
            ->whereHas('publishedFamilies', $constraint)
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Filtered pages are noindex in the view; paginated pages canonicalize to themselves.
     */
    public function canonical(string $url, LengthAwarePaginator $families): string
    {
        return $families->currentPage() > 1
            ? $url . '?pagina=' . $families->currentPage()
            : $url;
    }

    /**
     * Keeps titles of paginated pages unique ("... - Página 2").
     */
    public function titleWithPage(string $title, LengthAwarePaginator $families): string
    {
        return $families->currentPage() > 1
            ? $title . ' - Página ' . $families->currentPage()
            : $title;
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'baixados'     => $query->orderByDesc('download_count'),
            'visualizados' => $query->orderByDesc('view_count'),
            'nome'         => $query->orderBy('name'),
            default        => $query->orderByDesc('created_at'),
        };

        // Tie-breaker keeps pagination stable when values repeat.
        $query->orderByDesc('id');
    }

    /**
     * Query-string value as a trimmed string; arrays (?q[]=x) become an empty string.
     */
    private function stringParam(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? trim($value) : '';
    }
}