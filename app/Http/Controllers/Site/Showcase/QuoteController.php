<?php

namespace App\Http\Controllers\Site\Showcase;

use App\Enums\LeadType;
use App\Enums\ServiceArea;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use App\Services\LeadService;
use App\Support\BotTrap;
use App\Support\BrazilStates;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    /**
     * Extra questions per area. Keys are the field names; labels are stored
     * with the answers, so the admin reads them as written here.
     */
    public const DETAILS = [
        'engineering' => [
            'area'         => ['label' => 'Área aproximada (m²)', 'type' => 'number'],
            'floors'       => ['label' => 'Pavimentos', 'options' => ['1', '2', '3', '4 ou mais']],
            'architecture' => ['label' => 'Já tem projeto arquitetônico?', 'options' => ['Sim', 'Não', 'Não se aplica']],
        ],
        'technology' => [
            'kind'     => ['label' => 'Tipo de projeto', 'options' => ['Site institucional', 'Loja virtual', 'Sistema web', 'Aplicativo', 'Outro']],
            'deadline' => ['label' => 'Prazo desejado', 'options' => ['Até 1 mês', '1 a 3 meses', 'Mais de 3 meses', 'Sem pressa']],
            'budget'   => ['label' => 'Investimento previsto', 'options' => ['Até R$ 3 mil', 'R$ 3 mil a R$ 10 mil', 'R$ 10 mil a R$ 30 mil', 'Acima de R$ 30 mil', 'Ainda não sei']],
        ],
    ];

    public function create(Request $request): View
    {
        $services = Service::active()->get(['id', 'area', 'name', 'slug', 'sort_order']);

        $selected = $request->filled('servico')
            ? $services->firstWhere('slug', $request->query('servico'))
            : null;

        $project = $request->filled('projeto')
            ? Project::query()->where('is_active', true)->where('slug', $request->query('projeto'))->first(['id', 'slug', 'title', 'service_id'])
            : null;

        // A project link implies its service.
        $selected ??= $project ? $services->firstWhere('id', $project->service_id) : null;

        return view('site.showcase.quote.create', [
            'servicesByArea' => collect(ServiceArea::cases())
                ->mapWithKeys(fn (ServiceArea $area) => [$area->value => $services->filter(fn (Service $s) => $s->area === $area)->values()])
                ->filter(fn ($group) => $group->isNotEmpty()),
            'selected'  => $selected,
            'project'   => $project,
            'details'   => self::DETAILS,
            'states'    => BrazilStates::ALL,
        ]);
    }

    public function store(Request $request, LeadService $leads): RedirectResponse
    {
        // Honeypot filled (bot): answer as if it worked, but save and send nothing.
        if (! BotTrap::verify($request, 'message')) {
            return redirect()->route('leads.thanks')->with('lead_type', LeadType::Quote->value);
        }

        LeadService::normalize($request);

        $data = $request->validate([
            ...LeadService::contactRules(),
            'phone'      => ['required', 'digits_between:10,11'],
            'city'       => ['nullable', 'string', 'max:100'],
            'state'      => ['nullable', Rule::in(BrazilStates::codes())],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('is_active', true)],
            'details'    => ['nullable', 'array'],
            'details.*'  => ['nullable', 'string', 'max:100'],
            'privacy'    => ['accepted'],
        ], [
            'phone.required'       => 'Informe seu WhatsApp para retornarmos.',
            'phone.digits_between' => 'Informe o WhatsApp com DDD (10 ou 11 dígitos).',
            'privacy.accepted'     => 'Para enviar, aceite a Política de Privacidade.',
        ]);

        $data['details'] = $this->details($data, $request);

        $leads->capture(LeadType::Quote, $data, $request);

        return redirect()->route('leads.thanks')->with('lead_type', LeadType::Quote->value);
    }

    /**
     * Keeps only the answers of the chosen service's area (both groups are
     * in the form) and stores them as "label => answer".
     *
     * @return array<string, string>|null
     */
    private function details(array $data, Request $request): ?array
    {
        $service = isset($data['service_id']) ? Service::find($data['service_id']) : null;

        if (! $service) {
            return null;
        }

        $answers = [];

        foreach (self::DETAILS[$service->area->value] ?? [] as $key => $question) {
            $value = trim((string) ($data['details'][$key] ?? ''));

            if ($value === '') {
                continue;
            }

            // Selects only accept their own options; free numbers stay numbers.
            if (isset($question['options']) && ! in_array($value, $question['options'], true)) {
                continue;
            }

            if (($question['type'] ?? null) === 'number' && ! is_numeric(str_replace(',', '.', $value))) {
                continue;
            }

            $answers[$question['label']] = $value;
        }

        return $answers ?: null;
    }

    public function thanks(): View|RedirectResponse
    {
        $type = LeadType::tryFrom((string) session('lead_type'));

        // Flash data lives for one request: a refresh goes back to the services.
        return $type
            ? view('site.showcase.thanks', ['type' => $type])
            : redirect()->route('services.index');
    }
}
