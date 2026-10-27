<?php

/*
 * Revit plugin releases. The source of truth is the GitHub Releases page of
 * the plugin repository: publishing a release there is all it takes for the
 * API to announce it (no manual registration on this side).
 */
return [

    // "owner/repository" of the public plugin repository.
    'github_repo' => env('PLUGIN_GITHUB_REPO', 'ronaldoaires/unset-bim-revit'),

    // Optional. Without a token GitHub allows 60 requests/hour per IP, which
    // is plenty with the cache below. A fine-grained token with no
    // permissions (public repositories only) raises it to 5000.
    'github_token' => env('PLUGIN_GITHUB_TOKEN'),

    // Release asset offered as the installer (first match wins).
    'asset_pattern' => env('PLUGIN_ASSET_PATTERN', '/\.(msi|exe)$/i'),

    // Oldest plugin version still accepted. Raise it only when an API change
    // breaks older versions: they are then told the update is required.
    'min_version' => env('PLUGIN_MIN_VERSION', '0.0.0'),

    // How long a successful answer from GitHub is reused.
    'cache_minutes' => (int) env('PLUGIN_CACHE_MINUTES', 15),

];
