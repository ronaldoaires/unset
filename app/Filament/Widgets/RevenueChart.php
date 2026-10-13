<?php

namespace App\Filament\Widgets;

use App\Models\FamilyPurchase;
use App\Models\SubscriptionPayment;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Receita nos últimos 12 meses';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = now()->startOfMonth()->subMonths(11);

        $subscriptions = SubscriptionPayment::approved()
            ->where('paid_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $purchases = FamilyPurchase::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $subscriptionData = [];
        $purchaseData = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $key   = $month->format('Y-m');

            $labels[]           = $month->translatedFormat('M/y');
            $subscriptionData[] = (float) ($subscriptions[$key] ?? 0);
            $purchaseData[]     = (float) ($purchases[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Assinaturas',
                    'data'            => $subscriptionData,
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label'           => 'Compras avulsas',
                    'data'            => $purchaseData,
                    'backgroundColor' => '#10b981',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true],
            ],
        ];
    }
}