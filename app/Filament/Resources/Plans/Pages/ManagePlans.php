<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Filament\Resources\Plans\PlanResource;
use App\Models\Plan;
use App\Support\SlugGenerator;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManagePlans extends ManageRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::FourExtraLarge)
                ->mutateDataUsing(function (array $data): array {
                    $data['slug'] = SlugGenerator::unique(Plan::class, $data['name']);

                    return $data;
                })
                ->successNotificationTitle(fn (Plan $record) => 'Plano ' . $record->name . ' criado'),
        ];
    }
}