<?php

namespace App\Services;

use App\Models\Texture;
use App\Models\TextureCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Search, filters, sort and pagination of the public textures page.
 * Same lenient rules as FamilyListingService: bad values are ignored.
 */
class TextureListingService
{
    private const PER_PAGE = 24;

    private const DEFAULT_SORT = 'recentes';

    private const SORTS = ['recentes', 'baixadas', 'nome'];

    /**
     * @return array{textures: LengthAwarePaginator, filters: array, hasFilters: bool}
     */
    public function build(Request $request): array
    {
        $term     = Str::limit($this->stringParam($request, 'q'), 100, '');
        $category = $this->stringParam($request, 'categoria');
        $type     = $this->stringParam($request, 'tipo');
        $type     = array_key_exists($type, Texture::MATERIAL_TYPES) ? $type : '';
        $sort     = $this->stringParam($request, 'ordenar');
        $sort     = in_array($sort, self::SORTS, true) ? $sort : self::DEFAULT_SORT;
        $freeOnly = $request->boolean('gratuita');

        $query = Texture::published()
            ->with('category')
            ->when($term !== '', fn(Builder $query) => $query->search($term))
            ->when($category !== '', fn(Builder $query) => $query->whereHas(
                'category',
                fn(Builder $categoryQuery) => $categoryQuery->where('slug', $category),
            ))
            ->when($type !== '', fn(Builder $query) => $query->where('material_type', $type))
            ->when($freeOnly, fn(Builder $query) => $query->free());

        match ($sort) {
            'baixadas' => $query->orderByDesc('download_count'),
            'nome'     => $query->orderBy('name'),
            default    => $query->orderByDesc('created_at'),
        };

        $query->orderByDesc('id');

        return [
            'textures'   => $query->paginate(self::PER_PAGE, pageName: 'pagina')->withQueryString(),
            'filters'    => [
                'q'         => $term,
                'categoria' => $category,
                'tipo'      => $type,
                'gratuita'  => $freeOnly,
                'ordenar'   => $sort,
            ],
            'hasFilters' => $term !== '' || $category !== '' || $type !== '' || $freeOnly || $sort !== self::DEFAULT_SORT,
        ];
    }

    /** Categories that have at least one published texture. */
    public function categoryOptions(): Collection
    {
        return TextureCategory::active()->whereHas('publishedTextures')->get(['id', 'name', 'slug']);
    }

    private function stringParam(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? trim($value) : '';
    }
}
