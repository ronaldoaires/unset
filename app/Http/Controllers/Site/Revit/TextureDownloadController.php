<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Texture;
use App\Models\TextureMap;
use App\Services\TextureDownloadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ZIP of a texture from the site. Same service as the API: same access rule,
 * same cached package and the same download history.
 */
class TextureDownloadController extends Controller
{
    public function __invoke(Request $request, Texture $texture, TextureDownloadService $downloads): StreamedResponse
    {
        $resolution = $request->query('resolucao');
        $resolution = in_array($resolution, TextureMap::RESOLUTIONS, true) ? $resolution : null;

        $texture->loadMissing('maps');

        return $downloads->download($request->user(), $texture, $resolution, $request->ip());
    }
}
