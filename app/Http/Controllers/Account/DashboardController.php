<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Lazy loading is blocked outside production, so load what the view reads.
        $user->loadMissing('activeSubscription.plan');

        return view('site.account.index', ['user' => $user]);
    }
}