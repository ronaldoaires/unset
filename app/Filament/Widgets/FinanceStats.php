<?php

namespace App\Filament\Widgets;

use App\Models\FamilyPurchase;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Financeiro';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        $subscriptionsRevenue = (float) SubscriptionPayment::approved()
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $purchasesRevenue = (float) FamilyPurchase::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        // Monthly recurring revenue: yearly plans are divided by 12.
        $mrr = Subscription::active()
            ->with('plan')
            ->get()
            ->sum(fn (Subscription $subscription) => $subscription->isYearly()
                ? ($subscription->plan?->price_yearly ?? 0) / 12
                : ($subscription->plan?->price_monthly ?? 0));

        $pastDue = Subscription::where('status', 'past_due')->count();

        return [
            Stat::make('Receita do mês', $this->money($subscriptionsRevenue + $purchasesRevenue))
                ->description('Assinaturas ' . $this->money($subscriptionsRevenue) . ' · Avulsas ' . $this->money($purchasesRevenue))
                ->color('success'),

            Stat::make('Assinantes ativos', Subscription::active()->count())
                ->description(Subscription::trial()->count() . ' em período de teste'),

            Stat::make('Receita recorrente mensal', $this->money((float) $mrr))
                ->description('Estimativa das assinaturas ativas'),

            Stat::make('Pagamentos pendentes', $pastDue)
                ->description('Assinaturas com pagamento pendente')
                ->color($pastDue > 0 ? 'warning' : 'gray'),
        ];
    }

    private function money(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}