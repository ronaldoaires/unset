<?php

namespace App\Filament\Widgets;

use App\Models\Download;
use Filament\Widgets\ChartWidget;

class DownloadsChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Downloads por dia';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getFilters(): ?array
    {
        return [
            '7'  => 'Últimos 7 dias',
            '30' => 'Últimos 30 dias',
            '90' => 'Últimos 90 dias',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days  = (int) $this->filter;
        $start = now()->subDays($days - 1)->startOfDay();

        $totals = Download::query()
            ->where('downloaded_at', '>=', $start)
            ->selectRaw('DATE(downloaded_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data   = [];

        // Fill days without downloads with zero so the line has no gaps.
        for ($i = 0; $i < $days; $i++) {
            $date     = $start->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $data[]   = (int) ($totals[$date->format('Y-m-d')] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'       => 'Downloads',
                    'data'        => $data,
                    'borderColor' => '#3b82f6',
                    'fill'        => true,
                    'tension'     => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}