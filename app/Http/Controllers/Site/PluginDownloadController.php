<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\PluginReleaseService;
use Illuminate\Http\RedirectResponse;

/**
 * "Baixar plugin": sends the visitor to the installer of the latest plugin release.
 *
 * Uses the same cached GitHub release as GET /api/v1/plugin/latest, so the site
 * button and the plugin's update check always point to the same version.
 * Public: people download the plugin first and sign in from inside Revit.
 */
class PluginDownloadController extends Controller
{
    public function __invoke(PluginReleaseService $releases): RedirectResponse
    {
        $release = $releases->latest();

        // Installer asset > release page > releases list (GitHub unreachable and nothing cached).
        $url = $release['download_url']
            ?? $release['release_url']
            ?? 'https://github.com/' . config('plugin.github_repo') . '/releases/latest';

        return redirect()->away($url);
    }
}
