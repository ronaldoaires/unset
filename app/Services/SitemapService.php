<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Family;
use App\Models\Subcategory;
use Illuminate\Support\Carbon;

/**
 * Builds the list of public, indexable URLs for sitemap.xml.
 * Mirrors the noindex rules of the pages: only active records with at
 * least one published family are listed.
 */
class SitemapService
{
    /**
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    public function entries(): array
    {
        $entries = [];

        $entries[] = ['loc' => route('home'), 'lastmod' => null];
        $entries[] = ['loc' => route('legal.terms'), 'lastmod' => null];
        $entries[] = ['loc' => route('legal.privacy'), 'lastmod' => null];

        $entries[] = [
            'loc'     => route('revit.families.index'),
            'lastmod' => $this->iso(Family::published()->max('updated_at')),
        ];

        $categories = Category::active()
            ->whereHas('publishedFamilies')
            ->withMax('publishedFamilies', 'updated_at')
            ->get();

        foreach ($categories as $category) {
            $entries[] = [
                'loc'     => route('revit.categories.show', ['category' => $category->slug]),
                'lastmod' => $this->iso($category->published_families_max_updated_at),
            ];
        }

        $subcategories = Subcategory::query()
            ->where('is_active', true)
            ->whereHas('category', fn($query) => $query->where('is_active', true))
            ->whereHas('publishedFamilies')
            ->with('category:id,slug')
            ->withMax('publishedFamilies', 'updated_at')
            ->get();

        foreach ($subcategories as $subcategory) {
            $entries[] = [
                'loc'     => route('revit.subcategories.show', [
                    'category'    => $subcategory->category->slug,
                    'subcategory' => $subcategory->slug,
                ]),
                'lastmod' => $this->iso($subcategory->published_families_max_updated_at),
            ];
        }

        $brands = Brand::active()
            ->whereHas('publishedFamilies')
            ->withMax('publishedFamilies', 'updated_at')
            ->get();

        foreach ($brands as $brand) {
            $entries[] = [
                'loc'     => route('revit.brands.show', ['brand' => $brand->slug]),
                'lastmod' => $this->iso($brand->published_families_max_updated_at),
            ];
        }

        // Read in chunks so memory stays flat however large the catalog grows.
        $families = Family::published()
            ->select(['id', 'slug', 'updated_at'])
            ->lazyById(1000);

        foreach ($families as $family) {
            $entries[] = [
                'loc'     => route('revit.families.show', ['family' => $family->slug]),
                'lastmod' => $this->iso($family->updated_at),
            ];
        }

        return $entries;
    }

    /**
     * W3C datetime (with the app timezone offset), or null when there is no date.
     */
    private function iso(mixed $date): ?string
    {
        return $date ? Carbon::parse($date)->toAtomString() : null;
    }
}
