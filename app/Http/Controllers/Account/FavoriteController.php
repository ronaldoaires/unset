<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    private const PER_PAGE = 2;

    public function index(Request $request): View|RedirectResponse
    {
        // Unpublished families drop out of the list but keep their row, so
        // they come back on their own if they are published again.
        $favorites = $request->user()->favorites()
            ->whereHas('family', fn ($query) => $query->published())
            // Everything the family card reads (lazy loading is blocked outside production).
            ->with(['family.category', 'family.brand', 'family.thumbnail', 'family.primaryImage'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, pageName: 'pagina');

        // Removing the last item of the last page leaves that page empty.
        if ($favorites->isEmpty() && $favorites->currentPage() > 1) {
            return redirect()->route('account.favorites', ['pagina' => $favorites->lastPage()]);
        }

        return view('site.account.favorites', ['favorites' => $favorites]);
    }
}