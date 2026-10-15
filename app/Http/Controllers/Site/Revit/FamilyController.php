<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Family;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FamilyController extends Controller
{
    private const PER_PAGE = 12;

    private const DEFAULT_SORT = 'recentes';

    /** Allowed values of the "ordenar" query parameter. */
    private const SORTS = ['recentes', 'baixados', 'visualizados', 'nome'];

    public function index(Request $request): View
    {
        // Filters are read leniently: invalid values are ignored instead of
        // raising an error, so a shared or mistyped URL always opens.
        $term     = Str::limit($this->stringParam($request, 'q'), 100, '');
        $category = $this->stringParam($request, 'categoria');
        $sort     = $this->stringParam($request, 'ordenar');
        $sort     = in_array($sort, self::SORTS, true) ? $sort : self::DEFAULT_SORT;
        $freeOnly = $request->boolean('gratuita');

        $query = Family::published()
            // Relations used by the card, loaded up front (lazy loading is blocked outside production).
            ->with(['category', 'brand', 'thumbnail', 'primaryImage'])
            ->when($term !== '', fn (Builder $query) => $query->search($term))
            ->when($category !== '', fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category),
            ))
            ->when($freeOnly, fn (Builder $query) => $query->free());

        $this->applySort($query, $sort);

        $families = $query
            ->paginate(self::PER_PAGE, pageName: 'pagina')
            ->withQueryString();

        $hasFilters = $term !== '' || $category !== '' || $freeOnly || $sort !== self::DEFAULT_SORT;

        return view('site.revit.families.index', [
            'families'   => $families,
            'categories' => Category::active()->whereHas('publishedFamilies')->get(['id', 'name', 'slug']),
            'filters'    => [
                'q'         => $term,
                'categoria' => $category,
                'gratuita'  => $freeOnly,
                'ordenar'   => $sort,
            ],
            'hasFilters' => $hasFilters,
            // Filtered pages are noindex in the view; paginated pages canonicalize to themselves.
            'canonical'  => route(
                'revit.families.index',
                $families->currentPage() > 1 ? ['pagina' => $families->currentPage()] : [],
            ),
        ]);
    }

    public function show(Family $family): View
    {
        abort_unless($family->isPublished(), 404);

        $family->loadMissing(['category', 'subcategory', 'brand', 'primaryImage', 'tags']);

        $family->incrementViews();

        return view('site.revit.families.show', ['family' => $family]);
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