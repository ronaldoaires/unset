<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'WebSite',
                    'name'  => config('app.name'),
                    'url'   => url('/'),
                ],
                [
                    '@type' => 'Organization',
                    'name'  => config('app.name'),
                    'url'   => url('/'),
                ],
            ],
        ];

        return view('site.home', ['jsonLd' => $jsonLd]);
    }
}