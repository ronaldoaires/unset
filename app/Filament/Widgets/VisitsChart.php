<?php

namespace App\Filament\Widgets;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Subcategory;
use Filament\Widgets\ChartWidget;

class VisitsChart extends ChartWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'O que mais é visitado';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'categories';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getFilters(): ?array
    {
        return [
            'categories'    => 'Categorias',
            'subcategories' => 'Subcategorias',
            'brands'        => 'Marcas',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $model = match ($this->filter) {
            'subcategories' => Subcategory::class,
            'brands'        => Brand::class,
            default         => Category::class,
        };

        $rows = $model::query()
            ->orderByDesc('view_count')
            ->limit(10)
            ->get(['name', 'view_count']);

        return [
            'datasets' => [
                [
                    'label'           => 'Visualizações',
                    'data'            => $rows->pluck('view_count')->map(fn ($v) => (int) $v)->all(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $rows->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            // Horizontal bars read better with long names.
            'indexAxis' => 'y',
            'plugins'   => ['legend' => ['display' => false]],
        ];
    }
}