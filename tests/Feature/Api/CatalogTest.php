<?php

use App\Models\Brand;
use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/families')->assertUnauthorized();
    $this->getJson('/api/v1/categories')->assertUnauthorized();
    $this->getJson('/api/v1/brands')->assertUnauthorized();
});

it('lists only active categories that have published families', function () {
    signIn();

    $doors = makeCategory('portas');
    $sub   = $doors->subcategories()->create(['name' => 'Pivotantes', 'slug' => 'pivotantes']);
    makeFamily(['category_id' => $doors->id, 'subcategory_id' => $sub->id]);

    makeCategory('vazia');

    $inactive = makeCategory('inativa', ['is_active' => false]);
    makeFamily(['category_id' => $inactive->id]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'portas')
        ->assertJsonPath('data.0.families_count', 1)
        ->assertJsonPath('data.0.subcategories.0.slug', 'pivotantes');
});

it('lists only active brands that have published families', function () {
    signIn();

    $brand = Brand::create(['name' => 'Tigre', 'slug' => 'tigre', 'country' => 'BR']);
    makeFamily(['brand_id' => $brand->id]);

    Brand::create(['name' => 'Sem Familias', 'slug' => 'sem-familias']);

    $this->getJson('/api/v1/brands')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'tigre');
});

it('lists published families with pagination', function () {
    signIn();

    makeFamily(['name' => 'Porta A']);
    makeFamily(['name' => 'Rascunho', 'status' => Family::STATUS_DRAFT]);

    $this->getJson('/api/v1/families')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonStructure([
            'data' => ['*' => [
                'id', 'slug', 'name', 'category', 'subcategory', 'brand', 'revit_version',
                'is_parametric', 'is_free', 'price', 'formatted_price', 'thumbnail_url',
            ]],
            'links',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);
});

it('filters, searches and sorts the list', function () {
    signIn();

    $brand   = Brand::create(['name' => 'Tigre', 'slug' => 'tigre']);
    $windows = makeCategory('janelas');

    makeFamily(['name' => 'Porta Pivotante', 'brand_id' => $brand->id]);
    makeFamily(['name' => 'Mesa de Jantar', 'is_free' => false, 'price' => 50, 'revit_version' => 2027]);
    makeFamily(['name' => 'Janela Alfa', 'category_id' => $windows->id]);

    $names = fn (string $query) => collect($this->getJson("/api/v1/families?{$query}")->assertOk()->json('data'))
        ->pluck('name')
        ->all();

    expect($names('q=pivotante'))->toBe(['Porta Pivotante']);
    expect($names('category=janelas'))->toBe(['Janela Alfa']);
    expect($names('brand=tigre'))->toBe(['Porta Pivotante']);
    expect($names('free=1'))->toEqualCanonicalizing(['Porta Pivotante', 'Janela Alfa']);
    expect($names('revit_version=2026'))->toEqualCanonicalizing(['Porta Pivotante', 'Janela Alfa']);
    expect($names('sort=name'))->toBe(['Janela Alfa', 'Mesa de Jantar', 'Porta Pivotante']);
});

it('validates the list filters', function () {
    signIn();

    $this->getJson('/api/v1/families?per_page=500')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    $this->getJson('/api/v1/families?sort=banana')->assertUnprocessable()->assertJsonValidationErrors('sort');
    $this->getJson('/api/v1/families?subcategory=x')->assertUnprocessable()->assertJsonValidationErrors('category');
});

it('shows a free family as downloadable', function () {
    signIn();

    $family = makeFamily(['name' => 'Porta A', 'description' => 'Texto']);
    $family->syncTags(['Porta Pivotante']);

    $this->getJson("/api/v1/families/{$family->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Porta A')
        ->assertJsonPath('data.tags', ['porta pivotante'])
        ->assertJsonPath('data.download.url', route('api.v1.families.download', ['family' => $family->slug]))
        ->assertJsonPath('data.download.allowed', true)
        ->assertJsonPath('data.download.purchase_url', null);
});

it('offers the purchase link for a paid family without access', function () {
    signIn();

    $family = makeFamily(['is_free' => false, 'price' => 80]);

    $this->getJson("/api/v1/families/{$family->slug}")
        ->assertOk()
        ->assertJsonPath('data.download.allowed', false)
        ->assertJsonPath('data.download.purchase_url', route('revit.families.purchase.show', ['family' => $family->slug]));
});

it('allows a paid family for an active subscriber', function () {
    signIn();
    subscribe($this->user);

    $family = makeFamily(['is_free' => false, 'price' => 80]);

    $this->getJson("/api/v1/families/{$family->slug}")
        ->assertOk()
        ->assertJsonPath('data.download.allowed', true);
});

it('hides unpublished families', function () {
    signIn();

    $draft = makeFamily(['status' => Family::STATUS_DRAFT]);

    $this->getJson("/api/v1/families/{$draft->slug}")->assertNotFound();
});