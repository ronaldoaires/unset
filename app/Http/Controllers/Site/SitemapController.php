<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(SitemapService $sitemap): Response
    {
        // The XML declaration lives here, not in the Blade view: with
        // short_open_tag enabled on the server, PHP reads "<?xml" in a
        // template as the start of a PHP block and the view fails to compile.
        $build = fn() => '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . view('site.sitemap', ['entries' => $sitemap->entries()])->render();

        // Only the rendered XML string is cached: the cache config does not
        // allow serializing objects. Outside production it is rebuilt on every
        // request so changes show up immediately while developing.
        $xml = app()->isProduction()
            ? Cache::remember('sitemap.xml', now()->addHour(), $build)
            : $build();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}