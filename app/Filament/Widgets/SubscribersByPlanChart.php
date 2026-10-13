<?php

namespace App\Filament\Widgets;

use App\Models\Plan;
use Filament\Widgets\ChartWidget;

class SubscribersByPlanChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Assinantes por plano';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $plans = Plan::query()->ordered()->withCount('activeSubscriptions')->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Assinantes',
                    'data'            => $plans->pluck('active_subscriptions_count')->all(),
                    'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#14b8a6'],
                ],
            ],
            'labels' => $plans->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        // Hide the axes Chart.js would otherwise draw on a doughnut.
        return [
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}