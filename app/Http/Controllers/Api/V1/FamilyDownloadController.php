<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\FamilyDownloadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FamilyDownloadController extends Controller
{
    public function __invoke(Request $request, Family $family, FamilyDownloadService $downloads): StreamedResponse
    {
        return $downloads->download($request->user(), $family, $request->ip());
    }
}