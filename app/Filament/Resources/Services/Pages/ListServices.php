<?php

namespace App\Filament\Resources\Services\Pages;

use App\Enums\ServiceArea;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * One tab per area, so engineering and technology stay apart without
     * two separate resources.
     */
    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Todos')];

        foreach (ServiceArea::cases() as $area) {
            $tabs[$area->value] = Tab::make($area->getLabel())
                ->badge(Service::query()->inArea($area)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->inArea($area));
        }

        return $tabs;
    }
}
