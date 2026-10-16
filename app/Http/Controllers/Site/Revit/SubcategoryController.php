<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Family;
use App\Models\Subcategory;
use App\Services\FamilyListingService;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function show(
        Request $request,
        Category $category,
        Subcategory $subcategory,
        FamilyListingService $listing,
    ): View {
        abort_unless($category->is_active && $subcategory->is_active, 404);

        $subcategory->incrementViews();

        $data     = $listing->build(
            $request,
            Family::published()->where('subcategory_id', $subcategory->id),
            ignore: ['categoria'],
        );
        $families = $data['families'];
        $url      = route('revit.subcategories.show', [
            'category'    => $category->slug,
            'subcategory' => $subcategory->slug,
        ]);
        $categoryUrl = route('revit.categories.show', ['category' => $category->slug]);

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Famílias Revit', 'url' => route('revit.families.index')],
            ['name' => $category->name, 'url' => $categoryUrl],
            ['name' => $subcategory->name],
        ];

        // "All" chip first, then the sibling subcategories with the current one highlighted.
        $chips = [['name' => 'Todas', 'url' => $categoryUrl, 'active' => false]];

        $category->activeSubcategories()
            ->whereHas('publishedFamilies')
            ->get(['id', 'category_id', 'name', 'slug'])
            ->each(function ($sibling) use (&$chips, $category, $subcategory) {
                $chips[] = [
                    'name'   => $sibling->name,
                    'url'    => route('revit.subcategories.show', [
                        'category'    => $category->slug,
                        'subcategory' => $sibling->slug,
                    ]),
                    'active' => $sibling->is($subcategory),
                ];
            });

        $intro = $subcategory->description
            ?: 'Famílias Revit de ' . $subcategory->name . ' em ' . $category->name . ' (.rfa), gratuitas e premium, prontas para usar nos seus projetos BIM.';

        return view('site.revit.families.collection', [
            ...$data,
            'seoTitle'    => $listing->titleWithPage($subcategory->name . ' - ' . $category->name . ' - Famílias Revit', $families),
            'heading'     => $subcategory->name,
            'intro'       => $intro,
            'logo'        => null,
            'chips'       => $chips,
            'breadcrumbs' => $breadcrumbs,
            'action'      => $url,
            'canonical'   => $listing->canonical($url, $families),
            'noindex'     => $data['hasFilters'] || $families->isEmpty(),
            'categories'  => null,
            'brands'      => $listing->brandOptions(fn($query) => $query->where('subcategory_id', $subcategory->id)),
            'jsonLd'      => StructuredData::graph(
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::collectionPage($subcategory->name, $url, $intro, $families),
            ),
        ]);
    }
}