<?php

use App\Models\Download;
use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function recordDownload(User $user, Family $family, $at): void
{
    Download::create([
        'user_id'       => $user->id,
        'family_id'     => $family->id,
        'ip_address'    => '127.0.0.1',
        'downloaded_at' => $at,
    ]);
}

it('requires authentication', function () {
    $this->getJson('/api/v1/me/downloads')->assertUnauthorized();
});

it('lists one row per family, most recent first, only the user own published ones', function () {
    signIn();

    $door   = makeFamily(['name' => 'Porta']);
    $window = makeFamily(['name' => 'Janela']);
    $draft  = makeFamily(['name' => 'Rascunho', 'status' => Family::STATUS_DRAFT]);
    $other  = makeFamily(['name' => 'De outro']);

    recordDownload($this->user, $door, now()->subDays(3));
    recordDownload($this->user, $window, now()->subDays(2));
    recordDownload($this->user, $door, now()->subDay());
    recordDownload($this->user, $draft, now());
    recordDownload(User::factory()->create(), $other, now());

    $this->getJson('/api/v1/me/downloads')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.name', 'Porta')
        ->assertJsonPath('data.0.downloads_count', 2)
        ->assertJsonPath('data.1.name', 'Janela')
        ->assertJsonPath('data.1.downloads_count', 1)
        ->assertJsonStructure(['data' => [['slug', 'last_downloaded_at', 'downloads_count', 'is_favorite']]]);
});

it('records a real download in the history', function () {
    Illuminate\Support\Facades\Storage::fake('local');
    signIn();

    $family = attachRfaFile(makeFamily(['name' => 'Baixada']));

    $this->get("/api/v1/families/{$family->slug}/download")->assertOk();

    $this->getJson('/api/v1/me/downloads')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Baixada')
        ->assertJsonPath('data.0.downloads_count', 1);
});
