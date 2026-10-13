<?php

namespace App\Filament\Widgets;

use App\Models\Download;
use App\Models\Family;
use App\Models\Favorite;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Catálogo e comunidade';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        $pending = Family::pending()->count();

        return [
            Stat::make('Famílias publicadas', Family::published()->count())
                ->description($pending . ' aguardando revisão')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Downloads no mês', Download::whereBetween('downloaded_at', [$from, $to])->count())
                ->description(number_format(Download::count(), 0, ',', '.') . ' no total'),

            Stat::make('Novos usuários no mês', User::whereBetween('created_at', [$from, $to])->count())
                ->description(number_format(User::count(), 0, ',', '.') . ' no total'),

            Stat::make('Visualizações de famílias', number_format((int) Family::sum('view_count'), 0, ',', '.'))
                ->description(number_format(Favorite::count(), 0, ',', '.') . ' favoritos no total'),
        ];
    }
}