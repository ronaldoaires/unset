<?php

namespace App\Filament\Widgets;

use App\Models\Family;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MySubmissionsStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Minhas famílias';

    // Moderators only: the superadmin has the full dashboard instead.
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user->isAdmin() && ! $user->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $counts = Family::query()
            ->where('user_id', auth()->id())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            Stat::make('Rascunhos', $counts[Family::STATUS_DRAFT] ?? 0)
                ->color('gray'),

            Stat::make('Aguardando revisão', $counts[Family::STATUS_PENDING_REVIEW] ?? 0)
                ->color('warning'),

            Stat::make('Publicadas', $counts[Family::STATUS_PUBLISHED] ?? 0)
                ->color('success'),

            Stat::make('Rejeitadas', $counts[Family::STATUS_REJECTED] ?? 0)
                ->color('danger'),
        ];
    }
}