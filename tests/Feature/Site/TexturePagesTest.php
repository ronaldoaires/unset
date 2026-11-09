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

    $this->user  = User::factory()->create();
    $this->stone = TextureCategory::create(['name' => 'Pedra', 'slug' => 'pedra']);
});

function siteTexture(array $overrides = []): Texture
{
    static $n = 0;
    $n++;

    $texture = Texture::create(array_merge([
        'user_id'             => test()->user->id,
        'texture_category_id' => test()->stone->id,
        'name'                => "Pedra {$n}",
        'slug'                => "pedra-{$n}",
        'tile_width_mm'       => 600,
        'tile_height_mm'      => 600,
        'status'              => Texture::STATUS_PUBLISHED,
    ], $overrides));

    app(TextureMapService::class)->storeMany($texture, [UploadedFile::fake()->image('diffuse.png', 1024, 1024)]);

    return $texture->fresh();
}

it('shows the launch announcement on every public page', function () {
    $this->get('/')->assertOk()->assertSee('Portal em lançamento', false)->assertSee('Baixar plugin grátis');
    $this->get(route('revit.families.index'))->assertOk()->assertSee('Portal em lançamento', false);
});

it('hides the announcement when switched off', function () {
    config(['site.announcement.enabled' => false]);

    $this->get('/')->assertOk()->assertDontSee('Portal em lançamento', false);
});

it('lists published textures with the plugin highlight and the menu link', function () {
    $published = siteTexture(['name' => 'Granito cinza']);
    siteTexture(['name' => 'Rascunho', 'status' => Texture::STATUS_DRAFT]);

    $this->get(route('revit.textures.index'))
        ->assertOk()
        ->assertSee('Texturas PBR')
        ->assertSee('Textura no Revit em 3 cliques', false)
        ->assertSee('Granito cinza')
        ->assertDontSee('Rascunho')
        ->assertSee('60 × 60 cm', false)
        ->assertSee(route('revit.textures.show', ['texture' => $published->slug]));
});

it('filters by free and by type', function () {
    siteTexture(['name' => 'Pedra grátis']);
    siteTexture(['name' => 'Pedra paga', 'is_free' => false]);

    $this->get(route('revit.textures.index', ['gratuita' => 1]))
        ->assertOk()
        ->assertSee('Pedra grátis')
        ->assertDontSee('Pedra paga');

    $this->get(route('revit.textures.index', ['tipo' => 'glass']))
        ->assertOk()
        ->assertSee('Nenhuma textura encontrada');
});

it('shows a texture page with download buttons per resolution for signed-in users', function () {
    $texture = siteTexture(['name' => 'Ardósia']);

    $this->get(route('revit.textures.show', ['texture' => $texture->slug]))
        ->assertOk()
        ->assertSee('Ardósia')
        ->assertSee('600 × 600 mm', false)
        ->assertSee('Entrar para baixar');

    $this->actingAs($this->user)
        ->get(route('revit.textures.show', ['texture' => $texture->slug]))
        ->assertOk()
        ->assertSee(route('revit.textures.download', ['texture' => $texture->slug, 'resolucao' => '1k']), false);
});

it('downloads the zip from the site and records it', function () {
    $texture = siteTexture();

    $this->actingAs($this->user)
        ->get(route('revit.textures.download', ['texture' => $texture->slug, 'resolucao' => '1k']))
        ->assertOk()
        ->assertDownload("{$texture->slug}-1k.zip");

    $this->assertDatabaseHas('texture_downloads', ['user_id' => $this->user->id, 'texture_id' => $texture->id]);
});

it('asks for a plan on subscriber textures', function () {
    $texture = siteTexture(['is_free' => false]);

    $this->actingAs($this->user)
        ->get(route('revit.textures.show', ['texture' => $texture->slug]))
        ->assertOk()
        ->assertSee('Esta textura é exclusiva para assinantes.')
        ->assertSee('Ver planos');

    $this->actingAs($this->user)
        ->get(route('revit.textures.download', ['texture' => $texture->slug]))
        ->assertForbidden();
});

it('hides drafts and lists textures in the sitemap', function () {
    $draft = siteTexture(['status' => Texture::STATUS_DRAFT]);
    $live  = siteTexture();

    $this->get(route('revit.textures.show', ['texture' => $draft->slug]))->assertNotFound();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('revit.textures.show', ['texture' => $live->slug]), false)
        ->assertDontSee(route('revit.textures.show', ['texture' => $draft->slug]), false);
});
