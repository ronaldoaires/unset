<?php

use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication', function () {
    $family = makeFamily();

    $this->getJson('/api/v1/me/favorites')->assertUnauthorized();
    $this->postJson("/api/v1/families/{$family->slug}/favorite")->assertUnauthorized();
    $this->deleteJson("/api/v1/families/{$family->slug}/favorite")->assertUnauthorized();
});

it('adds a favorite only once', function () {
    signIn();
    $family = makeFamily();

    $this->postJson("/api/v1/families/{$family->slug}/favorite")
        ->assertOk()
        ->assertExactJson(['data' => ['slug' => $family->slug, 'is_favorite' => true]]);

    // Same request again: still one row, same answer.
    $this->postJson("/api/v1/families/{$family->slug}/favorite")->assertOk();

    $this->assertDatabaseCount('favorites', 1);
});

it('does not favorite an unpublished family', function () {
    signIn();
    $draft = makeFamily(['status' => Family::STATUS_DRAFT]);

    $this->postJson("/api/v1/families/{$draft->slug}/favorite")->assertNotFound();
    $this->postJson('/api/v1/families/nao-existe/favorite')->assertNotFound();

    $this->assertDatabaseCount('favorites', 0);
});

it('removes a favorite, even of an unpublished family, and repeating is fine', function () {
    signIn();
    $family = makeFamily();
    $this->user->favorites()->create(['family_id' => $family->id]);
    $family->update(['status' => Family::STATUS_DRAFT]);

    $this->deleteJson("/api/v1/families/{$family->slug}/favorite")
        ->assertOk()
        ->assertJsonPath('data.is_favorite', false);

    $this->deleteJson("/api/v1/families/{$family->slug}/favorite")->assertOk();

    $this->assertDatabaseCount('favorites', 0);
});

it('lists only the user own published favorites, newest first', function () {
    signIn();

    $first  = makeFamily(['name' => 'Primeira']);
    $second = makeFamily(['name' => 'Segunda']);
    $draft  = makeFamily(['name' => 'Rascunho', 'status' => Family::STATUS_DRAFT]);
    $other  = makeFamily(['name' => 'De outro']);

    $this->user->favorites()->create(['family_id' => $first->id]);
    $this->travel(1)->minutes();
    $this->user->favorites()->create(['family_id' => $second->id]);
    $this->user->favorites()->create(['family_id' => $draft->id]);
    User::factory()->create()->favorites()->create(['family_id' => $other->id]);

    $this->getJson('/api/v1/me/favorites')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.name', 'Segunda')
        ->assertJsonPath('data.0.is_favorite', true)
        ->assertJsonPath('data.1.name', 'Primeira')
        ->assertJsonStructure(['data' => [['slug', 'favorited_at']], 'links', 'meta']);
});

it('validates the page parameters', function () {
    signIn();

    $this->getJson('/api/v1/me/favorites?per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('marks favorites in the catalog', function () {
    signIn();

    $liked = makeFamily(['name' => 'Curtida']);
    makeFamily(['name' => 'Outra']);
    $this->user->favorites()->create(['family_id' => $liked->id]);

    $flags = collect($this->getJson('/api/v1/families')->assertOk()->json('data'))
        ->pluck('is_favorite', 'name')
        ->all();

    expect($flags)->toBe(['Outra' => false, 'Curtida' => true]);

    $this->getJson("/api/v1/families/{$liked->slug}")->assertJsonPath('data.is_favorite', true);
});
