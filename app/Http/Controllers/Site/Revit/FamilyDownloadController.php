<?php

namespace App\Http\Controllers\Site\Revit;

use App\Models\Download;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;

class FamilyDownloadController extends Controller
{
    public function __invoke(Request $request, Family $family): StreamedResponse
    {
        abort_unless($family->isPublished(), 404);

        // Runs FamilyPolicy::download(): free, purchased or within the plan limit.
        Gate::authorize('download', $family);

        $file = $family->rfaFiles()->firstOrFail();

        // Fail before counting anything if the file is missing from disk.
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        // The history row and the counter go together or not at all.
        DB::transaction(function () use ($request, $family) {
            Download::create([
                'user_id'       => $request->user()->id,
                'family_id'     => $family->id,
                'ip_address'    => $request->ip(),
                'downloaded_at' => now(),
            ]);

            $family->incrementDownloads();
        });

        return Storage::disk($file->disk)->download($file->path, $family->slug . '.rfa');
    }
}
