<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Service;
use Illuminate\Pagination\LengthAwarePaginator;

class StructuredData
{
    /**
     * Builds a BreadcrumbList node. Each item is ['name' => ..., 'url' => ...];
     * the last item usually has no url (it is the current page).
     */
    public static function breadcrumbs(array $items): array
    {
        $list = [];

        foreach (array_values($items) as $index => $item) {
            $entry = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $item['name'],
            ];

            if (! empty($item['url'])) {
                $entry['item'] = $item['url'];
            }

            $list[] = $entry;
        }

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    /**
     * Builds a CollectionPage node whose main entity is the list of
     * families shown on the current page of results.
     */
    public static function collectionPage(
        string $name,
        string $url,
        ?string $description,
        LengthAwarePaginator $families,
    ): array {
        $items = [];

        foreach (array_values($families->items()) as $index => $family) {
            $items[] = [
                '@type'    => 'ListItem',
                // Position continues across pages (13, 14, ... on page 2).
                'position' => $families->firstItem() + $index,
                'url'      => route('revit.families.show', ['family' => $family->slug]),
                'name'     => $family->name,
            ];
        }

        return array_filter([
            '@type'       => 'CollectionPage',
            'name'        => $name,
            'url'         => $url,
            'description' => $description,
            'mainEntity'  => [
                '@type'           => 'ItemList',
                'itemListElement' => $items,
            ],
        ]);
    }

    /**
     * The company behind the services, with its technical lead. Other nodes
     * point to it through its @id instead of repeating the data.
     */
    public static function organization(): array
    {
        $company     = config('showcase.company');
        $person = config('showcase.person');

        return [
            '@type'      => 'ProfessionalService',
            '@id'        => url('/') . '#organization',
            'name'       => $company['name'],
            'url'        => url('/'),
            'areaServed' => ['@type' => 'Country', 'name' => $company['area_served']],
            'employee'   => [
                '@type'      => 'Person',
                'name'       => $person['name'],
                'jobTitle'   => array_values(array_map(fn (array $profile) => $profile['title'], config('showcase.profiles'))),
                'identifier' => config('showcase.profiles.engineering.badge'),
                'sameAs'     => array_values($person['links']),
            ],
        ];
    }

    public static function service(Service $service, string $url): array
    {
        return array_filter([
            '@type'       => 'Service',
            'name'        => $service->name,
            'serviceType' => $service->name,
            'description' => $service->meta_description,
            'url'         => $url,
            'image'       => $service->cover_url,
            'category'    => $service->area->getLabel(),
            'provider'    => ['@id' => url('/') . '#organization'],
            'areaServed'  => ['@type' => 'Country', 'name' => config('showcase.company.area_served')],
        ]);
    }

    /**
     * FAQPage node from the service's [{question, answer}] list, or null
     * when there are no questions (graph() drops empty nodes).
     */
    public static function faq(?array $items): ?array
    {
        $questions = collect($items ?? [])
            ->filter(fn ($item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->map(fn ($item) => [
                '@type'          => 'Question',
                'name'           => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ])
            ->values()
            ->all();

        return $questions ? ['@type' => 'FAQPage', 'mainEntity' => $questions] : null;
    }

    public static function project(Project $project, string $url): array
    {
        $images = collect([$project->cover_url])
            ->merge($project->images->pluck('url'))
            ->filter()
            ->values()
            ->all();

        return array_filter([
            '@type'           => 'CreativeWork',
            'name'            => $project->title,
            'description'     => $project->meta_description,
            'url'             => $url,
            'image'           => $images,
            'dateCreated'     => $project->year ? (string) $project->year : null,
            'datePublished'   => $project->published_at?->toAtomString(),
            'locationCreated' => $project->location ? ['@type' => 'Place', 'name' => $project->location] : null,
            'about'           => ['@type' => 'Service', 'name' => $project->service->name],
            'creator'         => ['@id' => url('/') . '#organization'],
        ]);
    }

    /**
     * Generic ItemList for pages that list links (services, projects).
     *
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function itemList(string $name, string $url, ?string $description, array $items, int $offset = 0): array
    {
        return array_filter([
            '@type'       => 'CollectionPage',
            'name'        => $name,
            'url'         => $url,
            'description' => $description,
            'mainEntity'  => [
                '@type'           => 'ItemList',
                'itemListElement' => collect($items)->values()->map(fn ($item, $index) => [
                    '@type'    => 'ListItem',
                    'position' => $offset + $index + 1,
                    'url'      => $item['url'],
                    'name'     => $item['name'],
                ])->all(),
            ],
        ]);
    }

    /**
     * Wraps nodes in a single @graph, ready for the layout's jsonLd prop.
     * Null nodes (e.g. faq() for a service without questions) are skipped.
     */
    public static function graph(?array ...$nodes): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph'   => array_values(array_filter($nodes)),
        ];
    }
}