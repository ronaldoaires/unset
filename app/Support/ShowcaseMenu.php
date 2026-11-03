<?php

namespace App\Support;

use App\Enums\ServiceArea;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

/**
 * Active services grouped by area, for the header submenu and the footer.
 * Both render on every page, so the list is cached and rebuilt only when a
 * service is saved or deleted (see Service::booted()).
 */
class ShowcaseMenu
{
    private const CACHE_KEY = 'showcase.menu';

    /**
     * @return array<int, array{label: string, heading: string, url: string, services: array<int, array{name: string, url: string}>}>
     */
    public static function areas(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $services = Service::active()->get(['id', 'area', 'name', 'slug', 'sort_order']);

            $areas = [];

            foreach (ServiceArea::cases() as $area) {
                $inArea = $services->filter(fn (Service $service) => $service->area === $area);

                if ($inArea->isEmpty()) {
                    continue;
                }

                $areas[] = [
                    'label'    => $area->getLabel(),
                    'heading'  => config('showcase.areas.' . $area->value . '.heading'),
                    'url'      => route('services.area', ['area' => $area->slug()]),
                    'services' => $inArea->map(fn (Service $service) => [
                        'name' => $service->name,
                        'url'  => route('services.show', ['area' => $area->slug(), 'service' => $service->slug]),
                    ])->values()->all(),
                ];
            }

            return $areas;
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
