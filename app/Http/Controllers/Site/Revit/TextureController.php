<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Texture;
use App\Services\TextureDownloadService;
use App\Services\TextureListingService;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TextureController extends Controller
{
    public function index(Request $request, TextureListingService $listing): View
    {
        $data = $listing->build($request);
        $url  = route('revit.textures.index');

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Texturas PBR'],
        ];

        // Filtered/paginated variants point to the clean listing.
        $canonical = $data['hasFilters'] || $data['textures']->currentPage() > 1 ? $url : null;

        return view('site.revit.textures.index', [
            ...$data,
            'categories'  => $listing->categoryOptions(),
            'types'       => Texture::MATERIAL_TYPES,
            'breadcrumbs' => $breadcrumbs,
            'canonical'   => $canonical,
            'jsonLd'      => StructuredData::graph(StructuredData::breadcrumbs($breadcrumbs)),
        ]);
    }

    public function show(Texture $texture, TextureDownloadService $downloads): View
    {
        abort_unless($texture->isPublished(), 404);

        $texture->loadMissing(['category', 'maps']);

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Texturas PBR', 'url' => route('revit.textures.index')],
            ['name' => $texture->name],
        ];

        return view('site.revit.textures.show', [
            'texture'     => $texture,
            'resolutions' => $downloads->resolutionSummary($texture),
            'breadcrumbs' => $breadcrumbs,
            'jsonLd'      => StructuredData::graph(StructuredData::breadcrumbs($breadcrumbs)),
        ]);
    }
}
