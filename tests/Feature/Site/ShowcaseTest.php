<?php

use App\Enums\ServiceArea;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\Service;

/*
|--------------------------------------------------------------------------
| Public services and portfolio pages
|--------------------------------------------------------------------------
*/

function showcaseService(array $overrides = []): Service
{
    return Service::create(array_merge([
        'area'         => ServiceArea::Engineering,
        'name'         => 'Projeto Estrutural',
        'slug'         => 'projeto-estrutural',
        'headline'     => 'Estrutura segura e econômica',
        'summary'      => 'Cálculo e detalhamento de estruturas de concreto armado.',
        'body'         => '<h2>Por que contratar</h2><p>Texto <strong>forte</strong>.</p><script>alert(1)</script>',
        'deliverables' => ['Planta de formas', 'Detalhamento de armaduras'],
        'faq'          => [['question' => 'Quanto tempo leva?', 'answer' => 'Depende da obra.']],
        'is_active'    => true,
    ], $overrides));
}

function showcaseProject(Service $service, array $overrides = []): Project
{
    return Project::create(array_merge([
        'service_id' => $service->id,
        'title'      => 'Sobrado 10x16',
        'slug'       => 'sobrado-10x16',
        'summary'    => 'Projeto estrutural de sobrado com laje treliçada.',
        'cover_path' => 'projects/sobrado-10x16.webp',
        'location'   => 'Nova Xavantina/MT',
        'year'       => 2026,
        'built_area' => 160,
        'is_active'  => true,
    ], $overrides));
}

it('renders the services hub, the area page and the service page', function () {
    $service = showcaseService();
    showcaseProject($service, ['is_featured' => true]);

    $this->get('/servicos')->assertOk()->assertSee('Projeto Estrutural')->assertSee('Sobrado 10x16');

    $this->get('/servicos/engenharia')->assertOk()->assertSee('Serviços de engenharia');

    $this->get('/servicos/engenharia/projeto-estrutural')
        ->assertOk()
        ->assertSee('Estrutura segura e econômica')
        ->assertSee('Detalhamento de armaduras')
        ->assertSee('Quanto tempo leva?')
        ->assertSee('CREA-MT 62629')
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('"@type":"Service"', false)
        // The rich text goes through Filament's renderer: markup kept, scripts dropped.
        ->assertSee('<strong>forte</strong>', false)
        ->assertDontSee('<script>alert(1)</script>', false);

    expect($service->fresh()->view_count)->toBe(1);
});

it('renders a service without FAQ, deliverables or cover', function () {
    showcaseService(['faq' => null, 'deliverables' => null, 'body' => null, 'headline' => null]);

    $this->get('/servicos/engenharia/projeto-estrutural')
        ->assertOk()
        ->assertDontSee('"@type":"FAQPage"', false);
});

it('hides inactive services and empty areas', function () {
    showcaseService(['is_active' => false]);

    $this->get('/servicos/engenharia/projeto-estrutural')->assertNotFound();
    $this->get('/servicos/engenharia')->assertNotFound();
    $this->get('/servicos/tecnologia')->assertNotFound();
    $this->get('/servicos/qualquer-coisa')->assertNotFound();
});

it('redirects a service opened under the wrong area', function () {
    showcaseService();

    $this->get('/servicos/tecnologia/projeto-estrutural')
        ->assertMovedPermanently()
        ->assertRedirect('/servicos/engenharia/projeto-estrutural');
});

it('lists the portfolio with shareable filters', function () {
    $structural = showcaseService();
    $website    = showcaseService([
        'area' => ServiceArea::Technology, 'name' => 'Criação de Sites', 'slug' => 'criacao-de-sites',
    ]);

    showcaseProject($structural);
    showcaseProject($website, ['title' => 'Site da Igreja', 'slug' => 'site-da-igreja']);

    $this->get('/portfolio')->assertOk()->assertSee('Sobrado 10x16')->assertSee('Site da Igreja');

    $this->get('/portfolio?area=tecnologia')
        ->assertOk()
        ->assertSee('Site da Igreja')
        ->assertDontSee('Sobrado 10x16')
        ->assertSee('noindex,follow', false);

    $this->get('/portfolio?servico=projeto-estrutural')->assertOk()->assertSee('Sobrado 10x16')->assertDontSee('Site da Igreja');
});

it('renders a project with its gallery and hides inactive ones', function () {
    $project = showcaseProject(showcaseService());

    ProjectImage::create([
        'project_id' => $project->id,
        'path'       => 'projects/gallery/sobrado-10x16-1.webp',
        'alt'        => 'Fachada do sobrado',
        'sort_order' => 1,
    ]);

    $this->get('/portfolio/sobrado-10x16')
        ->assertOk()
        ->assertSee('Fachada do sobrado')
        ->assertSee('Nova Xavantina/MT')
        ->assertSee('160,00 m²')
        ->assertSee('"@type":"CreativeWork"', false);

    $project->update(['is_active' => false]);

    $this->get('/portfolio/sobrado-10x16')->assertNotFound();
});

it('stamps the publication date the first time a project goes live', function () {
    $project = showcaseProject(showcaseService(), ['is_active' => false]);

    expect($project->published_at)->toBeNull();

    $project->update(['is_active' => true]);

    expect($project->fresh()->published_at)->not->toBeNull();
});

it('lists services and projects in the sitemap', function () {
    showcaseProject(showcaseService());
    showcaseService(['name' => 'Rascunho', 'slug' => 'rascunho', 'is_active' => false]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(url('/servicos/engenharia/projeto-estrutural'), false)
        ->assertSee(url('/portfolio/sobrado-10x16'), false)
        ->assertDontSee('rascunho', false);
});
