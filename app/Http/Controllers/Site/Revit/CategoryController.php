<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Family;
use App\Services\FamilyListingService;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category, FamilyListingService $listing): View
    {
        abort_unless($category->is_active, 404);

        $category->incrementViews();

        $data     = $listing->build(
            $request,
            Family::published()->byCategory($category->id),
            ignore: ['categoria'],
        );
        $families = $data['families'];
        $url      = route('revit.categories.show', ['category' => $category->slug]);

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Famílias Revit', 'url' => route('revit.families.index')],
            ['name' => $category->name],
        ];

        // Only subcategories that actually have published families.
        $chips = $category->activeSubcategories()
            ->whereHas('publishedFamilies')
            ->get(['id', 'category_id', 'name', 'slug'])
            ->map(fn($subcategory) => [
                'name'   => $subcategory->name,
                'url'    => route('revit.subcategories.show', [
                    'category'    => $category->slug,
                    'subcategory' => $subcategory->slug,
                ]),
                'active' => false,
            ])
            ->all();

        $intro = $category->description
            ?: 'Famílias Revit de ' . $category->name . ' (.rfa) gratuitas e premium, prontas para usar nos seus projetos BIM.';

        return view('site.revit.families.collection', [
            ...$data,
            'seoTitle'    => $listing->titleWithPage($category->name . ' - Famílias Revit', $families),
            'heading'     => $category->name,
            'intro'       => $intro,
            'logo'        => null,
            'chips'       => $chips,
            'breadcrumbs' => $breadcrumbs,
            'action'      => $url,
            'canonical'   => $listing->canonical($url, $families),
            'noindex'     => $data['hasFilters'] || $families->isEmpty(),
            'categories'  => null,
            'brands'      => $listing->brandOptions(fn($query) => $query->where('category_id', $category->id)),
            'jsonLd'      => StructuredData::graph(
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::collectionPage($category->name, $url, $intro, $families),
            ),
        ]);
    }
}