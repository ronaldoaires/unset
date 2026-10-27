<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\Api\V1\DownloadedFamilyResource;
use App\Services\DownloadHistoryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DownloadHistoryController extends Controller
{
    public function __invoke(PaginationRequest $request, DownloadHistoryService $history): AnonymousResourceCollection
    {
        return DownloadedFamilyResource::collection(
            $history->paginate($request->user(), $request->perPage()),
        );
    }
}
