<?php

use App\Filament\Resources\Textures\Pages\CreateTexture;
use App\Filament\Resources\Textures\Pages\ListTextures;
use App\Models\Texture;
use App\Models\TextureCategory;
use App\Models\TextureMap;
use App\Models\User;
use App\Services\TextureMapService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
    $this->category = TextureCategory::create(['name' => 'Pedra', 'slug' => 'pedra']);
});

function makeTexture(array $overrides = []): Texture
{
    return Texture::create(array_merge([
        'user_id'             => test()->admin->id,
        'texture_category_id' => test()->category->id,
        'name'                => 'Pedra rústica',
        'slug'                => 'pedra-rustica',
        'tile_width_mm'       => 600,
        'tile_height_mm'      => 600,
    ], $overrides))->fresh();
}

it('guesses the map type from common file names', function (string $name, ?string $type) {
    expect(TextureMap::guessType($name))->toBe($type);
})->with([
    ['diffuse.png', TextureMap::TYPE_DIFFUSE],
    ['Stone_BaseColor_2K.jpg', TextureMap::TYPE_DIFFUSE],
    ['rock_nor_gl_1k.png', TextureMap::TYPE_NORMAL],
    ['Wall_Roughness.png', TextureMap::TYPE_ROUGHNESS],
    ['brick_ao.jpg', TextureMap::TYPE_AO],
    ['displacement.png', TextureMap::TYPE_DISPLACEMENT],
    ['leaf_opacity.png', TextureMap::TYPE_ALPHA],
    ['photo.png', null],
]);

it('stores maps by type and resolution and derives color and preview', function () {
    $texture = makeTexture();

    $result = app(TextureMapService::class)->storeMany($texture, [
        UploadedFile::fake()->image('diffuse.png', 1024, 1024),
        UploadedFile::fake()->image('normal.png', 2048, 2048),
        UploadedFile::fake()->image('photo.png', 64, 64),
    ]);

    $texture->refresh();

    expect($result['skipped'])->toBe(['photo.png'])
        ->and($texture->maps()->pluck('resolution', 'type')->all())
        ->toBe([TextureMap::TYPE_DIFFUSE => '1k', TextureMap::TYPE_NORMAL => '2k'])
        ->and($texture->shading_color)->toMatch('/^#[0-9a-f]{6}$/')
        ->and($texture->preview_path)->toBe('textures/previews/pedra-rustica.webp')
        ->and($texture->availableResolutions())->toBe(['1k']);

    Storage::disk('local')->assertExists('textures/pedra-rustica/1k/diffuse.png');
    Storage::disk('public')->assertExists('textures/previews/thumbs/pedra-rustica.webp');
});

it('replaces a map of the same type and resolution and bumps the version', function () {
    $texture  = makeTexture();
    $service  = app(TextureMapService::class);

    $service->store($texture, UploadedFile::fake()->image('a.png', 1024, 1024), TextureMap::TYPE_DIFFUSE);
    $version = $texture->fresh()->version;

    $service->store($texture->fresh(), UploadedFile::fake()->image('b.jpg', 1024, 1024), TextureMap::TYPE_DIFFUSE);

    expect($texture->maps()->count())->toBe(1)
        ->and($texture->maps()->first()->path)->toBe('textures/pedra-rustica/1k/diffuse.jpg')
        ->and($texture->fresh()->version)->toBe($version + 1);

    Storage::disk('local')->assertMissing('textures/pedra-rustica/1k/diffuse.png');
});

it('keeps a custom cover when the diffuse map changes', function () {
    $texture = makeTexture();
    $service = app(TextureMapService::class);

    $service->storeCover($texture, UploadedFile::fake()->image('capa.jpg', 800, 600));
    $service->store($texture->fresh(), UploadedFile::fake()->image('diffuse.png', 1024, 1024), TextureMap::TYPE_DIFFUSE);

    expect($texture->fresh()->preview_path)->toStartWith('textures/covers/');
});

it('lets everyone download free textures and only subscribers the others', function () {
    $user = User::factory()->create();
    $free = makeTexture();
    $paid = makeTexture(['slug' => 'pedra-paga', 'is_free' => false]);

    expect(Gate::forUser($user)->inspect('download', $free)->allowed())->toBeTrue();

    $denied = Gate::forUser($user)->inspect('download', $paid);
    expect($denied->allowed())->toBeFalse()
        ->and($denied->code())->toBe('subscription_required');

    subscribe($user);
    expect(Gate::forUser($user->fresh())->inspect('download', $paid)->allowed())->toBeTrue();
});

