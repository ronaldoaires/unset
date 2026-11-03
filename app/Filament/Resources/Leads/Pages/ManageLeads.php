<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadType;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageLeads extends ManageRecords
{
    protected static string $resource = LeadResource::class;

    // No header actions: leads only come from the public forms.
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Em aberto')
                ->badge(Lead::query()->open()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->open()),

            'quote' => Tab::make(LeadType::Quote->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', LeadType::Quote)),

            'contact' => Tab::make(LeadType::Contact->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', LeadType::Contact)),

            'all' => Tab::make('Todos'),
        ];
    }
}
