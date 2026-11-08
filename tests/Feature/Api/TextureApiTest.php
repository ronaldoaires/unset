<?php

use App\Models\Texture;
use App\Models\TextureCategory;
use App\Models\User;
use App\Services\TextureMapService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->user = User::factory()->create();
    signIn();

    $this->stone = TextureCategory::create(['name' => 'Pedra', 'slug' => 'pedra']);
});

/** A published texture with the given maps (file name => size in px). */
function publishedTexture(array $overrides = [], array $maps = ['diffuse.png' => 1024, 'normal.png' => 1024]): Texture
{
    static $n = 0;
    $n++;

    $texture = Texture::create(array_merge([
        'user_id'             => test()->user->id,
        'texture_category_id' => test()->stone->id,
        'name'                => "Pedra {$n}",
        'slug'                => "pedra-{$n}",
        'tile_width_mm'       => 600,
        'tile_height_mm'      => 400,
        'status'              => Texture::STATUS_PUBLISHED,
    ], $overrides));

    $files = collect($maps)->map(fn($px, $name) => UploadedFile::fake()->image($name, $px, $px))->values()->all();

    if ($files) {
        app(TextureMapService::class)->storeMany($texture, $files);
    }

    return $texture->fresh();
}

function zipEntries(string $binary): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'tex');
    file_put_contents($tmp, $binary);

    $zip = new ZipArchive();
    $zip->open($tmp);

    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name           = $zip->getNameIndex($i);
        $entries[$name] = $zip->getFromName($name);
    }

    $zip->close();
    unlink($tmp);

    return $entries;
}

it('lists only published textures with filters', function () {
    $free = publishedTexture();
    publishedTexture(['is_free' => false]);
    publishedTexture(['status' => Texture::STATUS_DRAFT]);

    $this->getJson('/api/v1/textures')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['slug', 'name', 'category', 'material_type', 'tile_width_mm', 'tile_height_mm', 'is_free', 'thumbnail_url', 'version']], 'meta', 'links']);

    $this->getJson('/api/v1/textures?free=1&category=pedra')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $free->slug);

    $this->getJson('/api/v1/textures?material_type=wood')->assertUnprocessable();
});

it('lists categories that have published textures', function () {
    publishedTexture();
    TextureCategory::create(['name' => 'Madeira', 'slug' => 'madeira']);

    $this->getJson('/api/v1/texture-categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'pedra')
        ->assertJsonPath('data.0.textures_count', 1);
});

it('shows the detail with resolutions and the download decision', function () {
    $texture = publishedTexture([], ['diffuse.png' => 1024, 'normal.png' => 1024, 'diffuse_2k.png' => 2048]);

    $this->getJson("/api/v1/textures/{$texture->slug}")
        ->assertOk()
        ->assertJsonPath('data.tile_width_mm', 600)
        ->assertJsonPath('data.needs_download', true)
        ->assertJsonPath('data.resolutions.0.resolution', '1k')
        ->assertJsonPath('data.resolutions.0.maps', ['diffuse', 'normal'])
        ->assertJsonPath('data.resolutions.1.resolution', '2k')
        // The 2k package borrows the 1k normal map.
        ->assertJsonPath('data.resolutions.1.maps', ['diffuse', 'normal'])
        ->assertJsonPath('data.default_resolution', '2k')
        ->assertJsonPath('data.download.allowed', true)
        ->assertJsonPath('data.download.code', null);
});

it('denies a subscriber texture to a user without a plan', function () {
    $texture = publishedTexture(['is_free' => false]);

    $this->getJson("/api/v1/textures/{$texture->slug}")
        ->assertOk()
        ->assertJsonPath('data.download.allowed', false)
        ->assertJsonPath('data.download.code', 'subscription_required');

    $this->getJson("/api/v1/textures/{$texture->slug}/download")
        ->assertForbidden()
        ->assertJsonPath('code', 'subscription_required');

    $this->assertDatabaseCount('texture_downloads', 0);
});

it('lets any subscriber download a subscriber texture', function () {
    subscribe($this->user, makePlan(['features' => ['modules' => []]]));
    $texture = publishedTexture(['is_free' => false]);

    $this->get("/api/v1/textures/{$texture->slug}/download")->assertOk();
});

it('downloads a zip with the maps and a manifest, and records it', function () {
    $texture = publishedTexture();

    $response = $this->get("/api/v1/textures/{$texture->slug}/download?resolution=1k")
        ->assertOk()
        ->assertDownload("{$texture->slug}-1k.zip")
        ->assertHeader('X-Texture-Resolution', '1k')
        ->assertHeader('X-Texture-Version', '1');

    $entries  = zipEntries($response->streamedContent());
    $manifest = json_decode($entries['texture.json'], true);

    expect(array_keys($entries))->toEqualCanonicalizing(['diffuse.png', 'normal.png', 'texture.json'])
        ->and($manifest['tile_width_mm'])->toBe(600)
        ->and($manifest['maps'])->toBe(['diffuse' => 'diffuse.png', 'normal' => 'normal.png']);

    $this->assertDatabaseHas('texture_downloads', [
        'user_id' => $this->user->id, 'texture_id' => $texture->id, 'resolution' => '1k', 'version' => 1,
    ]);
    expect($texture->fresh()->download_count)->toBe(1);
});

it('serves the best resolution available up to the requested one', function () {
    $texture = publishedTexture();

    $this->get("/api/v1/textures/{$texture->slug}/download?resolution=8k")
        ->assertOk()
        ->assertHeader('X-Texture-Resolution', '1k');

    $this->getJson("/api/v1/textures/{$texture->slug}/download?resolution=16k")->assertUnprocessable();
});

it('counts each user once and rebuilds the package after the maps change', function () {
    $texture = publishedTexture();

    $this->get("/api/v1/textures/{$texture->slug}/download")->assertOk();
    Storage::disk('local')->assertExists("texture-packages/{$texture->slug}/v1-1k.zip");

    app(TextureMapService::class)->store($texture, UploadedFile::fake()->image('roughness.png', 1024, 1024), 'roughness');
    Storage::disk('local')->assertMissing("texture-packages/{$texture->slug}/v1-1k.zip");

    $response = $this->get("/api/v1/textures/{$texture->slug}/download")
        ->assertOk()
        ->assertHeader('X-Texture-Version', '2');

    expect(array_keys(zipEntries($response->streamedContent())))->toContain('roughness.png')
        ->and($texture->fresh()->download_count)->toBe(1);

    $this->assertDatabaseCount('texture_downloads', 2);
});

it('answers 404 for drafts and textures without files', function () {
    $draft = publishedTexture(['status' => Texture::STATUS_DRAFT]);
    $glass = publishedTexture(['material_type' => Texture::TYPE_GLASS, 'base_color' => '#88aacc'], []);

    $this->getJson("/api/v1/textures/{$draft->slug}")->assertNotFound();
    $this->getJson("/api/v1/textures/{$draft->slug}/download")->assertNotFound();

    $this->getJson("/api/v1/textures/{$glass->slug}")
        ->assertOk()
        ->assertJsonPath('data.needs_download', false)
        ->assertJsonPath('data.resolutions', [])
        ->assertJsonPath('data.default_resolution', null);

    $this->getJson("/api/v1/textures/{$glass->slug}/download")->assertNotFound();
});

