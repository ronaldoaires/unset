<?php

namespace App\Console\Commands;

use App\Services\PluginReleaseService;
use Illuminate\Console\Command;

class RefreshPluginRelease extends Command
{
    protected $signature = 'plugin:refresh';

    protected $description = 'Reads the latest plugin release from GitHub now, instead of waiting for the cache to expire';

    public function handle(PluginReleaseService $releases): int
    {
        $releases->refresh();

        $release = $releases->latest();

        if (! $release) {
            $this->error('GitHub did not answer and there is no previous release cached.');

            return self::FAILURE;
        }

        $this->info("Latest plugin release: {$release['version']}");
        $this->line('Installer: ' . ($release['download_url'] ?? 'no asset matched config("plugin.asset_pattern")'));

        return self::SUCCESS;
    }
}
