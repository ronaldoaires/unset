<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ServiceArea;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Todos')];

        foreach (ServiceArea::cases() as $area) {
            $inArea = fn (Builder $query) => $query->whereHas('service', fn (Builder $q) => $q->inArea($area));

            $tabs[$area->value] = Tab::make($area->getLabel())
                ->badge($inArea(Project::query())->count())
                ->modifyQueryUsing($inArea);
        }

        return $tabs;
    }
}
