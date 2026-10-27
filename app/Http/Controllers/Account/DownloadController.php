<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\DownloadHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request, DownloadHistoryService $history): View|RedirectResponse
    {
        // One row per family (not per download); same query as the API.
        $families = $history->paginate($request->user(), self::PER_PAGE, 'pagina');

        // Defensive: a page past the end (e.g. an old bookmark) goes to the last one.
        if ($families->isEmpty() && $families->currentPage() > 1) {
            return redirect()->route('account.downloads', ['pagina' => $families->lastPage()]);
        }

        return view('site.account.downloads', ['families' => $families]);
    }
}