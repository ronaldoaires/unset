<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Latest published release of the Revit plugin, read from GitHub Releases.
 *
 * GitHub is called at most once per cache window. If it is unreachable, the
 * last good answer keeps being served, so a GitHub outage never breaks the
 * plugin's update check.
 */
class PluginReleaseService
{
    private const FRESH_KEY     = 'plugin.release.fresh';
    private const LAST_GOOD_KEY = 'plugin.release.last_good';
    private const BACKOFF_KEY   = 'plugin.release.backoff';

    /** Seconds to wait before asking GitHub again after a failure. */
    private const BACKOFF_SECONDS = 60;

    /**
     * @return array<string, mixed>|null  null only when GitHub never answered.
     */
    public function latest(): ?array
    {
        if ($release = Cache::get(self::FRESH_KEY)) {
            return $release;
        }

        if (! Cache::has(self::BACKOFF_KEY)) {
            $release = $this->fetch();

            if ($release) {
                Cache::put(self::FRESH_KEY, $release, now()->addMinutes(config('plugin.cache_minutes')));
                Cache::forever(self::LAST_GOOD_KEY, $release);

                return $release;
            }

            // Failed: do not hammer GitHub (or wait on its timeout) on every request.
            Cache::put(self::BACKOFF_KEY, true, self::BACKOFF_SECONDS);
        }

        return Cache::get(self::LAST_GOOD_KEY);
    }

    /**
     * Forgets the cached answer, so the next call reads GitHub again.
     */
    public function refresh(): void
    {
        Cache::forget(self::FRESH_KEY);
        Cache::forget(self::BACKOFF_KEY);
    }

    public function minVersion(): string
    {
        return $this->normalize((string) config('plugin.min_version'));
    }

    /**
     * "v1.2.0" and "1.2.0" are the same version.
     */
    public function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetch(): ?array
    {
        $repo  = config('plugin.github_repo');
        $token = config('plugin.github_token');

        try {
            // "latest" already skips drafts and pre-releases.
            $response = Http::acceptJson()
                ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
                ->withUserAgent(config('app.name') . ' update check')
                ->when($token, fn ($request) => $request->withToken($token))
                ->timeout(5)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || ! $response->json('tag_name')) {
            return null;
        }

        $asset = collect($response->json('assets', []))
            ->first(fn (array $asset) => preg_match(config('plugin.asset_pattern'), $asset['name'] ?? '') === 1);

        // GitHub publishes the SHA-256 of each asset as "sha256:<hex>".
        $digest = $asset['digest'] ?? null;

        return [
            'version'      => $this->normalize($response->json('tag_name')),
            'name'         => $response->json('name'),
            'notes'        => $response->json('body'),
            'released_at'  => $response->json('published_at'),
            'release_url'  => $response->json('html_url'),
            'download_url' => $asset['browser_download_url'] ?? null,
            'file_name'    => $asset['name'] ?? null,
            'size_bytes'   => $asset['size'] ?? null,
            'sha256'       => is_string($digest) && str_starts_with($digest, 'sha256:') ? substr($digest, 7) : null,
        ];
    }
}