it('blocks publishing an image texture without a diffuse map', function () {
    expect(makeTexture()->publishBlocker())->not->toBeNull()
        ->and(makeTexture(['slug' => 'vidro', 'material_type' => Texture::TYPE_GLASS, 'base_color' => '#88aacc'])->publishBlocker())->toBeNull();
});

it('creates a texture with its maps from the admin form', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateTexture::class)
        ->fillForm([
            'name'                => 'Tijolo aparente',
            'texture_category_id' => $this->category->id,
            'material_type'       => Texture::TYPE_PBR,
            'tile_width_mm'       => 1200,
            'tile_height_mm'      => 800,
            'status'              => Texture::STATUS_PUBLISHED,
            'maps_upload'         => [
                UploadedFile::fake()->image('tijolo_diffuse.png', 1024, 1024),
                UploadedFile::fake()->image('tijolo_roughness.png', 1024, 1024),
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $texture = Texture::where('slug', 'tijolo-aparente')->firstOrFail();

    expect($texture->is_free)->toBeTrue()
        ->and($texture->isPublished())->toBeTrue()
        ->and($texture->version)->toBe(1)
        ->and($texture->maps()->count())->toBe(2);
});

it('saves an image texture without maps as a draft', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateTexture::class)
        ->fillForm([
            'name'                => 'Sem mapas',
            'texture_category_id' => $this->category->id,
            'material_type'       => Texture::TYPE_PBR,
            'tile_width_mm'       => 1000,
            'tile_height_mm'      => 1000,
            'status'              => Texture::STATUS_PUBLISHED,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Texture::where('slug', 'sem-mapas')->value('status'))->toBe(Texture::STATUS_DRAFT);
});

it('renders the texture list for admins', function () {
    $this->actingAs($this->admin);
    makeTexture();

    Livewire::test(ListTextures::class)->assertOk()->assertSee('Pedra rústica');
});

it('renders the edit page and uploads maps from the maps tab', function () {
    $this->actingAs($this->admin);
    $texture = makeTexture();

    Livewire::test(\App\Filament\Resources\Textures\Pages\EditTexture::class, ['record' => $texture->getRouteKey()])
        ->assertOk();

    Livewire::test(\App\Filament\Resources\Textures\RelationManagers\TextureMapsRelationManager::class, [
        'ownerRecord' => $texture,
        'pageClass'   => \App\Filament\Resources\Textures\Pages\EditTexture::class,
    ])
        ->assertOk()
        ->callAction(\Filament\Actions\Testing\TestAction::make('uploadMany')->table(), [
            'files' => [UploadedFile::fake()->image('diffuse.png', 2048, 2048)],
        ])
        ->assertHasNoFormErrors();

    expect($texture->maps()->value('resolution'))->toBe('2k')
        ->and($texture->fresh()->version)->toBe(2);
});

it('creates a texture category from the texture form', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateTexture::class)
        ->callFormComponentAction('texture_category_id', 'createOption', data: ['name' => 'Madeira'])
        ->assertHasNoFormComponentActionErrors();

    expect(TextureCategory::where('slug', 'madeira')->exists())->toBeTrue();
});

it('removes the map folders from the private disk', function () {
    $texture = makeTexture();
    $service = app(TextureMapService::class);

    $service->storeMany($texture, [
        UploadedFile::fake()->image('diffuse.png', 1024, 1024),
        UploadedFile::fake()->image('normal.png', 2048, 2048),
    ]);

    // Deleting the only 2k map drops the empty 2k folder, keeps 1k.
    $service->delete($texture->maps()->where('resolution', '2k')->first());

    expect(Storage::disk('local')->directories('textures/pedra-rustica'))
        ->toBe(['textures/pedra-rustica/1k']);

    // Deleting the texture drops its whole folder.
    $texture->fresh()->delete();

    expect(Storage::disk('local')->exists('textures/pedra-rustica'))->toBeFalse()
        ->and(TextureMap::count())->toBe(0);
});
