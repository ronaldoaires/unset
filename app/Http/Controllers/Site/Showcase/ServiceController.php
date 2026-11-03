<?php

namespace App\Http\Controllers\Site\Showcase;

use App\Enums\ServiceArea;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ServiceController extends Controller
{
    /**
     * /servicos: hub with both areas and the featured portfolio.
     */
    public function index(): View
    {
        $services = Service::active()->withCount('activeProjects')->get()->groupBy(fn (Service $service) => $service->area->value);

        $projects = Project::active()
            ->featured()
            ->with('service')
            ->whereHas('service', fn ($query) => $query->where('is_active', true))
            ->limit(6)
            ->get();

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Serviços'],
        ];

        $url = route('services.index');

        return view('site.showcase.services.index', [
            'areas'       => collect(ServiceArea::cases())->filter(fn (ServiceArea $area) => $services->has($area->value)),
            'services'    => $services,
            'projects'    => $projects,
            'breadcrumbs' => $breadcrumbs,
            'jsonLd'      => StructuredData::graph(
                StructuredData::organization(),
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::itemList('Serviços', $url, null, $services->flatten()->map(fn (Service $service) => [
                    'name' => $service->name,
                    'url'  => $this->serviceUrl($service),
                ])->all()),
            ),
        ]);
    }

    /**
     * /servicos/{area}: the services of one area plus its latest projects.
     */
    public function area(string $area): View
    {
        $area = ServiceArea::fromSlug($area);

        $services = Service::active()->inArea($area)->withCount('activeProjects')->get();

        // An area with nothing published has no page (and stays out of the sitemap).
        abort_if($services->isEmpty(), 404);

        $projects = Project::active()
            ->with('service')
            ->whereIn('service_id', $services->pluck('id'))
            ->limit(6)
            ->get();

        $texts = config('showcase.areas.' . $area->value);
        $url   = route('services.area', ['area' => $area->slug()]);

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Serviços', 'url' => route('services.index')],
            ['name' => $area->getLabel()],
        ];

        return view('site.showcase.services.area', [
            'area'        => $area,
            'texts'       => $texts,
            'services'    => $services,
            'projects'    => $projects,
            'breadcrumbs' => $breadcrumbs,
            'jsonLd'      => StructuredData::graph(
                StructuredData::organization(),
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::itemList($texts['heading'], $url, $texts['description'], $services->map(fn (Service $service) => [
                    'name' => $service->name,
                    'url'  => $this->serviceUrl($service),
                ])->all()),
            ),
        ]);
    }

    /**
     * /servicos/{area}/{service}: the sales page of one service.
     */
    public function show(string $area, Service $service): View|RedirectResponse
    {
        abort_unless($service->is_active, 404);

        // The service moved to another area: send visitors and search engines to the right URL.
        if ($service->area->slug() !== $area) {
            return redirect()->to($this->serviceUrl($service), 301);
        }

        $service->incrementViews();

        $projects = $service->activeProjects()->with('service')->limit(6)->get();

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Serviços', 'url' => route('services.index')],
            ['name' => $service->area->getLabel(), 'url' => route('services.area', ['area' => $service->area->slug()])],
            ['name' => $service->name],
        ];

        // Other services of the same area, for internal linking.
        $related = Service::active()
            ->inArea($service->area)
            ->whereKeyNot($service->id)
            ->limit(3)
            ->get();

        return view('site.showcase.services.show', [
            'service'     => $service,
            'projects'    => $projects,
            'related'     => $related,
            'breadcrumbs' => $breadcrumbs,
            'jsonLd'      => StructuredData::graph(
                StructuredData::organization(),
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::service($service, $this->serviceUrl($service)),
                StructuredData::faq($service->faq),
            ),
        ]);
    }

    private function serviceUrl(Service $service): string
    {
        return route('services.show', ['area' => $service->area->slug(), 'service' => $service->slug]);
    }
}
