<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View|RedirectResponse
    {
        // Arrays (?q[]=x) are ignored instead of raising an error.
        $query = $request->query('q');
        $term  = is_string($query) ? Str::limit(trim($query), 100, '') : '';

        $purchases = $request->user()->familyPurchases()
            // Everything the view reads (lazy loading is blocked outside production).
            ->with(['family.category', 'family.brand', 'family.thumbnail', 'family.primaryImage'])
            ->when($term !== '', fn ($purchases) => $purchases->whereHas(
                'family',
                fn ($family) => $family->where('name', 'like', "%{$term}%"),
            ))
            // updated_at, not created_at: a new attempt reuses the row of an old one.
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, pageName: 'pagina')
            ->withQueryString();

        // A page past the end (e.g. an old bookmark) goes to the last one.
        if ($purchases->isEmpty() && $purchases->currentPage() > 1) {
            return redirect()->route('account.purchases', array_filter([
                'q'      => $term,
                'pagina' => $purchases->lastPage(),
            ]));
        }

        return view('site.account.purchases', [
            'purchases' => $purchases,
            'term'      => $term,
        ]);
    }
}