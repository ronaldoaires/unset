<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View|RedirectResponse
    {
        $userId = $request->user()->id;

        // Restricts every downloads subquery below to this user's own rows.
        $ownDownloads = fn ($query) => $query->where('user_id', $userId);

        // One row per family (not per download). Unpublished families drop out,
        // since the download route answers 404 for them anyway.
        $families = Family::published()
            ->whereHas('downloads', $ownDownloads)
            ->withMax(['downloads as last_downloaded_at' => $ownDownloads], 'downloaded_at')
            ->withCount(['downloads as user_downloads_count' => $ownDownloads])
            ->withCasts(['last_downloaded_at' => 'datetime'])
            // Everything the view reads (lazy loading is blocked outside production).
            ->with(['category', 'brand', 'thumbnail', 'primaryImage'])
            ->orderByDesc('last_downloaded_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, pageName: 'pagina');

        // Defensive: a page past the end (e.g. an old bookmark) goes to the last one.
        if ($families->isEmpty() && $families->currentPage() > 1) {
            return redirect()->route('account.downloads', ['pagina' => $families->lastPage()]);
        }

        return view('site.account.downloads', ['families' => $families]);
    }
}