<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Family;
use App\Services\FamilyListingService;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function show(Request $request, Brand $brand, FamilyListingService $listing): View
    {
        abort_unless($brand->is_active, 404);

        $brand->incrementViews();

        $data     = $listing->build(
            $request,
            Family::published()->where('brand_id', $brand->id),
            ignore: ['marca'],
        );
        $families = $data['families'];
        $url      = route('revit.brands.show', ['brand' => $brand->slug]);

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Famílias Revit', 'url' => route('revit.families.index')],
            ['name' => $brand->name],
        ];

        $intro = 'Famílias Revit da marca ' . $brand->name . ' (.rfa), gratuitas e premium, prontas para usar nos seus projetos BIM.';

        return view('site.revit.families.collection', [
            ...$data,
            'seoTitle'    => $listing->titleWithPage($brand->name . ' - Famílias Revit', $families),
            'heading'     => $brand->name,
            'intro'       => $intro,
            'logo'        => $brand->logo_thumb_url,
            'chips'       => [],
            'breadcrumbs' => $breadcrumbs,
            'action'      => $url,
            'canonical'   => $listing->canonical($url, $families),
            'noindex'     => $data['hasFilters'] || $families->isEmpty(),
            'categories'  => $listing->categoryOptions(fn($query) => $query->where('brand_id', $brand->id)),
            'brands'      => null,
            'jsonLd'      => StructuredData::graph(
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::collectionPage($brand->name, $url, $intro, $families),
            ),
        ]);
    }
}