<?php

namespace App\Support;

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
     * Wraps nodes in a single @graph, ready for the layout's jsonLd prop.
     */
    public static function graph(array ...$nodes): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph'   => array_values(array_filter($nodes)),
        ];
    }
}