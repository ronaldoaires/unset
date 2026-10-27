<?php

use Illuminate\Support\Facades\Http;

const GITHUB_LATEST = 'https://api.github.com/repos/acme/plugin/releases/latest';

beforeEach(function () {
    config([
        'plugin.github_repo'   => 'acme/plugin',
        'plugin.min_version'   => '1.0.0',
        'plugin.cache_minutes' => 15,
    ]);

    Http::preventStrayRequests();
});

function fakeGithubRelease(string $tag = 'v1.4.0'): void
{
    Http::fake([GITHUB_LATEST => Http::response(githubReleasePayload($tag))]);
}

function githubReleasePayload(string $tag = 'v1.4.0'): array
{
    return [
        'tag_name'     => $tag,
        'name'         => "UnSet BIM {$tag}",
        'body'         => '- Correções',
        'html_url'     => "https://github.com/acme/plugin/releases/tag/{$tag}",
        'published_at' => '2026-10-27T18:00:00Z',
        'assets'       => [
            ['name' => 'checksums.txt', 'size' => 100, 'browser_download_url' => 'https://example.com/checksums.txt'],
            [
                'name'                 => 'UnSetBIM-Setup.msi',
                'size'                 => 5242880,
                'digest'               => 'sha256:abc123',
                'browser_download_url' => 'https://github.com/acme/plugin/releases/download/v1.4.0/UnSetBIM-Setup.msi',
            ],
        ],
    ];
}

it('is public and returns the latest release with its installer', function () {
    fakeGithubRelease();

    $this->getJson('/api/v1/plugin/latest')
        ->assertOk()
        ->assertJsonPath('data.version', '1.4.0')
        ->assertJsonPath('data.file_name', 'UnSetBIM-Setup.msi')
        ->assertJsonPath('data.sha256', 'abc123')
        ->assertJsonPath('data.min_version', '1.0.0')
        ->assertJsonPath('data.current', null)
        ->assertJsonPath('data.update_available', null);
});

it('compares the current version', function () {
    fakeGithubRelease();

    $this->getJson('/api/v1/plugin/latest?current=1.4.0')
        ->assertJsonPath('data.update_available', false)
        ->assertJsonPath('data.update_required', false);

    $this->getJson('/api/v1/plugin/latest?current=v1.2.3')
        ->assertJsonPath('data.current', '1.2.3')
        ->assertJsonPath('data.update_available', true)
        ->assertJsonPath('data.update_required', false);

    $this->getJson('/api/v1/plugin/latest?current=0.9')
        ->assertJsonPath('data.update_required', true);
});

it('validates the current version', function () {
    $this->getJson('/api/v1/plugin/latest?current=banana')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current');
});

it('asks GitHub only once per cache window', function () {
    fakeGithubRelease();

    $this->getJson('/api/v1/plugin/latest')->assertOk();
    $this->getJson('/api/v1/plugin/latest')->assertOk();

    Http::assertSentCount(1);
});

it('keeps serving the last good release when GitHub fails', function () {
    // First call succeeds, the next one fails.
    Http::fake([GITHUB_LATEST => Http::sequence()
        ->push(githubReleasePayload('v1.4.0'))
        ->push(['message' => 'Server Error'], 500)]);

    $this->getJson('/api/v1/plugin/latest')->assertOk();

    // The cache window is over, so GitHub is asked again (and fails).
    $this->travel(16)->minutes();

    $this->getJson('/api/v1/plugin/latest')
        ->assertOk()
        ->assertJsonPath('data.version', '1.4.0');

    Http::assertSentCount(2);
});

it('answers 503 when GitHub never answered', function () {
    Http::fake([GITHUB_LATEST => Http::response([], 500)]);

    $this->getJson('/api/v1/plugin/latest')
        ->assertStatus(503)
        ->assertJsonPath('code', 'update_info_unavailable');
});
