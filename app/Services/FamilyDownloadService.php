<?php

namespace App\Services;

use App\Models\Download;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The one place that decides and records a family download. Shared by the
 * site and the API, so both always apply the same rules.
 */
class FamilyDownloadService
{
    /**
     * Checks the rules, records the download and streams the .rfa.
     * Aborts with 404 (unpublished family, missing file) or 403 (policy denial).
     */
    public function download(User $user, Family $family, ?string $ip): StreamedResponse
    {
        abort_unless($family->isPublished(), 404);

        // The policy reads the user's subscription: load it once up front.
        $user->loadMissing('activeSubscription.plan');

        // Runs FamilyPolicy::download(): free, purchased or within the plan limit.
        Gate::forUser($user)->authorize('download', $family);

        $file = $family->rfaFiles()->firstOrFail();

        // Fail before counting anything if the file is missing from disk.
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        // The history row and the counter go together or not at all.
        DB::transaction(function () use ($user, $family, $ip) {
            Download::create([
                'user_id'       => $user->id,
                'family_id'     => $family->id,
                'ip_address'    => $ip,
                'downloaded_at' => now(),
            ]);

            $family->incrementDownloads();
        });

        return Storage::disk($file->disk)->download($file->path, $family->slug . '.rfa');
    }
}