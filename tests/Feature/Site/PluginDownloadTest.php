<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'plugin.github_repo'   => 'acme/plugin',
        'plugin.cache_minutes' => 15,
    ]);

    Cache::flush();
    Http::preventStrayRequests();
});

it('redirects to the installer of the latest release', function () {
    Http::fake([
        'https://api.github.com/repos/acme/plugin/releases/latest' => Http::response([
            'tag_name' => 'v1.0.0',
            'html_url' => 'https://github.com/acme/plugin/releases/tag/v1.0.0',
            'assets'   => [[
                'name'                 => 'UnSetBIM-Setup-1.0.0.exe',
                'size'                 => 100,
                'browser_download_url' => 'https://github.com/acme/plugin/releases/download/v1.0.0/UnSetBIM-Setup-1.0.0.exe',
            ]],
        ]),
    ]);

    $this->get(route('plugin.download'))
        ->assertRedirect('https://github.com/acme/plugin/releases/download/v1.0.0/UnSetBIM-Setup-1.0.0.exe');
});

it('falls back to the releases page when GitHub is unreachable', function () {
    Http::fake(['https://api.github.com/*' => Http::response(null, 500)]);

    $this->get(route('plugin.download'))
        ->assertRedirect('https://github.com/acme/plugin/releases/latest');
});

it('shows the download button in the header', function () {
    Http::fake();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('plugin.download'), false)
        ->assertSee('Plugin Revit');
});
