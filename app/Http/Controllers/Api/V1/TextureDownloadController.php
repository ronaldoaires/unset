<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TextureDownloadRequest;
use App\Models\Texture;
use App\Services\TextureDownloadService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TextureDownloadController extends Controller
{
    public function __invoke(TextureDownloadRequest $request, Texture $texture, TextureDownloadService $downloads): StreamedResponse
    {
        $texture->loadMissing('maps');

        return $downloads->download($request->user(), $texture, $request->validated('resolution'), $request->ip());
    }
}
