<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\FamilyListingService;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index(Request $request, FamilyListingService $listing): View
    {
        $data     = $listing->build($request);
        $families = $data['families'];
        $url      = route('revit.families.index');

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Famílias Revit'],
        ];

        return view('site.revit.families.index', [
            ...$data,
            'categories'  => $listing->categoryOptions(),
            'brands'      => $listing->brandOptions(),
            'breadcrumbs' => $breadcrumbs,
            'canonical'   => $listing->canonical($url, $families),
            'jsonLd'      => StructuredData::graph(
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::collectionPage('Famílias Revit', $url, null, $families),
            ),
        ]);
    }

    public function show(Family $family): View
    {
        abort_unless($family->isPublished(), 404);

        $family->loadMissing(['category', 'subcategory', 'brand', 'primaryImage', 'tags']);

        $family->incrementViews();

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Famílias Revit', 'url' => route('revit.families.index')],
        ];

        // Inactive categories and subcategories have no public page, so they are not linked.
        if ($family->category) {
            $breadcrumbs[] = [
                'name' => $family->category->name,
                'url'  => $family->category->is_active
                    ? route('revit.categories.show', ['category' => $family->category->slug])
                    : null,
            ];

            if ($family->subcategory) {
                $breadcrumbs[] = [
                    'name' => $family->subcategory->name,
                    'url'  => $family->category->is_active && $family->subcategory->is_active
                        ? route('revit.subcategories.show', [
                            'category'    => $family->category->slug,
                            'subcategory' => $family->subcategory->slug,
                        ])
                        : null,
                ];
            }
        }

        $breadcrumbs[] = ['name' => $family->name];

        return view('site.revit.families.show', [
            'family'      => $family,
            'breadcrumbs' => $breadcrumbs,
            'jsonLd'      => StructuredData::graph(StructuredData::breadcrumbs($breadcrumbs)),
        ]);
    }
}