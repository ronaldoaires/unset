<?php

namespace App\Http\Controllers\Site\Showcase;

use App\Enums\ServiceArea;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    private const PER_PAGE = 12;

    /**
     * /portfolio, filtered by ?area= and ?servico= (plain GET, so a filtered
     * list can be copied and shared like any other link).
     */
    public function index(Request $request): View
    {
        $area    = ServiceArea::fromSlug((string) $request->query('area'));
        $service = $request->filled('servico')
            ? Service::active()->where('slug', $request->query('servico'))->first()
            : null;

        $projects = Project::active()
            ->with('service')
            ->whereHas('service', function (Builder $query) use ($area) {
                $query->where('is_active', true);

                if ($area) {
                    $query->inArea($area);
                }
            })
            ->when($service, fn (Builder $query) => $query->where('service_id', $service->id))
            ->paginate(self::PER_PAGE, pageName: 'pagina')
            ->withQueryString();

        $url        = route('portfolio.index');
        $hasFilters = $area !== null || $service !== null;

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Portfólio'],
        ];

        $title = 'Portfólio de projetos';

        if ($projects->currentPage() > 1) {
            $title .= ' - Página ' . $projects->currentPage();
        }

        return view('site.showcase.portfolio.index', [
            'projects'    => $projects,
            'area'        => $area,
            'service'     => $service,
            'filters'     => $this->filterOptions(),
            'title'       => $title,
            'breadcrumbs' => $breadcrumbs,
            'canonical'   => $projects->currentPage() > 1 ? $url . '?pagina=' . $projects->currentPage() : $url,
            // Filtered and empty lists are thin duplicates of the main one.
            'noindex'     => $hasFilters || $projects->isEmpty(),
            'jsonLd'      => StructuredData::graph(
                StructuredData::organization(),
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::itemList(
                    'Portfólio',
                    $url,
                    null,
                    collect($projects->items())->map(fn (Project $project) => [
                        'name' => $project->title,
                        'url'  => route('portfolio.show', ['project' => $project->slug]),
                    ])->all(),
                    max(0, ($projects->firstItem() ?? 1) - 1),
                ),
            ),
        ]);
    }

    public function show(Project $project): View
    {
        $project->loadMissing(['service', 'images']);

        abort_unless($project->is_active && $project->service?->is_active, 404);

        $project->incrementViews();

        $serviceUrl = route('services.show', ['area' => $project->service->area->slug(), 'service' => $project->service->slug]);

        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Portfólio', 'url' => route('portfolio.index')],
            ['name' => $project->service->name, 'url' => $serviceUrl],
            ['name' => $project->title],
        ];

        $related = Project::active()
            ->with('service')
            ->where('service_id', $project->service_id)
            ->whereKeyNot($project->id)
            ->limit(3)
            ->get();

        return view('site.showcase.portfolio.show', [
            'project'     => $project,
            'serviceUrl'  => $serviceUrl,
            'related'     => $related,
            'breadcrumbs' => $breadcrumbs,
            'jsonLd'      => StructuredData::graph(
                StructuredData::organization(),
                StructuredData::breadcrumbs($breadcrumbs),
                StructuredData::project($project, route('portfolio.show', ['project' => $project->slug])),
            ),
        ]);
    }

    /**
     * Areas and services that actually have published projects.
     *
     * @return array<string, array{label: string, services: \Illuminate\Support\Collection}>
     */
    private function filterOptions(): array
    {
        $services = Service::active()->whereHas('activeProjects')->get(['id', 'area', 'name', 'slug', 'sort_order']);

        $options = [];

        foreach (ServiceArea::cases() as $area) {
            $inArea = $services->filter(fn (Service $service) => $service->area === $area)->values();

            if ($inArea->isNotEmpty()) {
                $options[$area->slug()] = ['label' => $area->getLabel(), 'services' => $inArea];
            }
        }

        return $options;
    }
}
